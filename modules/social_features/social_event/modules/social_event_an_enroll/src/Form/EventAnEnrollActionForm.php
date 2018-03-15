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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nid = $this->routeMatch->getRawParameter('node');
    $token = \Drupal::request()->query->get('token');

    // Load node object.
    if (!is_null($nid) && !is_object($nid)) {
      $node = Node::load($nid);
    }

    if (!empty($token) && social_event_an_enroll_token_exists($token, $nid)) {
      $form = parent::buildForm($form, $form_state);
    }
    else {
      if ($this->eventHasBeenFinished($node)) {
        $form['event_enrollment'] = [
          '#type' => 'submit',
          '#value' => $this->t('Event has passed'),
          '#disabled' => TRUE,
          '#attributes' => [
            'class' => [
              'btn',
              'btn-accent brand-bg-accent',
              'btn-lg btn-raised',
              'waves-effect',
            ],
          ],
        ];
      }
      else {
        $attributes = [
          'class' => [
            'use-ajax',
            'button',
            'button--accent',
            'js-form-submit',
            'form-submit',
            'btn-lg',
            'btn',
            'js-form-submit',
            'btn-accent',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode([
            'title' => t('Enroll in @event Event', ['@event' => $node->getTitle()]),
            'width' => 'auto',
          ]),
        ];

        $form['event_enrollment'] = [
          '#type' => 'link',
          '#title' => $this->t('Enroll'),
          '#url' => Url::fromRoute('social_event_an_enroll.enroll_dialog', ['node' => $nid]),
          '#attributes' => $attributes,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = $this->currentUser;
    $uid = $current_user->id();

    $token = \Drupal::request()->query->get('token');
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
      }
    }
  }

}
