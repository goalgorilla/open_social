<?php

namespace Drupal\social_event_invite\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\profile\Entity\Profile;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\EventEnrollmentStatusHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnrollInviteConfirmForm.
 *
 * @package Drupal\social_event_invite\Form
 */
class EnrollInviteConfirmForm extends FormBase {

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
   * Tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var array
   */
  private $recipients;

  /**
   * @var string
   */
  private $nid;


  /**
   * EnrollInviteConfirmForm constructor.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect interface.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The account interface.
   * @param \Drupal\social_event\EventEnrollmentStatusHelper $enrollmentStatusHelper
   *   The enrollment status helper.
   */
  public function __construct(RedirectDestinationInterface $redirect_destination, AccountInterface $current_user, EventEnrollmentStatusHelper $enrollmentStatusHelper, PrivateTempStoreFactory $tempStoreFactory) {
    $this->redirectDestination = $redirect_destination;
    $this->currentUser = $current_user;
    $this->eventInviteStatus = $enrollmentStatusHelper;
    $this->tempStoreFactory = $tempStoreFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('redirect.destination'),
      $container->get('current_user'),
      $container->get('social_event.status_helper'),
      $container->get('tempstore.private')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_enrollment_confirm_form';
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
    $tempstore = $this->tempStoreFactory->get('event_invite_form_values');
    $params = $tempstore->get('params');
    $this->recipients = $params['recipients'];
    $this->nid = $params['nid'];

//    // Get the event_enrollment from the request.
//    $this->eventEnrollment = $this->getRequest()->get('event_enrollment');
//
//    // Load the user profile to format a nice name.
//    if (!empty($this->eventEnrollment)) {
//      /** @var \Drupal\profile\Entity\Profile $user_profile */
//      $user_profile = Profile::load($this->eventEnrollment->getAccount());
//      $this->fullName = $user_profile->field_profile_first_name->value . ' ' . $user_profile->field_profile_last_name->value;
//    }

    $form['#attributes']['class'][] = 'form--default';

    $form['question'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure you want to send a invitation to all e-mails listed bellow?'),
      '#weight' => 1,
      '#prefix' => '<div class="card"><div class="card__block">',
      '#suffix' => '</div></div>',
    ];

    $recipients_list_markup = "";
    foreach ($this->recipients as $recipient) {
      $recipients_list_markup .= "{$recipient} <br />";
    }

    $form['question']['invitees'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t("Invitation recipients: <br /> @email_list",
        [
          '@email_list' => new FormattableMarkup($recipients_list_markup, []),
        ]
      ),
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
    $batch = [
      'title' => $this->t('Sending invites...'),
      'init_message' => $this->t("Preparing to send invites..."),
      'operations' => [
        [
          '\Drupal\social_event_invite\SocialEventInviteBulkHelper::bulkInviteEmails',
          [$this->recipients, $this->nid],
        ],
      ],
    ];
    batch_set($batch);
  }

}
