<?php

namespace Drupal\social_event_an_enroll\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\social_email_broadcast\SocialEmailBroadcast;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_an_enroll\EventAnEnrollManager;
use Drupal\social_event_managers\Plugin\Action\SocialEventManagersSendEmail;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send email to event enrollment users.
 */
#[Action(
  id: 'social_event_an_enroll_send_email_action',
  label: new TranslatableMarkup('Email'),
  confirm_form_route_name: 'social_event_managers.vbo.confirm',
  type: 'event_enrollment',
)]
class SocialEventAnEnrollSendEmail extends SocialEventManagersSendEmail {

  /**
   * The event enrollment.
   *
   * @var \Drupal\social_event\EventEnrollmentInterface
   */
  protected $entity;

  /**
   * The event an enroll manager.
   */
  protected EventAnEnrollManager $socialEventAnEnrollManager;

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
    EventAnEnrollManager $event_an_enroll_manager,
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
      $allow_text_format,
      $email_broadcast_service
    );

    $this->socialEventAnEnrollManager = $event_an_enroll_manager;
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
      $container->get('social_event_an_enroll.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $guests = [];
    foreach ($objects as $key => $entity) {
      if ($this->socialEventAnEnrollManager->isGuest($entity)) {
        $guests[$key] = [
          'email_address' => $entity->field_email->value,
          'display_name' => $this->getDisplayName($entity),
        ];
      }
    }

    if (!empty($guests)) {
      // Create some chunks and then make queue items.
      $chunk_size = Settings::get('social_mail_chunk_size', 10);
      $chunks = array_chunk($guests, $chunk_size);
      foreach ($chunks as $chunk) {
        // Get the entity ID of the email that is send.
        $data['mail'] = $this->configuration['queue_storage_id'];
        // Add the list of user IDs.
        $data['user_mail_addresses'] = $chunk;

        // Put the $data in the queue item.
        /** @var \Drupal\Core\Queue\QueueInterface $queue */
        $queue = $this->queue->get('user_email_queue');
        $queue->createItem($data);
      }
    }

    // Before parent remove the guest from the objects list.
    // Otherwise they will be processed as users and it will break as there
    // is no user account.
    $objects = array_diff_key($objects, $guests);

    // Execute parent as we still need to check if there are users enrolled.
    return parent::executeMultiple($objects);
  }

  /**
   * Get the display name of the guest.
   *
   * @param \Drupal\social_event\EventEnrollmentInterface $entity
   *   The event enrolment to get the name from.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The name of the guest enrolment.
   */
  public function getDisplayName(EventEnrollmentInterface $entity) {
    $display_name = $this->socialEventAnEnrollManager->getGuestName($entity, FALSE);

    if (!$display_name) {
      $display_name = $this->t('Guest');
    }
    return $display_name;
  }

}
