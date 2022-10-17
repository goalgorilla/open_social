<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupContent;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\SocialEventTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnrollActionForm.
 *
 * @package Drupal\social_event\Form
 */
class EnrollActionForm extends FormBase {

  use SocialEventTrait;

  /**
   * The routing matcher to get the nid.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The node storage for event enrollments.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $enrollmentStorage;

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
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The event invite status helper.
   *
   * @var \Drupal\social_event\EventEnrollmentStatusHelper
   */
  protected $eventHelper;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_action_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->routeMatch = $container->get('current_route_match');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->enrollmentStorage = $container->get('entity_type.manager')->getStorage('event_enrollment');
    $instance->userStorage = $container->get('entity_type.manager')->getStorage('user');
    $instance->configFactory = $container->get('config.factory');
    $instance->moduleHandler = $container->get('module_handler');
    $instance->eventEnrollService = $container->get('social_event.enroll');
    $instance->formBuilder = $container->get('form_builder');
    $instance->eventHelper = $container->get('social_event.status_helper');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $node = $this->routeMatch->getParameter('node');
    $current_user = $this->currentUser();

    // It's possible this form is rendered through a route that doesn't properly
    // convert the parameter to a node. So in that case we must do so manually.
    if (is_numeric($node)) {
      $node = $this->entityTypeManager
        ->getStorage('node')
        ->load($node);
    }

    // This entire function collapses if we don't have a node so we just short-
    // circuit early.
    if (!$node instanceof NodeInterface) {
      return [];
    }

    // We check if the node is placed in a Group I am a member of. If not,
    // we are not going to build anything.
    $groups = $this->getGroups($node);

    // If the user is invited to an event
    // it shouldn't care about group permissions.
    $enrollments = $this->enrollmentStorage->loadByProperties([
      'field_account' => $current_user->id(),
      'field_event' => $node->id(),
    ]);

    // Check if groups are not empty, or that the outsiders are able to join.
    if (
      !empty($groups) &&
      $node->field_event_enroll_outside_group->value !== '1'
      && empty($enrollments)
      && !social_event_manager_or_organizer()
    ) {

      // Default prediction is that user does not have permissions to enroll
      // to event and this is why $enroll_to_events_in_groups is set to FALSE.
      // If user has permission 'enroll to events in groups' in at least one
      // group in "$groups", this value will be changed to TRUE.
      $enroll_to_events_in_groups = FALSE;

      $group_type_ids = $this->configFactory->get('social_event.settings')
        ->get('enroll');

      /** @var \Drupal\group\Entity\GroupInterface $group */
      foreach ($groups as $group) {
        // The join group permission has never really been used since
        // this commit. This now means that events in a closed group cannot
        // be joined by outsiders, which makes sense, since they also
        // couldn't see these events in the first place.
        if (in_array($group->bundle(), $group_type_ids, TRUE) && $group->hasPermission('join group', $current_user)) {
          $enroll_to_events_in_groups = TRUE;
          break;
        }

        if ($group->hasPermission('enroll to events in groups', $current_user)) {
          $enroll_to_events_in_groups = TRUE;

          // Skip permission validation if 'enroll to events in groups' is
          // already granted.
          break;
        }
      }

      // Do not render form if user does not have permission
      // 'enroll to events in groups' for at least one group in "$groups".
      if (!$enroll_to_events_in_groups) {
        return [];
      }
    }

    $form['event'] = [
      '#type' => 'hidden',
      '#value' => $node->id(),
    ];

