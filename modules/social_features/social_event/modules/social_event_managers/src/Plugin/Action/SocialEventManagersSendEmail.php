<?php

namespace Drupal\social_event_managers\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_user\Plugin\Action\SocialSendEmail;
use Drupal\user\Entity\User;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;

/**
 * Send email to event enrollment users.
 *
 * @Action(
 *   id = "social_event_managers_send_email_action",
 *   label = @Translation("Send email to event enrollment users"),
 *   type = "event_enrollment",
 *   view_id = "event_manage_enrollments",
 *   display_id = "page_manage_enrollments",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event_managers.vbo.confirm",
 * )
 */
class SocialEventManagersSendEmail extends SocialSendEmail {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
    $allow_text_format
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
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $users = [];
    // Process the event enrollment chunks. These need to be converted to users.
    foreach ($objects as $chunk) {
      $entities = [];
      /** @var \Drupal\social_event\Entity\EventEnrollment $enrollment */
      foreach ($chunk as $enrollment) {
        // Get the user from the even enrollment.
        /** @var \Drupal\user\Entity\User $user */
        $user = User::load($enrollment->getAccount());
        $entities[] = $this->execute($user);
      }

      $users += $this->entityTypeManager->getStorage('user')->loadMultiple($entities);
    }
    // Pass it back to our parent who handles creation of queue items.
    return parent::executeMultiple($users);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowedIf($object instanceof EventEnrollmentInterface);

    if ($object instanceof EventEnrollmentInterface) {
      $access = $object->access('delete', $account, TRUE);

      $event_id = $object->getFieldValue('field_event', 'target_id');
      $node = $this->entityTypeManager->getStorage('node')->load($event_id);

      // Also Event organizers can do this.
      if ($node instanceof NodeInterface && social_event_manager_or_organizer($node)) {
        $access = AccessResult::allowedIf($object instanceof EventEnrollmentInterface);
      }
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
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

}
