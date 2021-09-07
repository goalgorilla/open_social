<?php

namespace Drupal\social_event_invite\Form;

use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Form\EnrollActionForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Cache\Cache;

/**
 * Class EventInviteEnrollActionForm.
 *
 * @package Drupal\social_event_invite\Form
 */
class EventInviteEnrollActionForm extends EnrollActionForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_invite_enroll_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL) {
    $form = parent::buildForm($form, $form_state);
    $nid = $this->routeMatch->getRawParameter('node');
    $current_user = $this->currentUser;
    $uid = $current_user->id();

    if (!$current_user->isAnonymous()) {
      $conditions = [
        'field_account' => $uid,
        'field_event' => $nid,
      ];
      $enrollments = $this->entityStorage->loadByProperties($conditions);

      // If the event is invite only and you have not been invited, return.
      // Unless you are the node owner or organizer.
      if (empty($enrollments)) {
        if ((int) $node->field_enroll_method->value === EventEnrollmentInterface::ENROLL_METHOD_INVITE
          && social_event_manager_or_organizer() === FALSE) {
          return [];
        }
      }
      elseif ($enrollment = array_pop($enrollments)) {
        $enroll_request_status = $enrollment->field_request_or_invite_status->value;

        // If user got invited perform actions.
        if ($enroll_request_status == '4') {

          $submit_text = $this->t('Accept');

          $form['enroll_for_this_event'] = [
            '#type' => 'submit',
            '#value' => $submit_text,
            '#name' => 'accept_invite',
          ];

          // Extra attributes needed for when a user is logged in.
          // This will make sure the button acts like a dropdown.
          $form['enroll_for_this_event']['#attributes'] = [
            'class' => [
              'btn',
              'btn-accent brand-bg-accent',
              'btn-lg btn-raised',
              'dropdown-toggle',
              'waves-effect',
            ],
          ];

          // We need a hidden element for later usage.
          $form['event_id'] = [
            '#type' => 'hidden',
            '#value' => $this->routeMatch->getRawParameter('node'),
          ];

          $form['decline_invite'] = [
            '#type' => 'submit',
            '#value' => '',
            '#name' => 'decline_invite',
          ];

          // Extra attributes needed for when a user is logged in.
          // This will make sure the button acts like a dropdown.
          $form['decline_invite']['#attributes'] = [
            'class' => [
              'btn',
              'btn-accent brand-bg-accent',
              'btn-lg btn-raised',
              'dropdown-toggle',
              'waves-effect',
              'margin-left-s',
            ],
            'autocomplete' => 'off',
            'data-toggle' => 'dropdown',
            'aria-haspopup' => 'true',
            'aria-expanded' => 'false',
            'data-caret' => 'true',
          ];

          $decline_text = $this->t('Decline');

          // Add markup for the button so it will be a dropdown.
          $form['decline_invite_dropdown'] = [
            '#markup' => '<ul class="dropdown-menu dropdown-menu-right"><li><a href="#" class="enroll-form-submit"> ' . $decline_text . ' </a></li></ul>',
          ];

          // Add a hidden operation we can fill with jquery when declining.
          $form['operation'] = [
            '#type' => 'hidden',
            '#default_value' => '',
          ];

          $form['#attached']['library'][] = 'social_event/form_submit';

        }
      }
    }

    // For AN users it can be rendered on a Public event with
    // invite only as option. Let's make it similar to a Group experience
    // where there is no button rendered.
    // We unset it here because in the parent form and this form
    // a lot of times this button get's overridden.
    if ($current_user->isAnonymous()) {
      unset($form['enroll_for_this_event']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $operation = $form_state->getValue('operation');
    $current_user = $this->currentUser;
    $uid = $current_user->id();
    $nid = $form_state->getValue('event') ?? $this->routeMatch->getRawParameter('node');

    $conditions = [
      'field_account' => $uid,
      'field_event' => $nid,
    ];

    $enrollments = $this->entityStorage->loadByProperties($conditions);

    // @todo also clear the breadcrumb cachetags.
    // Invalidate cache for our enrollment cache tag in
    // social_event_node_view_alter().
    $tags = [];
    $tags[] = 'enrollment:' . $nid . '-' . $uid;
    $tags[] = 'event_content_list:entity:' . $uid;
    Cache::invalidateTags($tags);

    if ($enrollment = array_pop($enrollments)) {
      // Only trigger when the user is invited.
      if ($enrollment->field_request_or_invite_status
        && (int) $enrollment->field_request_or_invite_status->value === EventEnrollmentInterface::INVITE_PENDING_REPLY) {
        // Delete any messages since it would show a 'successful enrollment'.
        $this->messenger()->deleteAll();
        // Accept the invite.
        $enrollment->field_enrollment_status->value = '1';
        $enrollment->field_request_or_invite_status->value = EventEnrollmentInterface::INVITE_ACCEPTED_AND_JOINED;

        // If decline is chosen, set invite to declined.
        if ($operation === 'decline') {
          // Delete any messages since it would show a 'successful enrollment'.
          $this->messenger()->deleteAll();
          $enrollment->field_enrollment_status->value = '0';
          $enrollment->field_request_or_invite_status->value = EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED;
        }
        $enrollment->save();
      }
    }
  }

}
