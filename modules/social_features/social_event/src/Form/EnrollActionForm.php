<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\node\Entity\Node;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Service\SocialEventEnrollServiceInterface;
use Drupal\user\UserInterface;
use Drupal\group\Entity\GroupContent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

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
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The event enroll service.
   *
   * @var \Drupal\social_event\Service\SocialEventEnrollServiceInterface
   */
  protected $eventEnrollService;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\social_event\Service\SocialEventEnrollServiceInterface $event_enroll_service
   *   The event enroll service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    AccountProxyInterface $current_user,
    ConfigFactoryInterface $config_factory,
    ModuleHandlerInterface $module_handler,
     SocialEventEnrollServiceInterface $event_enroll_service
  ) {
    $this->routeMatch = $route_match;
    $this->entityStorage = $entity_type_manager->getStorage('event_enrollment');
    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
    $this->eventEnrollService = $event_enroll_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('social_event.enroll')
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
    if (!is_null($nid)) {
      if (!is_object($nid)) {
        $node = $this->entityTypeManager
          ->getStorage('node')
          ->load($nid);
      }

      $groups = $this->getGroups($node);

      // If the user is invited to an event
      // it shouldn't care about group permissions.
      $conditions = [
        'field_account' => $current_user->id(),
        'field_event' => $node->id(),
      ];

      $enrollments = $this->entityStorage->loadByProperties($conditions);

      // Check if groups are not empty, or that the outsiders are able to join.
      if (!empty($groups) && $node->field_event_enroll_outside_group->value !== '1'
        && empty($enrollments)
        && social_event_manager_or_organizer() === FALSE) {

        $group_type_ids = $this->configFactory->getEditable('social_event.settings')
          ->get('enroll');

        foreach ($groups as $group) {
          $group_type_id = $group->bundle();

          // The join group permission has never really been used since
          // this commit. This now means that events in a closed group cannot
          // be joined by outsiders, which makes sense, since they also
          // couldn't see these events in the first place.
          if (in_array($group_type_id, $group_type_ids) && $group->hasPermission('join group', $current_user)) {
            break;
          }

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
    $to_enroll_status = '1';
    $enrollment_open = TRUE;
    $request_to_join = FALSE;
    $isNodeOwner = ($node->getOwnerId() === $uid);

    // Initialise the default attributes for the "Enroll" button
    // if the event enroll method is request to enroll, this will
    // be overwritten because of the modal.
    $attributes = [
      'class' => [
        'btn',
        'btn-accent brand-bg-accent',
        'btn-lg btn-raised',
        'dropdown-toggle',
        'waves-effect',
      ],
    ];

    // Add request to join event.
    if ((int) $node->field_enroll_method->value === EventEnrollmentInterface::ENROLL_METHOD_REQUEST && !$isNodeOwner) {
      $submit_text = $this->t('Request to enroll');
      $to_enroll_status = '2';

      if ($current_user->isAnonymous()) {
        $attributes = [
          'class' => [
            'use-ajax',
            'js-form-submit',
            'form-submit',
            'btn',
            'btn-accent',
            'btn-lg',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => json_encode([
            'title' => t('Request to enroll'),
            'width' => 'auto',
          ]),
        ];

        $request_to_join = TRUE;
      }
    }

    // Add the enrollment closed label.
    if ($this->eventHasBeenFinished($node)) {
      $submit_text = $this->t('Event has passed');
      $enrollment_open = FALSE;
    }

    if (!$current_user->isAnonymous()) {
      $conditions = [
        'field_account' => $uid,
        'field_event' => $nid,
      ];

      $enrollments = $this->entityStorage->loadByProperties($conditions);
      if ($enrollment = array_pop($enrollments)) {
        $current_enrollment_status = $enrollment->field_enrollment_status->value;
        if ($current_enrollment_status === '1') {
          $submit_text = $this->t('Enrolled');
          $to_enroll_status = '0';
        }
        // If someone requested to join the event.
        elseif ($node->field_enroll_method->value && (int) $node->field_enroll_method->value === EventEnrollmentInterface::ENROLL_METHOD_REQUEST && !$isNodeOwner) {
          $event_request_ajax = TRUE;
          if ((int) $enrollment->field_request_or_invite_status->value === EventEnrollmentInterface::REQUEST_PENDING) {
            $submit_text = $this->t('Pending');
            $event_request_ajax = FALSE;
          }
        }
      }

      // Use the ajax submit if the enrollments are empty, or if the
      // user cancelled his enrollment and tries again.
      if ($enrollment_open === TRUE) {
        if (!$isNodeOwner && (empty($enrollment) && $node->field_enroll_method->value && (int) $node->field_enroll_method->value === EventEnrollmentInterface::ENROLL_METHOD_REQUEST)
          || (isset($event_request_ajax) && $event_request_ajax === TRUE)) {
          $attributes = [
            'class' => [
              'use-ajax',
              'js-form-submit',
              'form-submit',
              'btn',
              'btn-accent',
              'btn-lg',
            ],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode([
              'title' => t('Request to enroll'),
              'width' => 'auto',
            ]),
          ];
          $request_to_join = TRUE;
        }
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
      '#attributes' => $attributes,
    ];

    if ($request_to_join === TRUE) {
      $form['enroll_for_this_event'] = [
        '#type' => 'link',
        '#title' => $submit_text,
        '#url' => Url::fromRoute('social_event.request_enroll_dialog', ['node' => $nid]),
        '#attributes' => $attributes,
      ];
    }

    $form['#attributes']['name'] = 'enroll_action_form';

    if ((isset($enrollment->field_enrollment_status->value) && $enrollment->field_enrollment_status->value === '1')
      || (isset($enrollment->field_request_or_invite_status->value)
      && (int) $enrollment->field_request_or_invite_status->value === EventEnrollmentInterface::REQUEST_PENDING)) {
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
    /** @var \Drupal\Core\Datetime\DrupalDateTime $check_end_date */
    $check_end_date = $node->field_event_date->date;

    if (isset($node->field_event_date_end->date)) {
      $check_end_date = $node->field_event_date_end->date;
    }

    $current_time = new DrupalDateTime();

    // The event has finished if the end date is smaller than the current date.
    if ($current_time > $check_end_date) {
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
    $nid = $form_state->getValue('event') ?? $this->routeMatch->getRawParameter('node');
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    // Redirect anonymous use to login page before enrolling to an event.
    if ($current_user->isAnonymous()) {
      $node_url = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
      $destination = $node_url;
      // If the request enroll method is set, alter the destination for AN.
      if ((int) $node->get('field_enroll_method')->value === EventEnrollmentInterface::ENROLL_METHOD_REQUEST) {
        $destination = $node_url . '?requested-enrollment=TRUE';
      }
      $form_state->setRedirect('user.login', [], ['query' => ['destination' => $destination]]);

      // Check if user can register accounts.
      if ($this->configFactory->get('user.settings')->get('register') !== UserInterface::REGISTER_ADMINISTRATORS_ONLY) {
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

      $this->messenger()->addStatus($message);
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
      // The user is enrolled, but cancels his enrollment.
      if ($to_enroll_status === '0' && $current_enrollment_status === '1') {
        // The user is enrolled by invited or request, but either the user or
        // event manager is declining or invalidating the enrollment.
        if ($enrollment->field_request_or_invite_status
          && (int) $enrollment->field_request_or_invite_status->value === EventEnrollmentInterface::INVITE_ACCEPTED_AND_JOINED) {
          // Mark this user his enrollment as declined.
          $enrollment->field_request_or_invite_status->value = EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED;
          // If the user is cancelling, un-enroll.
          $current_enrollment_status = $enrollment->field_enrollment_status->value;
          if ($current_enrollment_status === '1') {
            $enrollment->field_enrollment_status->value = '0';
          }
          $enrollment->save();
        }
        // Else, the user simply wants to cancel his enrollment, so at
        // this point we can safely delete the enrollment record as well.
        else {
          $enrollment->delete();
        }
      }
      elseif ($to_enroll_status === '1' && $current_enrollment_status === '0') {
        $enrollment->field_enrollment_status->value = '1';
        $enrollment->save();
      }
      elseif ($to_enroll_status === '2' && $current_enrollment_status === '0') {
        if ((int) $enrollment->field_request_or_invite_status->value === EventEnrollmentInterface::REQUEST_PENDING) {
          $enrollment->delete();
        }
      }

    }
    else {
      // Default event enrollment field set.
      $fields = [
        'user_id' => $uid,
        'field_event' => $nid,
        'field_enrollment_status' => '1',
        'field_account' => $uid,
      ];

      // If request to join is on, alter fields.
      if ($to_enroll_status === '2') {
        $fields['field_enrollment_status'] = '0';
        $fields['field_request_or_invite_status'] = EventEnrollmentInterface::REQUEST_PENDING;
      }

      // Create a new enrollment for the event.
      $enrollment = EventEnrollment::create($fields);
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
        /** @var \Drupal\group\Entity\GroupContent $groupcontent */
        $group = $groupcontent->getGroup();
        /** @var \Drupal\group\Entity\Group $group */
        $groups[] = $group;
      }
    }

    return $groups;
  }

}
