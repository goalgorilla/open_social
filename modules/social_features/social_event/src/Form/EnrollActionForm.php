<?php

/**
 * @file
 * Contains \Drupal\social_event\Form\EnrollActionForm.
 */

namespace Drupal\social_event\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\social_event\Entity\EventEnrollment;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnrollActionForm.
 *
 * @package Drupal\social_event\Form
 */
class EnrollActionForm extends FormBase implements ContainerInjectionInterface {


  /**
   * The routing matcher to get the nid.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMath;

  /**
   * The node storage for event enrollments.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_action_form';
  }

  function __construct(RouteMatchInterface $route_match, EntityStorageInterface $entity_storage, UserStorageInterface $user_storage) {
    $this->routeMatch = $route_match;
    $this->entityStorage = $entity_storage;
    $this->userStorage = $user_storage;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity.manager')->getStorage('event_enrollment'),
      $container->get('entity.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nid = $this->routeMatch->getRawParameter('node');

    $form['event'] = array(
      '#type' => 'hidden',
      '#value' => $nid,
    );

    $submit_text = $this->t('Enroll for this event');

    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    $conditions = array(
      'field_account' => $uid,
      'field_event' => $nid,
    );

    $to_enroll_status = '1';

    if ($enrollment = array_pop($this->entityStorage->loadByProperties($conditions))) {
      $current_enrollment_status = $enrollment->field_enrollment_status->value;
      if ($current_enrollment_status ==='1') {
        $submit_text = $this->t('Cancel enrollment');
        $to_enroll_status = '0';
      }
    }

    $form['to_enroll_status'] = array(
      '#type' => 'hidden',
      '#value' => $to_enroll_status,
    );


    $form['enroll_for_this_event'] = array(
      '#type' => 'submit',
      '#value' => $submit_text,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    $nid = $form_state->getValue('event');
    $to_enroll_status = $form_state->getValue('to_enroll_status');

    $conditions = array(
      'field_account' => $uid,
      'field_event' => $nid,
    );

    if ($enrollment = array_pop($this->entityStorage->loadByProperties($conditions))) {
      $current_enrollment_status = $enrollment->field_enrollment_status->value;
      if ($to_enroll_status === '0' && $current_enrollment_status ==='1') {
        $enrollment->field_enrollment_status->value = '0';
        $enrollment->save();
      }
      elseif ($to_enroll_status === '1' && $current_enrollment_status ==='0') {
        $enrollment->field_enrollment_status->value = '1';
        $enrollment->save();
      }
    }
    else {
      // Create a new enrollment for the event.
      $enrollment = EventEnrollment::create([
        'user_id' => $uid,
        'field_event' => $nid,
        'field_enrollment_status' => '1',
        'field_account' => $uid
      ]);
      $enrollment->save();
    }
  }

}
