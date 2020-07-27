<?php

namespace Drupal\social_group_invite\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\ginvite\GroupInvitationLoader;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\group\Plugin\GroupContentEnablerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ginvite\Form\BulkGroupInvitation;

/**
 * Class SocialBulkGroupInvitation.
 */
class SocialBulkGroupInvitation extends BulkGroupInvitation {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * Group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The group content plugin manager.
   *
   * @var \Drupal\group\Plugin\GroupContentEnablerManagerInterface
   */
  protected $pluginManager;

  /**
   * Constructs a new BulkGroupInvitation Form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   * @param \Drupal\ginvite\GroupInvitationLoader $invitation_loader
   *   Invitations loader service.
   * @param \Drupal\group\Plugin\GroupContentEnablerManagerInterface $plugin_manager
   *   The group content enabler manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    PrivateTempStoreFactory $temp_store_factory,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    GroupMembershipLoaderInterface $group_membership_loader,
    GroupInvitationLoader $invitation_loader,
    GroupContentEnablerManagerInterface $plugin_manager,
    ConfigFactoryInterface $config_factory,
    Token $token
  ) {
    parent::__construct($route_match, $entity_type_manager, $temp_store_factory, $logger_factory, $messenger, $group_membership_loader, $invitation_loader);
    $this->group = $this->routeMatch->getParameter('group');
    $this->pluginManager = $plugin_manager;
    $this->configFactory = $config_factory;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('group.membership_loader'),
      $container->get('ginvite.invitation_loader'),
      $container->get('plugin.manager.group_content_enabler'),
      $container->get('config.factory'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_bulk_group_invitation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $group = $this->routeMatch->getParameter('group');

    $params = [
      'user' => $this->currentUser(),
      'group' => $this->routeMatch->getParameter('group'),
    ];

    // Load plugin configuration.
    $group_plugin_collection = $this->pluginManager->getInstalled($group->getGroupType());
    $group_invite_config = $group_plugin_collection->getConfiguration()['group_invitation'];

    $invitation_subject = $group_invite_config['invitation_subject'];
    $invitation_body = $group_invite_config['invitation_body'];

    // Cleanup message body and replace any links on preview page.
    $invitation_body = $this->token->replace($invitation_body, $params);
    $invitation_body = preg_replace('/href="([^"]*)"/', 'href="#"', $invitation_body);

    // Get default logo image and replace if it overridden with email settings.
    $theme_id = $this->configFactory->get('system.theme')->get('default');
    $logo = $this->getRequest()->getBaseUrl() . theme_get_setting('logo.url', $theme_id);
    $email_logo = theme_get_setting('email_logo', $theme_id);

    if (is_array($email_logo) && !empty($email_logo)) {
      $file = File::load(reset($email_logo));

      if ($file instanceof File) {
        $logo = file_create_url($file->getFileUri());
      }
    }

    // Load event invite configuration.
    $invite_config = $this->configFactory->get('social_group_invite.settings');

    $form['preview'] = [
      '#theme' => 'invite_email_preview',
      '#title' => $this->t('Message'),
      '#logo' => $logo,
      '#subject' => $this->token->replace($invitation_subject, $params),
      '#body' => $invitation_body,
      '#helper' => $this->token->replace($invite_config->get('invite_helper'), $params),
    ];

    $form['actions']['#type'] = 'actions';
    unset($form['submit']);
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send invite(s)'),
    ];

    return $form;
  }

}
