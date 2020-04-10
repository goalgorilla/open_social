<?php

namespace Drupal\social_event_invite\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\social_core\Form\InviteEmailBaseForm;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnrollInviteForm.
 */
class EnrollInviteEmailForm extends InviteEmailBaseForm {

  /**
   * The node storage for event enrollments.
   *
   * @var \Drupal\Core\entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_invite_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, EntityStorageInterface $entity_storage) {
    parent::__construct($route_match, $entity_type_manager, $logger_factory);
    $this->entityStorage = $entity_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('entity.manager')->getStorage('event_enrollment'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $nid = $this->routeMatch->getRawParameter('node');

    $form['event'] = [
      '#type' => 'hidden',
      '#value' => $nid,
    ];

    $form['actions']['submit_cancel'] = [
      '#type' => 'submit',
      '#weight' => 999,
      '#value' => $this->t('Back to event'),
      '#submit' => [[$this, 'cancelForm']],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * Cancel form taking you back to an event.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('view.event_manage_enrollments.page_manage_enrollments', [
      'node' => $this->routeMatch->getRawParameter('node'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $duplicated_values = [];
    $emails = $this->getSubmittedEmails($form_state);
    $nid = $form_state->getValue('event');

    // Check if the user is already enrolled.
    foreach ($emails as $email) {
      $user = user_load_by_mail($email);

      if ($user instanceof UserInterface) {
        $conditions = [
          'field_account' => $user->id(),
          'field_event' => $nid,
        ];

        $user = $user->getEmail();
      }
      else {
        $conditions = [
          'field_email' => $email,
          'field_event' => $nid,
        ];

        $user = $email;
      }

      $enrollments = $this->entityStorage->loadByProperties($conditions);

      // If enrollments can be found this user is already invited or joined.
      if (!empty($enrollments)) {
        $duplicated_values[] = $user;
      }
    }
    if (!empty($duplicated_values)) {
      $users = implode(', ', $duplicated_values);

      $message = \Drupal::translation()->formatPlural(count($duplicated_values),
        "@users is already invited or enrolled, you can't invite them again",
        "@users are already invited or enrolled, you can't invite them again",
        ['@users' => $users]
      );

      $form_state->setErrorByName('email_address', $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $emails = $this->getSubmittedEmails($form_state);
    $nid = $form_state->getValue('event');

    $batch = [
      'title' => $this->t('Sending invites...'),
      'init_message' => $this->t("Preparing to send invites..."),
      'operations' => [
        [
          '\Drupal\social_event_invite\SocialEventInviteBulkHelper::bulkInviteEmails',
          [$emails, $nid],
        ],
      ],
    ];
    batch_set($batch);
  }

}
