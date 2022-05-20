<?php

namespace Drupal\social_event_invite\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileUrlGenerator;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\node\NodeInterface;
use Drupal\social_core\Form\InviteEmailBaseForm;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event\Service\SocialEventEnrollServiceInterface;
use Drupal\social_event_max_enroll\Service\EventMaxEnrollService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EnrollInviteForm.
 */
class EnrollInviteEmailForm extends InviteEmailBaseForm {

  /**
   * The node storage for event enrollments.
   */
  protected EntityStorageInterface $entityStorage;

  /**
   * Drupal\Core\TempStore\PrivateTempStoreFactory definition.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The token service.
   */
  protected Token $token;

  /**
   * The social event enroll.
   */
  protected SocialEventEnrollServiceInterface $eventEnrollService;

  /**
   * The module handler service.
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The event maximum enroll service.
   */
  protected EventMaxEnrollService $eventMaxEnrollService;

  /**
   * File URL Generator services.
   *
   * @var \Drupal\Core\File\FileUrlGenerator
   */
  protected FileUrlGenerator $fileUrlGenerator;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'enroll_invite_email_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityStorage = $instance->entityTypeManager->getStorage('event_enrollment');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->token = $container->get('token');
    $instance->eventEnrollService = $container->get('social_event.enroll');
    $instance->moduleHandler = $container->get('module_handler');
    if ($instance->moduleHandler->moduleExists('social_event_max_enroll')) {
      $instance->eventMaxEnrollService = $container->get('social_event_max_enroll.service');
    }
    $instance->fileUrlGenerator = $container->get('file_url_generator');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['#attributes']['class'][] = 'form--default';

    /** @var \Drupal\node\Entity\Node $node */
    $node = $this->routeMatch->getParameter('node');

    $params = [
      'user' => $this->currentUser(),
      'node' => $node,
    ];

    // Load event invite configuration.
    $invite_config = $this->config('social_event_invite.settings');

    // Cleanup message body and replace any links on invite preview page.
    $body = $this->token->replace($invite_config->get('invite_message'), $params);
    $body = preg_replace('/href="([^"]*)"/', 'href="#"', $body);

    // Get default logo image and replace if it overridden with email settings.
    $theme_id = $this->config('system.theme')->get('default');
    $logo = $this->getRequest()->getBaseUrl() . theme_get_setting('logo.url', $theme_id);
    $email_logo = theme_get_setting('email_logo', $theme_id);

