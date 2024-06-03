<?php

namespace Drupal\social_event_managers\Plugin\Action;

use Drupal\activity_send\Plugin\SendActivityDestinationBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Utility\Token;
use Drupal\node\NodeInterface;
use Drupal\social_event\Entity\EventEnrollment;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_user\Plugin\Action\SocialSendEmail;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\views_bulk_operations\Form\ViewsBulkOperationsFormTrait;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send email to event enrollment users.
 *
 * @Action(
 *   id = "social_event_managers_send_email_action",
 *   label = @Translation("Send email to event enrollment users"),
 *   type = "event_enrollment",
 *   view_id = "event_manage_enrollments",
 *   display_id = "page_manage_enrollments",
 *   confirm = TRUE,
 *   confirm_form_route_name = "social_event_managers.vbo.confirm",
 * )
 */
class SocialEventManagersSendEmail extends SocialSendEmail {

  use ViewsBulkOperationsFormTrait;

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Drupal module handler service.
   */
  protected ModuleHandler $moduleHandler;

  /**
   * The tempstore service.
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The current user object.
   */
  protected AccountInterface $currentUser;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    Token $token,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    LanguageManagerInterface $language_manager,
    EmailValidator $email_validator,
    QueueFactory $queue_factory,
    $allow_text_format
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $token,
      $entity_type_manager,
      $logger,
      $language_manager,
      $email_validator,
      $queue_factory,
      $allow_text_format
    );

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    $instance->tempStoreFactory = $container->get('tempstore.private');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    $users = [];
    // Process the event enrollment chunks. These need to be converted to users.
    /** @var \Drupal\social_event\Entity\EventEnrollment $enrollment */
    foreach ($objects as $enrollment) {
      $entities = [];

      // Get the user from the even enrollment.
      /** @var \Drupal\user\Entity\User $user */
      $user = User::load($enrollment->getAccount());

      // Prevent sending emails for all enrollees if all selected users
      // are unsubscribed from receiving emails.
      if ($user && $this->isUnsubscribedFromEmails($user)) {
        continue;
      }

      $entities[] = $this->execute($user);

      $users += $this->entityTypeManager->getStorage('user')->loadMultiple($entities);
    }

    // Pass it back to our parent who handles the creation of queue items.
    return parent::executeMultiple($users);
  }

  /**
   * {@inheritdoc}
   */
  public function createQueueItem($name, array $data): void {
    $data['bulk_mail_footer'] = TRUE;

    parent::createQueueItem($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $access = AccessResult::allowedIf($object instanceof EventEnrollmentInterface);

    if ($object instanceof EventEnrollmentInterface) {
      // All users with the following access permission should be allowed.
      $access = AccessResult::allowedIfHasPermission($account, 'manage everything enrollments');

      $event_id = $object->getFieldValue('field_event', 'target_id');
      $node = $this->entityTypeManager->getStorage('node')->load($event_id);

      // Also Event organizers can do this.
      if ($node instanceof NodeInterface && social_event_manager_or_organizer($node)) {
        $access = AccessResult::allowedIf($object instanceof EventEnrollmentInterface);
      }
    }

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    if (!empty($this->context['selected_removed'])) {
      $this->messenger()
        ->addWarning($this->t('Your email will be sent to members who have opted to receive community updates and announcements'));
      $this->messenger()
        ->addWarning($this->formatPlural($this->context['selected_removed'],
          "1 member won't receive this email due to their communication preferences.",
          "@count members won't receive this email due to their communication preferences."
        ));
    }

    // Add title to the form as well.
    if ($form['#title'] !== NULL) {
      $selected_count = $this->context['selected_count'];
      $subtitle = $this->formatPlural($selected_count,
        'Configure the email you want to send to the one enrollee you have selected.',
        'Configure the email you want to send to the enrollees you have selected.'
      );

      $form['subtitle'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => ['placeholder'],
        ],
        '#value' => $subtitle,
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(array &$context): void {
    parent::setContext($context);

    // @todo Rely on something solid then dynamic data for batch.
    // We don't want to run this code if batch was started.
    // "prepopulated" key is adding exactly on batch start.
    if (isset($context['prepopulated'])) {
      return;
    }

    // Filter enrollees that have disabled bulk mailing based on their profile
    // settings.
    // We need to remove the selected users from temporary data and update
    // form data passed through the further forms.
    if (empty($context['list'])) {
      $selected_options = $context['bulk_form_keys'];
    }
    else {
      $selected_options = array_keys($context['list']);
    }

    if (!$selected_options) {
      return;
    }

    $origin = [...$selected_options];

    // Go through each enrollment and check if a user doesn't have
    // a disabled bulk mailing.
    foreach ($selected_options as $key => $name) {
      $item = $this->getListItem($name);
      if (!in_array('event_enrollment', $item)) {
        continue;
      }

      // First element is the enrollment ID.
      $eid = $item[0];
      $enrollment = EventEnrollment::load($eid);
      if (!$enrollment) {
        continue;
      }

      $account = $enrollment->getAccountEntity();

      // Check user frequency settings for event bulk mailing.
      // If a user has disabled mailing, we remove enrollment from
      // the selected list.
      // Only authenticated users have frequency settings.
      if ($account && $this->isUnsubscribedFromEmails($account)) {
        unset($selected_options[$key]);
      }
    }

    // All selected users can receive the email.
    $removed = array_diff($origin, $selected_options);
    $this->context['selected_removed'] = count($removed);
  }

  /**
   * Gets the current user.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *   The current user.
   */
  protected function currentUser(): AccountInterface {
    return $this->currentUser;
  }

  /**
   * Helps to check if a user is unsubscribed or not from bulk mailing.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user entity.
   *
   * @return bool
   *   If unsubscribed TRUE, otherwise FALSE.
   */
  private function isUnsubscribedFromEmails(UserInterface $user): bool {
    if ($user->isAuthenticated() && $this->moduleHandler->moduleExists('activity_send_email')) {
      $frequency = SendActivityDestinationBase::getSendUserSettings(destination: 'email', account: $user, type: 'bulk_mailing');
      return !empty($frequency['event_enrollees']) && $frequency['event_enrollees'] === FREQUENCY_NONE;
    }

    return FALSE;
  }

}
