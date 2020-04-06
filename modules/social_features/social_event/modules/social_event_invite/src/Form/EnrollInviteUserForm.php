<?php

namespace Drupal\social_event_invite\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\social_core\Form\InviteUserBaseForm;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_invite\SocialEventInviteStatusHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnrollInviteForm.
 */
class EnrollInviteUserForm extends InviteUserBaseForm {


  /**
   * The event invite status helper.
   *
   * @var \Drupal\social_event_invite\SocialEventInviteStatusHelper
   */
  protected $eventInviteStatus;

  /**
   * {@inheritDoc}
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, SocialEventInviteStatusHelper $eventInviteStatus) {
    parent::__construct($route_match, $entity_type_manager, $logger_factory);
    $this->eventInviteStatus = $eventInviteStatus;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('social_event_invite.status_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_invite_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $nid = $this->routeMatch->getRawParameter('node');

    $form['event'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    $form['name'] = [
      '#type' => 'social_enrollment_entity_autocomplete',
      '#selection_handler' => 'social',
      '#selection_settings' => [],
      '#target_type' => 'user',
      '#tags' => TRUE,
      '#description' => $this->t('To add multiple members, separate each member with a comma ( , ).'),
      '#title' => $this->t('Select members to add'),
      '#weight' => -1,
    ];

    $form['actions']['submit_cancel'] = [
      '#type' => 'submit',
      '#weight' => 999,
      '#value' => $this->t('Back to events'),
      '#submit' => [[$this, 'cancelForm']],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * Cancel form taking you back to an event.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('view.event_manage_enrollments.page_manage_enrollments', [
      'node' => $this->routeMatch->getRawParameter('node'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $users = $form_state->getValue('entity_id_new');
    $nid = $form_state->getValue('event');

    foreach ($users as $uid => $target_id) {
      // Default values.
      $fields = [
        'field_event' => $nid,
        'field_enrollment_status' => '0',
        'field_request_or_invite_status' => EventEnrollmentInterface::INVITE_PENDING_REPLY,
        'user_id' => $uid,
        'field_account' => $uid,
      ];

      // Check if this user has been invited before. It might be that the user
      // declined the invite, or that the invite is now invalid and expired.
      // We simply delete the outdated invite and create a new one.
      $existing_enrollment = $this->eventInviteStatus->getEventEnrollments($uid, $nid, TRUE);
      if (!empty($existing_enrollment)) {
        /** @var EventEnrollment $enrollment */
        $enrollment = end($existing_enrollment);
        // Of course, only delete the previous invite if it was declined
        // or if it was invalid or expired.
        $status_checks = [
          EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED,
          EventEnrollmentInterface::INVITE_INVALID_OR_EXPIRED,
        ];
        if (in_array($enrollment->field_request_or_invite_status->value, $status_checks)) {
          $enrollment->delete();
        }
      }

      // Clear the cache.
      $tags = [];
      $tags[] = 'enrollment:' . $nid . '-' . $uid;
      $tags[] = 'event_content_list:entity:' . $uid;
      Cache::invalidateTags($tags);

      // Create a new enrollment for the event.
      $enrollment = EventEnrollment::create($fields);
      // In order for the notifications to be sent correctly we're updating the
      // owner here. The account is still linked to the actual enrollee.
      // The owner is always used as the actor.
      // @see activity_creator_message_insert().
      $enrollment->setOwnerId(\Drupal::currentUser()->id());
      $enrollment->save();
    }
  }

}
