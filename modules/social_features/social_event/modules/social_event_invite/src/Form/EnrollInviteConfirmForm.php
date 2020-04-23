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
use Drupal\social_event\EventEnrollmentStatusHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnrollInviteConfirmForm.
 *
 * @package Drupal\social_event_invite\Form
 */
class EnrollInviteConfirmForm extends FormBase {

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
   * The recipients.
   *
   * @var array
   */
  private $recipients;

  /**
   * The event node id.
   *
   * @var string
   */
  private $nid;

  /**
   * The invite type.
   *
   * @var string
   */
  private $inviteType;

  /**
   * EnrollInviteConfirmForm constructor.
   *
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect interface.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The account interface.
   * @param \Drupal\social_event\EventEnrollmentStatusHelper $enrollmentStatusHelper
   *   The enrollment status helper.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   *   The temp store factory.
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
   * Provide a cancel redirect.
   */
  private function getCancelUrl() {
    if ($this->inviteType === 'user') {
      return Url::fromRoute('social_event_invite.invite_user', ['node' => $this->nid]);
    }
    else {
      return Url::fromRoute('social_event_invite.invite_email', ['node' => $this->nid]);
    }
  }

  /**
   * Builds a list with recipients.
   *
   * @return string
   *   Returns a markup string with the recipient(s).
   */
  private function getRecipientsList() {
    $recipients = "";
    if ($this->inviteType === 'user') {
      // Load the user profile to format a nice name.
      foreach ($this->recipients as $key => $value) {
        /** @var \Drupal\profile\Entity\Profile $user_profile */
        $user_profile = Profile::load($key);
        $recipient = $user_profile->field_profile_first_name->value . ' ' . $user_profile->field_profile_last_name->value;
        $recipients .= "{$recipient} <br />";
      }
    }
    elseif ($this->inviteType === 'email') {
      // Simply provide the email.
      foreach ($this->recipients as $recipient) {
        $recipients .= "{$recipient} <br />";
      }
    }

    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get the tempstore values.
    $tempstore = $this->tempStoreFactory->get('event_invite_form_values');
    $params = $tempstore->get('params');
    $this->inviteType = $params['invite_type'];
    $this->recipients = $params['recipients'];
    $this->nid = $params['nid'];
    $recipients_list_markup = $this->getRecipientsList();

    $form['#attributes']['class'][] = 'form--default';

    // Based on the invite type, prepare some variables.
    $questionType = '';
    if ($this->inviteType === 'user') {
      $questionType = 'user(s)';
    }
    elseif ($this->inviteType === 'email') {
      $questionType = 'e-mail(s)';
    }
    $form['question'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Are you sure you want to send a invitation to all @questionType listed bellow?', ['@questionType' => $questionType]),
      '#weight' => 1,
      '#prefix' => '<div class="card"><div class="card__block">',
      '#suffix' => '</div></div>',
    ];

    $form['question']['invitees'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('Invitation recipients: <br /> @recipients_list',
        [
          '@recipients_list' => new FormattableMarkup($recipients_list_markup, []),
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
    if ($this->inviteType === 'email') {
      $batch = [
        'title' => $this->t('Sending invites...'),
        'init_message' => $this->t("Preparing to send invites..."),
        'operations' => [
          [
            '\Drupal\social_event_invite\SocialEventInviteBulkHelper::bulkInviteEmails',
            [$this->recipients, $this->nid],
          ],
        ],
        'finished' => '\Drupal\social_event_invite\SocialEventInviteBulkHelper::bulkInviteEmailFinished',
      ];
      batch_set($batch);
    }
    elseif ($this->inviteType === 'user') {
      $batch = [
        'title' => $this->t('Sending invites...'),
        'init_message' => $this->t("Preparing to send invites..."),
        'operations' => [
          [
            '\Drupal\social_event_invite\SocialEventInviteBulkHelper::bulkInviteUsers',
            [$this->recipients, $this->nid],
          ],
        ],
        'finished' => '\Drupal\social_event_invite\SocialEventInviteBulkHelper::bulkInviteUserFinished',
      ];
      batch_set($batch);
    }
  }

}
