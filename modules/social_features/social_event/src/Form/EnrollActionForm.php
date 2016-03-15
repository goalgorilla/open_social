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
   * The node storage.
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
    global $user;

    $nid = $this->routeMatch->getRawParameter('node');

    $form['event'] = array(
      '#type' => 'hidden',
      '#value' => $nid,
    );

    $form['enroll_for_this_event'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Enroll for this event'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    var_dump($form);

    // Let's create some entities.
//    $enrollment = EventEnrollment::create([
//      'langcode' => $eventenrollment['language'],
//      'name' => substr($eventenrollment['title'], 0, 50),
//      'user_id' => $user_id,
//      'created' => REQUEST_TIME,
//      'field_event' => $event_id,
//      'field_enrollment_status' => $eventenrollment['field_enrollment_status'],
//      'field_account' => $user_id
//    ]);
//
//    $enrollment->save();
  }

}
