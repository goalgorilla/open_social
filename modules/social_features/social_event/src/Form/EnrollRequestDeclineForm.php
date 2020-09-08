<?php

namespace Drupal\social_event\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\profile\Entity\Profile;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\EventEnrollmentStatusHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnrollRequestDeclineForm.
 *
 * @package Drupal\social_event\Form
 */
class EnrollRequestDeclineForm extends FormBase {

  /**
   * The event enrollment entity.
   *
   * @var \Drupal\social_event\Entity\EventEnrollment
   */
  protected $eventEnrollment;

  /**
   * The redirect destination helper.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The event invite status helper.
   *
   * @var \Drupal\social_event\EventEnrollmentStatusHelper
   */
  protected $eventInviteStatus;

  /**
   * The full name of the user.
   *
   * @var string
   */
  protected $fullName;

  /**
   * EnrollRequestDeclineForm constructor.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect interface.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The account interface.
   * @param \Drupal\social_event\EventEnrollmentStatusHelper $enrollmentStatusHelper
   *   The enrollment status helper.
   */
  public function __construct(RedirectDestinationInterface $redirect_destination, AccountInterface $current_user, EventEnrollmentStatusHelper $enrollmentStatusHelper) {
    $this->redirectDestination = $redirect_destination;
    $this->currentUser = $current_user;
    $this->eventInviteStatus = $enrollmentStatusHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('current_user'),
      $container->get('social_event.status_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'request_enrollment_decline_form';
  }

  /**
   * {@inheritdoc}
   */
  private function getCancelUrl() {
    return Url::fromUserInput($this->redirectDestination->get());
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the event_enrollment from the request.
    $this->eventEnrollment = $this->getRequest()->get('event_enrollment');

    // Load the user profile to format a nice name.
    if (!empty($this->eventEnrollment)) {
      /** @var \Drupal\profile\Entity\Profile $user_profile */
      $user_profile = Profile::load($this->eventEnrollment->getAccount());
      $this->fullName = $user_profile->field_profile_first_name->value . ' ' . $user_profile->field_profile_last_name->value;
    }

    $form['#attributes']['class'][] = 'form--default';

    $form['question'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure you want to decline the enrollment request for @name?', [
        '@name' => $this->fullName,
      ]),
      '#weight' => 1,
      '#prefix' => '<div class="card"><div class="card__block">',
      '#suffix' => '</div></div>',
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => [
        'class' => [
          'button',
          'button--flat',
          'btn',
          'btn-flat',
          'waves-effect',
          'waves-btn',
        ],
      ],
      '#url' => $this->getCancelUrl(),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Yes'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!empty($this->eventEnrollment)) {
      $this->eventEnrollment->field_request_or_invite_status->value = EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED;
      $this->eventEnrollment->save();
    }

    $this->messenger()->addStatus($this->t('The enrollment request of @name has been declined.', ['@name' => $this->fullName]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
