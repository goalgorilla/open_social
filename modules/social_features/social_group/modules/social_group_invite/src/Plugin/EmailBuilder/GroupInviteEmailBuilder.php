<?php

namespace Drupal\social_group_invite\Plugin\EmailBuilder;

use Drupal\Core\TempStore\SharedTempStore;
use Drupal\Core\TempStore\SharedTempStoreFactory;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_group_invite\Plugin\Action\SocialGroupInviteResend;
use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailBuilderBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Render\Markup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the Email Builder plugin for the social_group_invite module.
 *
 * This mail is sent when people who do not have an account on the website yet
 * are invited into an event. It is sent in the language the inviter was using
 * the website in.
 *
 * @EmailBuilder(
 *   id = "ginvite",
 *   sub_types = {
 *     "invite" = @Translation("Group invite")
 *   }
 * )
 */
class GroupInviteEmailBuilder extends EmailBuilderBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The token service.
   */
  protected Token $token;

  /**
   * The language manager.
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Stores the shared tempstore.
   */
  protected SharedTempStore $tempStore;

  /**
   * The current user service.
   */
  protected AccountInterface $currentUser;

  /**
   * The user storage.
   */
  protected UserStorageInterface $userStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('token'),
      $container->get('language_manager'),
      $container->get('tempstore.shared'),
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  /**
   * Constructs an EventInviteEmailBuilder object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\TempStore\SharedTempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    Token $token,
    LanguageManagerInterface $language_manager,
    SharedTempStoreFactory $temp_store_factory,
    AccountInterface $current_user,
    UserStorageInterface $user_storage,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->token = $token;
    $this->languageManager = $language_manager;
    $this->tempStore = $temp_store_factory->get('social_group_invite');
    $this->currentUser = $current_user;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(EmailInterface $email): void {
    $params = $email->getParams();
    $language_manager = $this->languageManager;
    $langcode = $email->getLangcode();
    $language = $language_manager->getLanguage($langcode);
    $original_language = $language_manager->getConfigOverrideLanguage();
    $language_manager->setConfigOverrideLanguage($language);

    // Load configuration.
    $group_config = $this->configFactory->getEditable('social_group.settings');
    $invite_settings = $group_config->get('group_invite');

    // The mail params list should contain group content entity.
    /* @see ginvite_group_content_insert() */
    $invite = $params['group_content'] ?? NULL;

    if (!$invite instanceof GroupRelationshipInterface || $invite->getPluginId() !== 'group_invitation') {
      $language_manager->setConfigOverrideLanguage($original_language);
      return;
    }

    $is_existing_user = !empty($params['existing_user']);
    $overridden_body = NULL;

    // Check if the invitation is resent and site managers decided to change
    // the invitation email text.
    if (!empty($invite_settings['invite_resend_message'])) {
      $resent_invites = (array) $this->tempStore->get(SocialGroupInviteResend::TEMP_STORE_ID);
      if (!empty($resent_invites) && in_array($invite->uuid(), $resent_invites)) {
        $overridden_body = $invite_settings['invite_resend_message'];
        // Remove handled resent invite from list.
        unset($resent_invites[$invite->uuid()]);
        $this->tempStore->set(SocialGroupInviteResend::TEMP_STORE_ID, $resent_invites);
      }
    }

    // Get invitation templates from plugin configuration.
    $plugin_config = $this->getInvitationTemplates($invite, $is_existing_user);

    // Determine which templates to use.
    if (is_null($invite_settings)) {
      // No custom settings: use plugin configuration directly.
      $invitation_subject = $plugin_config['subject'];
      $invitation_body = $plugin_config['body'];
    }
    elseif (isset($invite_settings['invite_subject'], $invite_settings['invite_message'])) {
      // If custom settings: use plugin config, otherwise fall back to custom.
      $invitation_subject = $plugin_config['subject'] ?: $invite_settings['invite_subject'];
      $invitation_body = $overridden_body ?? ($plugin_config['body'] ?: $invite_settings['invite_message']);
    }
    else {
      // Invalid custom settings: use plugin configuration.
      $invitation_subject = $plugin_config['subject'];
      $invitation_body = $plugin_config['body'];
    }

    $email->setSubject($this->token->replace($invitation_subject, $params));
    $email->setBody(Markup::create($this->token->replace($invitation_body, $params)));

    $language_manager->setConfigOverrideLanguage($original_language);
  }

  /**
   * Gets the invitation templates from plugin configuration.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface $invite
   *   The group invitation entity.
   * @param bool $is_existing_user
   *   Whether the invitee is an existing user.
   *
   * @return array
   *   An array with 'subject' and 'body' keys containing the template strings.
   */
  protected function getInvitationTemplates(GroupRelationshipInterface $invite, bool $is_existing_user): array {
    /** @var \Drupal\group\Plugin\Group\Relation\GroupRelationInterface $group_content_plugin */
    $group_content_plugin = $invite->getPlugin();
    $configuration = $group_content_plugin->getConfiguration();

    if ($is_existing_user) {
      return [
        'subject' => $configuration['existing_user_invitation_subject'] ?? '',
        'body' => $configuration['existing_user_invitation_body'] ?? '',
      ];
    }

    return [
      'subject' => $configuration['invitation_subject'] ?? '',
      'body' => $configuration['invitation_body'] ?? '',
    ];
  }

  /**
   * Saves the parameters for a newly created email.
   *
   * @param \Drupal\symfony_mailer\EmailInterface $email
   *   The email to modify.
   * @param mixed $params
   *   The params containing the site name.
   * @param mixed $to
   *   The to addresses, see Address::convert().
   */
  public function createParams(EmailInterface $email, $params = NULL, $to = NULL): void {
    // Override the user parameter with the current user for token replacement.
    $params['user'] = $this->userStorage->load($this->currentUser->id());
    $email->setParams($params);
    $email->setParam('to', $to);
  }

  /**
   * {@inheritdoc}
   */
  public function fromArray(EmailFactoryInterface $factory, array $message): EmailInterface {
    return $factory->newTypedEmail($message['module'], $message['key'], $message['params'], $message['to']);
  }

  /**
   * {@inheritdoc}
   */
  public function build(EmailInterface $email): void {
    $email->setTo($email->getParam('to'));
  }

}