    if (is_array($email_logo) && !empty($email_logo)) {
      $file = File::load(reset($email_logo));

      if ($file instanceof File) {
        $logo = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    $form['email_preview'] = [
      '#type' => 'fieldset',
      '#title' => [
        'text' => [
          '#markup' => t('Preview your email invite'),
        ],
        'icon' => [
          '#markup' => '<svg class="icon icon-expand_more"><use xlink:href="#icon-expand_more" /></svg>',
          '#allowed_tags' => ['svg', 'use'],
        ],
      ],
      '#tree' => TRUE,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#attributes' => [
        'class' => [
          'form-horizontal',
          'form-preview-email',
        ],
      ],
    ];

    $form['email_preview']['preview'] = [
      '#theme' => 'invite_email_preview',
      '#logo' => $logo,
      '#subject' => $this->token->replace($invite_config->get('invite_subject'), $params),
      '#body' => $body,
      '#helper' => $this->token->replace($invite_config->get('invite_helper'), $params),
    ];

    $form['event'] = [
      '#type' => 'hidden',
      '#value' => $this->routeMatch->getRawParameter('node'),
    ];

    $form['actions']['submit_cancel'] = [
      '#type' => 'submit',
      '#weight' => 999,
      '#value' => $this->t('Back to event'),
      '#submit' => [[$this, 'cancelForm']],
      '#limit_validation_errors' => [],
    ];

    // We should prevent invite enrollments if social_event_max_enroll is
    // enabled and there are no left spots.
    if ($this->moduleHandler->moduleExists('social_event_max_enroll')) {
      if (
        $node instanceof NodeInterface &&
        $this->eventMaxEnrollService->isEnabled($node) &&
        $this->eventMaxEnrollService->getEnrollmentsLeft($node) === 0
      ) {
        $form['actions']['submit']['#attributes'] = [
          'disabled' => 'disabled',
          'title' => $this->t('There are no spots left'),
        ];
      }
    }

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
    $nid = $form_state->getValue('event');

    // Check if the user is already enrolled.
    foreach ($form_state->getValue('users_fieldset')['user'] as $user) {
      // Check if the user is a filled in email.
      $email = $this->extractEmailsFrom($user);
      if ($email) {
        $conditions = [
          'field_email' => $email,
          'field_event' => $nid,
        ];
      }
      else {
        $conditions = [
          'field_account' => $user,
          'field_event' => $nid,
        ];
      }

      $enrollments = $this->entityStorage->loadByProperties($conditions);

      if (!empty($enrollments)) {
        /** @var \Drupal\social_event\Entity\EventEnrollment $enrollment */
        $enrollment = end($enrollments);
        // Of course, only delete the previous invite if it was declined
        // or if it was invalid or expired.
        $status_checks = [
          EventEnrollmentInterface::REQUEST_OR_INVITE_DECLINED,
          EventEnrollmentInterface::INVITE_INVALID_OR_EXPIRED,
        ];
        if (in_array($enrollment->field_request_or_invite_status->value, $status_checks)) {
          $enrollment->delete();
          unset($enrollments[$enrollment->id()]);
        }
      }

      // If enrollments can be found this user is already invited or joined.
      if (!empty($enrollments)) {
        // If the user is already enrolled, don't enroll them again.
        $form_state->unsetValue(['users_fieldset', 'user', $user]);
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $params['recipients'] = $form_state->getValue('users_fieldset')['user'];
    $params['nid'] = $form_state->getValue('event');
    $tempstore = $this->tempStoreFactory->get('event_invite_form_values');
    try {
      $tempstore->set('params', $params);

      // Create batch for sending out the invites.
      $batch = [
        'title' => $this->t('Sending invites...'),
        'init_message' => $this->t("Preparing to send invites..."),
        'operations' => [
          [
            '\Drupal\social_event_invite\SocialEventInviteBulkHelper::bulkInviteUsersEmails',
            [$params['recipients'], $params['nid']],
          ],
        ],
        'finished' => '\Drupal\social_event_invite\SocialEventInviteBulkHelper::bulkInviteUserEmailsFinished',
      ];
      batch_set($batch);
    }
    catch (\Exception $error) {
      $this->loggerFactory->get('event_invite_form_values')->alert(t('@err', ['@err' => $error]));
      $this->messenger->addWarning(t('Unable to proceed, please try again.'));
    }
  }

  /**
   * Returns access to the invite page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access results.
   */
  public function inviteAccess(NodeInterface $node, AccountInterface $account) {
    // Anonymous users should not have the possibility to invite users.
    if ($account->isAnonymous()) {
      return AccessResult::forbidden();
    }

    // Allow for event managers/organizers.
    if (social_event_manager_or_organizer()) {
      return AccessResult::allowed();
    }

    // Disable access for non-enrolled users, invite by users was disabled in
    // the event invite settings, enrolment is disabled for this event or event
    // is visible only for group members.
    $event_invite_settings = $this->config('social_event_invite.settings');
    $enrollment = $this->entityTypeManager->getStorage('event_enrollment')
      ->loadByProperties([
        'status' => TRUE,
        'field_account' => $account->id(),
        'field_event' => $node->id(),
        'field_enrollment_status' => '1',
      ]);
    if (
      !$event_invite_settings->get('invite_by_users') ||
      !$this->eventEnrollService->isEnabled($node) ||
      empty($enrollment) ||
      $node->get('field_content_visibility')->getString() === 'group'
    ) {
      return AccessResult::forbidden();
    }

    // Allow sharing/invites for users only if allowed by the event manager.
    if (
      $node->hasField('field_event_send_invite_by_user') &&
      !$node->get('field_event_send_invite_by_user')->isEmpty() &&
      $node->get('field_event_send_invite_by_user')->getString() === '1'
    ) {
      return AccessResult::allowed();
    }

    return AccessResult::neutral();
  }

}
