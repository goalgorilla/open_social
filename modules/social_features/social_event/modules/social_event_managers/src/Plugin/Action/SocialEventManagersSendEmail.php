<?php

namespace Drupal\social_event_managers\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\social_email_broadcast\SocialEmailBroadcast;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_user\Plugin\Action\SocialSendEmail;
use Drupal\user\UserInterface;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send email to event enrollment users.
 */
#[Action(
  id: 'social_event_managers_send_email_action',
  label: new TranslatableMarkup('Send email to event enrollment users'),
  confirm_form_route_name: 'social_event_managers.vbo.confirm',
  type: 'event_enrollment',
)]
class SocialEventManagersSendEmail extends SocialSendEmail {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

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

    $this->entityTypeManager = $entity_type_manager;
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
      $container->get(SocialEmailBroadcast::class),
    );
  }

  /**
   * Helps to check if a user is subscribed or not for bulk mailing.
   *
   * @param string|int|\Drupal\social_event\EventEnrollmentInterface $enrollment
   *   The enrollment entity.
   *
   * @return bool
   *   If subscribed TRUE, otherwise FALSE.
   *
   * @throws \Exception
   */
  public function isSubscribedForBulkEmails(string|int|EventEnrollmentInterface $enrollment): bool {
    if (!$enrollment instanceof EventEnrollmentInterface) {
      $enrollment = EventEnrollment::load($enrollment);

      if (!$enrollment instanceof EventEnrollmentInterface) {
        return TRUE;
      }
    }

    $user = $enrollment->getAccountEntity();
    if (!$user instanceof UserInterface) {
      return TRUE;
    }

    if (!$user->isAuthenticated()) {
      return TRUE;
    }

    $frequency = $this->emailBroadcast->getBulkEmailUserSetting(account: $user, name: 'event_enrollees');
    return empty($frequency) || $frequency !== SocialEmailBroadcast::FREQUENCY_NONE;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    // Process the event enrollment chunks. These need to be converted to users.
    /** @var \Drupal\social_event\Entity\EventEnrollment $enrollment */
    foreach ($objects as $enrollment) {
      // Get the user from the even enrollment.
      /** @var \Drupal\user\Entity\User $user */
      $user = $enrollment->getAccountEntity();
      if (!$user instanceof UserInterface) {
        continue;
      }

      $users[] = $user;
    }

    // Pass it back to our parent who handles the creation of queue items.
    return parent::executeMultiple($users ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function createQueueItem($name, array $data): void {
    $data['bulk_mail_footer'] = TRUE;

    parent::createQueueItem($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, ?AccountInterface $account = NULL, $return_as_object = FALSE): bool|AccessResultInterface {
    $access = AccessResult::allowedIf($object instanceof EventEnrollmentInterface);

    if ($object instanceof EventEnrollmentInterface) {
      // All users with the following access permission should be allowed.
      $access = AccessResult::allowedIfHasPermission($account, 'manage everything enrollments');

      $event_id = $object->getFieldValue('field_event', 'target_id');
      $node = $this->entityTypeManager->getStorage('node')->load($event_id);

      // Also Event organizers can do this.
      if ($node instanceof NodeInterface && social_event_manager_or_organizer($node)) {
        $access = AccessResult::allowed();
      }
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $this->messenger()
      ->addWarning($this->t('Your email will be sent to members who have opted to receive community updates and announcements'));

    // Add title to the form as well.
    if ($form['#title'] !== NULL) {
      $selected_count = $this->context['selected_count'];
      $subtitle = $this->formatPlural($selected_count,
        'Configure the email you want to send to the one enrollee you have selected.',
        'Configure the email you want to send to the @count enrollees you have selected.'
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

}
