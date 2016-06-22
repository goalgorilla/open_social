<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\user\UserStorageInterface;
use Drupal\group\Entity\GroupContent;
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

  /**
   * Constructs an Enroll Action Form.
   */
  public function __construct(RouteMatchInterface $route_match, EntityStorageInterface $entity_storage, UserStorageInterface $user_storage) {
    $this->routeMatch = $route_match;
    $this->entityStorage = $entity_storage;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
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
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    // We check if the node is placed in a Group I am a member of. If not,
    // we are not going to build anything.
    if (!empty($nid)) {
      if (!is_object($nid) && !is_null($nid)) {
        $node = \Drupal::service('entity_type.manager')
          ->getStorage('node')
          ->load($nid);
      }

      $groups = $this->getGroups($node);
      if (!empty($groups)) {
        foreach ($groups as $group) {
          if ($group->hasPermission('enroll to events in groups', $current_user) == FALSE) {
            return '';
          }
        }
      }
    }

    $form['event'] = array(
      '#type' => 'hidden',
      '#value' => $nid,
    );

    $submit_text = $this->t('Enroll');

    $conditions = array(
      'field_account' => $uid,
      'field_event' => $nid,
    );

    $to_enroll_status = '1';

    $enrollments = $this->entityStorage->loadByProperties($conditions);

    if ($enrollment = array_pop($enrollments)) {
      $current_enrollment_status = $enrollment->field_enrollment_status->value;
      if ($current_enrollment_status === '1') {
        $submit_text = $this->t('Enrolled');

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

    $form['#attributes']['name'] = 'enroll_action_form';

    if (isset($enrollment->field_enrollment_status->value) && $enrollment->field_enrollment_status->value === '1') {
      // Extra attributes needed for when a user is logged in. This will make
      // sure the button acts like a dropwdown.
      $form['enroll_for_this_event']['#attributes'] = array(
        'class' => array(
          'btn',
          'btn-accent',
          'btn-lg btn-raised',
          'dropdown-toggle',
        ),
        'autocomplete' => 'off',
        'data-toggle' => 'dropdown',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
        'data-caret' => 'true',
      );

      $cancel_text = $this->t('Cancel enrollment');

      // Add markup for the button so it will be a dropdown.
      $form['feedback_user_has_enrolled'] = array(
        '#markup' => '<ul class="dropdown-menu"><li><a href="#" class="enroll-form-submit"> ' . $cancel_text . ' </a></li></ul>',
      );

      $form['#attached']['library'][] = 'social_event/form_submit';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = \Drupal::currentUser();
    $uid = $current_user->id();

    $nid = $form_state->getValue('event');

    // Redirect anonymous use to login page before enrolling to an event.
    if ($uid === 0) {
      $node_url = Url::fromRoute('entity.node.canonical', ['node' => $nid])->getInternalPath();
      $form_state->setRedirect('user.login',
        array(),
        array(
          'query' => array(
            'destination' => $node_url,
          ),
        )
        );
      drupal_set_message('Please log in or create a new account so that you can enroll to the event');
      return;
    }

    $to_enroll_status = $form_state->getValue('to_enroll_status');

    $conditions = array(
      'field_account' => $uid,
      'field_event' => $nid,
    );

    $enrollments = $this->entityStorage->loadByProperties($conditions);

    // Invalidate cache for our enrollment cache tag in
    // social_event_node_view_alter().
    $cache_tag = 'enrollment:' . $nid . '-' . $uid;
    Cache::invalidateTags(array($cache_tag));

    if ($enrollment = array_pop($enrollments)) {
      $current_enrollment_status = $enrollment->field_enrollment_status->value;
      if ($to_enroll_status === '0' && $current_enrollment_status === '1') {
        $enrollment->field_enrollment_status->value = '0';
        $enrollment->save();
      }
      elseif ($to_enroll_status === '1' && $current_enrollment_status === '0') {
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
        'field_account' => $uid,
      ]);
      $enrollment->save();
    }
  }

  /**
   * Get group object where event enrollment is posted in.
   *
   * Returns an array of Group Objects.
   *
   * @return array
   *   Array of group entities.
   */
  public function getGroups($node) {

    $groupcontents = GroupContent::loadByEntity($node);

    $groups = [];
    // Only react if it is actually posted inside a group.
    if (!empty($groupcontents)) {
      foreach ($groupcontents as $groupcontent) {
        /* @var \Drupal\group\Entity\GroupContent $groupcontent */
        $group = $groupcontent->getGroup();
        /* @var \Drupal\group\Entity\Group $group*/
        $groups[] = $group;
      }
    }

    return $groups;
  }

}