    $submit_text = $this->t('Enroll');
    $to_enroll_status = '1';
    $enrollment_open = TRUE;
    $request_to_join = FALSE;
    $isNodeOwner = $node->getOwnerId() === $current_user->id();
    $enroll_method = $node->get('field_enroll_method')->getString();

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
    if ((int) $enroll_method === EventEnrollmentInterface::ENROLL_METHOD_REQUEST && !$isNodeOwner) {
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
      if ($enrollment = array_pop($enrollments)) {
        $current_enrollment_status = $enrollment->field_enrollment_status->value;
        if ($current_enrollment_status === '1') {
          $submit_text = $this->t('Enrolled');
          $to_enroll_status = '0';
        }
        // If someone requested to join the event.
        elseif (
          (int) $enroll_method === EventEnrollmentInterface::ENROLL_METHOD_REQUEST &&
          !$isNodeOwner
        ) {
          $event_request_ajax = TRUE;
          if ((int) $enrollment->field_request_or_invite_status->value === EventEnrollmentInterface::REQUEST_PENDING) {
            $submit_text = $this->t('Pending');
            $event_request_ajax = FALSE;
          }
        }
      }

      // Use the ajax submit if the enrollments are empty, or if the
      // user cancelled their enrollment and tries again.
      if ($enrollment_open) {
        if (
          !$isNodeOwner &&
          (empty($enrollment) && (int) $enroll_method === EventEnrollmentInterface::ENROLL_METHOD_REQUEST) ||
          (isset($event_request_ajax) && $event_request_ajax)
        ) {
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

    $form['enroll_wrapper'] = [
      '#type' => 'container',
      '#id' => 'enroll-wrapper',
    ];

    // Form submit.
    $submit_button = [
      '#type' => 'submit',
      '#value' => $submit_text,
      '#disabled' => !$enrollment_open,
      '#ajax' => [
        'callback' => [$form_state->getFormObject(), 'ajaxSubmitEnrollForm'],
        'disable-refocus' => TRUE,
        'progress' => 'none',
      ],
    ];

    if ($request_to_join === TRUE) {
      $form['enroll_wrapper']['enroll_for_this_event'] = [
        '#type' => 'link',
        '#title' => $submit_text,
        '#url' => Url::fromRoute('social_event.request_enroll_dialog', ['node' => $node->id()]),
        '#attributes' => $attributes,
      ];
    }
    else {
      $form['enroll_wrapper']['enroll_for_this_event'] = $submit_button + [
        '#attributes' => $attributes,
      ];
    }

    $form['#attributes']['name'] = 'enroll_action_form';

    if ((isset($enrollment->field_enrollment_status->value) && $enrollment->field_enrollment_status->value === '1')
      || (isset($enrollment->field_request_or_invite_status->value)
      && (int) $enrollment->field_request_or_invite_status->value === EventEnrollmentInterface::REQUEST_PENDING)) {
      // Extra attributes needed for when a user is logged in. This will make
      // sure the button acts like a dropwdown.
      $form['enroll_wrapper']['enroll_for_this_event'] = [
        '#type' => 'button',
        '#value' => $submit_text,
        '#attributes' => [
          'class' => [
            'btn',
            'btn-accent',
            'btn-lg',
            'btn-raised',
            'brand-bg-accent',
            'dropdown-toggle',
            'waves-effect',
          ],
          'autocomplete' => 'off',
          'data-toggle' => 'dropdown',
          'aria-haspopup' => 'true',
          'aria-expanded' => 'false',
          'data-caret' => 'true',
        ],
      ];

      $cancel_text = $this->t('Cancel enrollment');

      // Add markup for the button so it will be a dropdown.
      $form['enroll_wrapper']['feedback_user_has_enrolled'] = $submit_button + [
        '#prefix' => '<ul class="dropdown-menu dropdown-menu-right"><li>',
        '#suffix' => '</li></ul>',
      ];
      $form['enroll_wrapper']['feedback_user_has_enrolled']['#value'] = $cancel_text;
      $form['enroll_wrapper']['feedback_user_has_enrolled']['#attributes']['class'][] = 'btn-link';

      $form['#prefix'] = '<div id="enroll-form-wrapper">';
      $form['#suffix'] = '</div>';
    }

    return $form;
  }

  /**
   * Rebuilds form after ajax submit.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function ajaxSubmitEnrollForm(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $nid = $form_state->getValue('event') ?? $this->routeMatch->getRawParameter('node');
    $current_user = $this->currentUser();

    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->entityTypeManager->getStorage('node')->load($nid);
    // Redirect anonymous use to login page before enrolling to an event.
    if ($current_user->isAnonymous()) {
      $node_url = Url::fromRoute('entity.node.canonical', ['node' => $nid])->toString();
      $destination = $node_url;
      // If the request enroll method is set, alter the destination for AN.
      if ((int) $node->get('field_enroll_method')->value === EventEnrollmentInterface::ENROLL_METHOD_REQUEST) {
        $destination = $node_url . '?requested-enrollment=TRUE';
      }

      $redirect_link = Url::fromRoute('user.login', [], ['query' => ['destination' => $destination]])->toString();

      $log_in_url = Url::fromUserInput('/user/login');
      $log_in_link = Link::fromTextAndUrl($this->t('log in'), $log_in_url)->toString();
      $message = $this->t('Please @log_in so that you can enroll to the event.', [
        '@log_in' => $log_in_link,
      ]);

      // Check if user can register accounts.
      if ($this->configFactory->get('user.settings')->get('register')) {
        $create_account_url = Url::fromUserInput('/user/register');
        $create_account_link = Link::fromTextAndUrl($this->t('create a new account'), $create_account_url)->toString();
        $message = $this->t('Please @log_in or @create_account_link so that you can enroll to the event.', [
          '@log_in' => $log_in_link,
          '@create_account_link' => $create_account_link,
        ]);
      }

      $this->messenger()->addStatus($message);
      $response->addCommand(new RedirectCommand($redirect_link));
      return $response;
    }

    $to_enroll_status = $form_state->getValue('to_enroll_status');

    /** @var \Drupal\social_event\EventEnrollmentInterface[] $enrollments */
    $enrollments = $this->enrollmentStorage->loadByProperties([
      'field_account' => $current_user->id(),
      'field_event' => $nid,
    ]);

    // Invalidate cache for our enrollment cache tag in
    // social_event_node_view_alter().
    $cache_tags[] = 'enrollment:' . $nid . '-' . $current_user->id();
    $cache_tags[] = 'node:' . $nid;
    Cache::invalidateTags($cache_tags);

    if ($enrollment = array_pop($enrollments)) {
      $current_enrollment_status = $enrollment->field_enrollment_status->value;
      // The user is enrolled, but cancels their enrollment.
      if ($to_enroll_status === '0' && $current_enrollment_status === '1') {
        // The user is enrolled by invited or request, but either the user or
        // event manager is declining or invalidating the enrollment.
        $request_or_invite = $enrollment->get('field_request_or_invite_status');
        if ($request_or_invite->isEmpty()
          && (int) $request_or_invite->getString() === EventEnrollmentInterface::INVITE_ACCEPTED_AND_JOINED) {
          // Mark this user's enrollment as declined.
          $enrollment->set('field_request_or_invite_status', EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED);
          // If the user is cancelling, un-enroll.
          $current_enrollment_status = $enrollment->field_enrollment_status->value;
          if ($current_enrollment_status === '1') {
            $enrollment->field_enrollment_status->value = '0';
          }
          $enrollment->save();
        }
        // Else, the user simply wants to cancel their enrollment, so at
        // this point we can safely delete the enrollment record as well.
        else {
          $enrollment->delete();
        }
      }
      elseif ($to_enroll_status === '1' && $current_enrollment_status === '0') {
        $enrollment->field_enrollment_status->value = '1';

        // If the user was invited to an event, and enrolls for this event
        // not from the /event-invites page, but from the event itself,
        // then we also need to mark this enrollment as accepted, otherwise
        // the list of invites will be empty, but the notification center will
        // say that there are new invites.
        if ($this->moduleHandler->moduleExists('social_event_invite')) {
          $event_invites = $this->eventHelper->getAllUserEventEnrollments((string) $current_user->id());

          if (NULL !== $event_invites && $event_invites > 0 && array_key_exists((int) $enrollment->id(), $event_invites)) {
            $enrollment->set('field_request_or_invite_status', EventEnrollmentInterface::INVITE_ACCEPTED_AND_JOINED);
          }
        }

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
        'user_id' => $current_user->id(),
        'field_event' => $nid,
        'field_enrollment_status' => '1',
        'field_account' => $current_user->id(),
      ];

      // If request to join is on, alter fields.
      if ($to_enroll_status === '2') {
        $fields['field_enrollment_status'] = '0';
        $fields['field_request_or_invite_status'] = EventEnrollmentInterface::REQUEST_PENDING;
      }

      // Create a new enrollment for the event.
      $enrollment = $this->enrollmentStorage->create($fields);
      $enrollment->save();
      $enroll_confirmation = $this->entityTypeManager->getViewBuilder('node')->view($node, 'teaser');
      $enroll_confirmation['#theme'] = 'event_enrollment_confirmation';
      $response->addCommand(new OpenModalDialogCommand(
        $this->t('Thanks for enrolling!'),
        $enroll_confirmation,
        [
          'width' => '479px',
          'dialogClass' => 'social-dialog--event-addtocal',
        ]
      ));
    }

    $new_form = $this->buildForm($form, $form_state);
    $new_form = $this->formBuilder->doBuildForm($this->getFormId(), $new_form, $form_state);
    $response->addCommand(new ReplaceCommand('#enroll-wrapper', $new_form['enroll_wrapper']));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

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
