<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_email_broadcast\SocialEmailBroadcast;
use Drupal\social_user\Plugin\Action\SocialSendEmail as SocialSendEmailBase;
use Drupal\user\UserInterface;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send an email to group members.
 */
#[Action(
  id: 'social_group_send_email_action',
  label: new TranslatableMarkup('Send email to group members'),
  type: 'group_content',
)]
class SocialSendEmail extends SocialSendEmailBase {

  /**
   * The Drupal module handler service.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The email broadcast service.
   */
  protected SocialEmailBroadcast $emailBroadcast;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Token $token,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    LanguageManagerInterface $language_manager,
    EmailValidator $email_validator,
    QueueFactory $queue_factory,
    $allow_text_format,
    ModuleHandlerInterface $module_handler,
    SocialEmailBroadcast $email_broadcast_service,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $token,
      $entity_type_manager,
      $logger,
      $language_manager,
      $email_validator,
      $queue_factory,
      $allow_text_format
    );

    $this->moduleHandler = $module_handler;
    $this->emailBroadcast = $email_broadcast_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('action'),
      $container->get('language_manager'),
      $container->get('email.validator'),
      $container->get('queue'),
      $container->get('current_user')->hasPermission('use text format mail_html'),
      $container->get('module_handler'),
      $container->get(SocialEmailBroadcast::class),
    );
  }

  /**
   * Helps to check if a user is subscribed or not for bulk mailing.
   *
   * @param \Drupal\group\Entity\GroupRelationshipInterface|string|int $membership
   *   The group content id (membership).
   *
   * @return bool
   *   If subscribed TRUE, otherwise FALSE.
   *
   * @throws \Exception
   */
  public function isSubscribedForBulkEmails(string|int|GroupRelationshipInterface $membership): bool {
    if (!$membership instanceof GroupRelationshipInterface) {
      $membership = GroupRelationship::load($membership);

      if (!$membership instanceof GroupMembershipInterface) {
        return TRUE;
      }
    }

    $user = $membership->getEntity();
    if (!$user instanceof UserInterface) {
      return TRUE;
    }

    if (!$user->isAuthenticated()) {
      return TRUE;
    }

    $setting_name = $this->getUnsubscribeSettingName();
    if (empty($setting_name)) {
      return TRUE;
    }

    $frequency = $this->emailBroadcast->getBulkEmailUserSetting(account: $user, name: $setting_name);
    return empty($frequency) || $frequency !== SocialEmailBroadcast::FREQUENCY_NONE;
  }

  /**
   * Helps to get unsubscribe setting.
   *
   * @return string
   *   The name of unsubscribe setting or empty.
   */
  private function getUnsubscribeSettingName(): string {
    $items = [];
    $this->moduleHandler->alter('social_email_broadcast_notifications', $items);

    if (empty($items['community_updates']['bulk_mailing'])) {
      return '';
    }

    foreach ($items['community_updates']['bulk_mailing'] as $setting) {
      if (empty($setting['entity_type']['group'])) {
        continue;
      }

      if (in_array($this->context['group_type'], (array) $setting['entity_type']['group'])) {
        $setting_name = $setting['name'];
        break;
      }
    }

    return $setting_name ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
    if ($object instanceof GroupRelationshipInterface) {
      return $object->access('view', $account, $return_as_object);
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(array &$context): void {
    $context['validate_email_subscriptions_callback'] = 'isSubscribedForBulkEmails';

    // We should set these values only if empty.
    // On batch, they may be overridden.
    if (!isset($context['results']['removed_selections']['count'])) {
      $context['results']['removed_selections'] = [
        'count' => 0,
        'message' => [
          'singular' => "1 member won't receive this email due to their communication preferences.",
          'plural' => "@count members won't receive this email due to their communication preferences.",
        ],
      ];
    }

    parent::setContext($context);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $entity */
    return $entity->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $this->messenger()
      ->addWarning($this->t('Your email will be sent to members who have opted to receive community updates and announcements.'));

    // Add title to the form as well.
    if ($form['#title'] !== NULL) {
      $selected_count = $this->context['selected_count'];
      $subtitle = $this->formatPlural($selected_count,
        'Configure the email you want to send to the one member you have selected.',
        'Configure the email you want to send to the @count members you have selected.'
      );

      $form['subtitle'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['placeholder'],
        ],
        '#value' => $subtitle,
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function createQueueItem($name, array $data): void {
    $data['bulk_mail_footer'] = TRUE;

    parent::createQueueItem($name, $data);
  }

}
