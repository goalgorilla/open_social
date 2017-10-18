<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
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
  protected $routeMatch;

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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_action_form';
  }

  /**
   * Constructs an Enroll Action Form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(RouteMatchInterface $route_match, EntityStorageInterface $entity_storage, UserStorageInterface $user_storage, EntityTypeManagerInterface $entityTypeManager, AccountProxyInterface $currentUser, ConfigFactoryInterface $configFactory) {
    $this->routeMatch = $route_match;
    $this->entityStorage = $entity_storage;
    $this->userStorage = $user_storage;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity.manager')->getStorage('event_enrollment'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $nid = $this->routeMatch->getRawParameter('node');
    $current_user = $this->currentUser;
    $uid = $current_user->id();

    // We check if the node is placed in a Group I am a member of. If not,
    // we are not going to build anything.
    if (!empty($nid)) {
      if (!is_object($nid) && !is_null($nid)) {
        $node = $this->entityTypeManager
          ->getStorage('node')
          ->load($nid);
      }

      $groups = $this->getGroups($node);
      if (!empty($groups)) {
        foreach ($groups as $group) {
          if ($group->hasPermission('enroll to events in groups', $current_user) == FALSE) {
            return [];
          }
        }
      }
    }

    $form['event'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    $submit_text = $this->t('Enroll');
    $enrollment_open = TRUE;

    // Add the enrollment closed label.
    if ($this->eventHasBeenFinished($node)) {
      $submit_text = $this->t('Event has passed');
      $enrollment_open = FALSE;
    }

    $conditions = [
      'field_account' => $uid,
      'field_event' => $nid,
    ];

    $to_enroll_status = '1';

    $enrollments = $this->entityStorage->loadByProperties($conditions);

    if ($enrollment = array_pop($enrollments)) {
      $current_enrollment_status = $enrollment->field_enrollment_status->value;
      if ($current_enrollment_status === '1') {
        $submit_text = $this->t('Enrolled');
        $to_enroll_status = '0';
      }
    }

    $form['to_enroll_status'] = [
      '#type' => 'hidden',
      '#value' => $to_enroll_status,
    ];

    $form['enroll_for_this_event'] = [
      '#type' => 'submit',
      '#value' => $submit_text,
      '#disabled' => !$enrollment_open,
    ];

    $form['#attributes']['name'] = 'enroll_action_form';

    if (isset($enrollment->field_enrollment_status->value) && $enrollment->field_enrollment_status->value === '1') {
      // Extra attributes needed for when a user is logged in. This will make
      // sure the button acts like a dropwdown.
      $form['enroll_for_this_event']['#attributes'] = [
        'class' => [
          'btn',
          'btn-accent brand-bg-accent',
          'btn-lg btn-raised',
          'dropdown-toggle',
          'waves-effect',
        ],
        'autocomplete' => 'off',
        'data-toggle' => 'dropdown',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
        'data-caret' => 'true',
      ];

      $cancel_text = $this->t('Cancel enrollment');

      // Add markup for the button so it will be a dropdown.
      $form['feedback_user_has_enrolled'] = [
        '#markup' => '<ul class="dropdown-menu dropdown-menu-right"><li><a href="#" class="enroll-form-submit"> ' . $cancel_text . ' </a></li></ul>',
      ];

      $form['#attached']['library'][] = 'social_event/form_submit';
    }

    return $form;
  }

  /**
   * Function to determine if an event has been finished.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The event.
   *
   * @return bool
   *   TRUE if the evens is finished / completed.
   */
  protected function eventHasBeenFinished(Node $node) {
    // Use the start date when the end date is not set to determine if the
    // event is closed.
    $check_end_date = $node->field_event_date->value;

    if (isset($node->field_event_date_end->value)) {
      $check_end_date = $node->field_event_date_end->value;
    }
    // Get Event end date to compare w/ current timestamp.
    $event_end_timestamp = strtotime($check_end_date);

    // Check to see if Event end date is in the future,
    // hence we can still "Enroll".
    if (time() > $event_end_timestamp) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $current_user = $this->currentUser;
    $uid = $current_user->id();

    $nid = $form_state->getValue('event');

    // Redirect anonymous use to login page before enrolling to an event.
    if ($uid === 0) {
      $node_url = Url::fromRoute('entity.node.canonical', ['node' => $nid])
        ->toString();
      $form_state->setRedirect('user.login',
        [],
        [
          'query' => [
            'destination' => $node_url,
          ],
        ]
      );

      // Check if user can register accounts.
      if ($this->configFactory->get('user.settings')->get('register') != USER_REGISTER_ADMINISTRATORS_ONLY) {
        $log_in_url = Url::fromUserInput('/user/login');
        $log_in_link = Link::fromTextAndUrl($this->t('log in'), $log_in_url)->toString();
        $create_account_url = Url::fromUserInput('/user/register');
        $create_account_link = Link::fromTextAndUrl($this->t('create a new account'), $create_account_url)->toString();
        $message = $this->t('Please @log_in or @create_account_link so that you can enroll to the event.', [
          '@log_in' => $log_in_link,
          '@create_account_link' => $create_account_link,
        ]);
      }
      else {
        $log_in_url = Url::fromUserInput('/user/login');
        $log_in_link = Link::fromTextAndUrl($this->t('log in'), $log_in_url)->toString();
        $message = $this->t('Please @log_in so that you can enroll to the event.', [
          '@log_in' => $log_in_link,
        ]);
      }

      drupal_set_message($message);
      return;
    }

    $to_enroll_status = $form_state->getValue('to_enroll_status');

    $conditions = [
      'field_account' => $uid,
      'field_event' => $nid,
    ];

    $enrollments = $this->entityStorage->loadByProperties($conditions);

    // Invalidate cache for our enrollment cache tag in
    // social_event_node_view_alter().
    $cache_tag = 'enrollment:' . $nid . '-' . $uid;
    Cache::invalidateTags([$cache_tag]);

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
        /* @var \Drupal\group\Entity\Group $group */
        $groups[] = $group;
      }
    }

    return $groups;
  }

}
