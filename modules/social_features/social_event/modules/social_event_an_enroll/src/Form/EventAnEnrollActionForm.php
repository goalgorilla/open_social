<?php

namespace Drupal\social_event_an_enroll\Form;

use Drupal\social_event\Form\EnrollActionForm;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Class EventAnEnrollActionForm.
 *
 * @package Drupal\social_event_an_enroll\Form
 */
class EventAnEnrollActionForm extends EnrollActionForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_an_enroll_cancel_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $node = NULL) {
    $nid = $node->id();
    $token = $this->getRequest()->query->get('token');

    $btn_classes = [
      'btn',
      'btn-accent',
      'btn-lg',
      'btn-raised',
      'brand-bg-accent',
      'dropdown-toggle',
      'waves-effect',
    ];

    if (!empty($token) && social_event_an_enroll_token_exists($token, $nid)) {
      $form['event'] = [
        '#type' => 'hidden',
        '#value' => $nid,
      ];

      $form['enroll_for_this_event'] = [
        '#type' => 'submit',
        '#value' => $this->t('Enrolled'),
        '#attributes' => [
          'class' => array_merge($btn_classes, ['dropdown-toggle']),
          'autocomplete' => 'off',
          'data-toggle' => 'dropdown',
          'aria-haspopup' => 'true',
          'aria-expanded' => 'false',
          'data-caret' => 'true',
        ],
      ];

      $cancel_text = $this->t('Cancel enrollment');
      $form['feedback_user_has_enrolled'] = [
        '#markup' => '<ul class="dropdown-menu dropdown-menu-right"><li><a href="#" class="enroll-form-submit"> ' . $cancel_text . ' </a></li></ul>',
      ];
      $form['#attached']['library'][] = 'social_event/form_submit';
    }
    else {
      if ($this->eventHasBeenFinished($node)) {
        $form['event_enrollment'] = [
          '#type' => 'submit',
          '#value' => $this->t('Event has passed'),
          '#disabled' => TRUE,
          '#attributes' => [
            'class' => $btn_classes,
          ],
        ];
      }
      else {
        // Take into account max enrollments.
        if ($this->moduleHandler->moduleExists('social_event_max_enroll')) {
          // We can't use dependency injection, because service is optional.
          $event_max_enroll_service = \Drupal::service('social_event_max_enroll.service');
          if ($event_max_enroll_service->isEnabled($node)) {
            // Count how many places left.
            $left = $this->eventMaxEnrollService->getEnrollmentsLeft($node);
            if ($left < 1) {
              $form['event_enrollment'] = [
                '#type' => 'submit',
                '#value' => $this->t('No places left'),
                '#disabled' => TRUE,
                '#attributes' => [
                  'class' => $btn_classes,
                ],
              ];
            }
          }
        }

        if (empty($form['event_enrollment'])) {
          $form['event_enrollment'] = [
            '#type' => 'link',
            '#title' => $this->t('Enroll'),
            '#url' => Url::fromRoute('social_event_an_enroll.enroll_dialog', ['node' => $nid]),
            '#attributes' => [
              'class' => array_merge($btn_classes, [
                'use-ajax',
                'js-form-submit',
                'form-submit',
              ]),
              'data-dialog-type' => 'modal',
              'data-dialog-options' => json_encode([
                'title' => $this->t('Enroll in @event Event', ['@event' => $node->getTitle()]),
                'width' => 'auto',
              ]),
            ],
          ];
        }

      }
    }
    $form['#cache'] = ['max-age' => 0];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $this->currentUser->id();

    $token = $this->getRequest()->query->get('token');
    if (!empty($token)) {
      $nid = $form_state->getValue('event');
      $conditions = [
        'field_account' => $uid,
        'field_event' => $nid,
        'field_token' => $token,
      ];

      $enrollments = $this->entityStorage->loadByProperties($conditions);

      // Invalidate cache for our enrollment cache tag in
      // social_event_node_view_alter().
      $cache_tag = 'enrollment:' . $nid . '-' . $uid;
      Cache::invalidateTags([$cache_tag]);

      if ($enrollment = array_pop($enrollments)) {
        $enrollment->delete();
        drupal_set_message($this->t('You are no longer enrolled in this event. Your personal data used for the enrollment is also deleted.'));
      }
    }
  }

}
