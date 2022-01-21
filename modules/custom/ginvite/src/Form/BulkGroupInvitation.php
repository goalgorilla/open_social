<?php

namespace Drupal\ginvite\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ginvite\GroupInvitationLoader;
use Drupal\group\GroupMembershipLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class BulkGroupInvitation.
 */
class BulkGroupInvitation extends FormBase implements ContainerInjectionInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;


  /**
   * Group.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * Constructs a new BulkGroupInvitation Form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\group\GroupMembershipLoaderInterface $group_membership_loader
   *   The group membership loader.
   * @param \Drupal\ginvite\GroupInvitationLoader $invitation_loader
   *   Invitations loader service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    PrivateTempStoreFactory $temp_store_factory,
    LoggerChannelFactoryInterface $logger_factory,
    MessengerInterface $messenger,
    GroupMembershipLoaderInterface $group_membership_loader,
    GroupInvitationLoader $invitation_loader
  ) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->tempStoreFactory = $temp_store_factory;
    $this->loggerFactory = $logger_factory;
    $this->messenger = $messenger;
    $this->groupMembershipLoader = $group_membership_loader;
    $this->groupInvitationLoader = $invitation_loader;
    $this->group = $this->routeMatch->getParameter('group');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('logger.factory'),
      $container->get('messenger'),
      $container->get('group.membership_loader'),
      $container->get('ginvite.invitation_loader')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bulk_group_invitation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['email_address'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Select Recipients'),
      '#description' => $this->t('You can copy/paste multiple emails, enter one email per line.'),
      '#required' => TRUE,
    ];

    $form['actions']['submit_cancel'] = [
      '#type' => 'submit',
      '#weight' => 999,
      '#value' => $this->t('Back to group'),
      '#submit' => [[$this, 'cancelForm']],
      '#limit_validation_errors' => [],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Cancel form taking you back to a group.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.group.canonical', ['group' => $this->group->id(), []]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {

      switch ($key) {
        case 'email_address':

          $this->validateEmails($form_state);
          $this->validateExistingMembers($form_state);
          $this->validateInviteDuplication($form_state);
          break;
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Prepare params to store them in tempstore.
    $params['gid'] = $this->group->id();
    $params['plugin'] = $this->group->getGroupType()->getContentPlugin('group_invitation')->getContentTypeConfigId();
    $params['emails'] = $this->getSubmittedEmails($form_state);

    $tempstore = $this->tempStoreFactory->get('ginvite_bulk_invitation');

    try {
      $tempstore->set('params', $params);
      // Redirect to confirm form.
      $form_state->setRedirect('ginvite.invitation.bulk.confirm', ['group' => $this->group->id()]);
    }
    catch (\Exception $error) {
      $this->loggerFactory->get('ginvite')->alert($this->t('@err', ['@err' => $error]));
      $this->messenger->addWarning($this->t('Unable to proceed, please try again.'));
    }
  }

  /**
   * Get array of submited emails.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   List of emails to invite .
   */
  private function getSubmittedEmails(FormStateInterface $form_state) {
    return array_map('trim', array_unique(explode("\r\n", trim($form_state->getValue('email_address')))));
  }

  /**
   * Validate emails, display error message if not valid.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  private function validateEmails(FormStateInterface $form_state) {
    $invalid_emails = [];
    foreach ($this->getSubmittedEmails($form_state) as $line => $email) {
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $invalid_emails[$line + 1] = $email;
      }
    }

    if (!empty($invalid_emails)) {
      $message_singular = "The @error_message is not a valid e-mail address.";
      $message_plural = "The e-mails: @error_message are not valid e-mail addresses.";

      $this->displayErrorMessage($invalid_emails, $message_singular, $message_plural, $form_state);
    }
  }

  /**
   * Validate if emails belong to existing group member,display an error if so.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  private function validateExistingMembers(FormStateInterface $form_state) {
    $invalid_emails = [];
    foreach ($this->getSubmittedEmails($form_state) as $line => $email) {
      if ($user = user_load_by_mail($email)) {
        $membership = $this->groupMembershipLoader->load($this->group, $user);
        if (!empty($membership)) {
          $invalid_emails[$line + 1] = $email;
        }
      }
    }

    if (!empty($invalid_emails)) {
      $message_singular = "User with @error_message e-mail already a member of this group.";
      $message_plural = "Users with: @error_message e-mails already members of this group.";

      $this->displayErrorMessage($invalid_emails, $message_singular, $message_plural, $form_state);
    }
  }

  /**
   * Validate if emails have already been invited, display an error if so.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  private function validateInviteDuplication(FormStateInterface $form_state) {
    $invalid_emails = [];
    foreach ($this->getSubmittedEmails($form_state) as $line => $email) {
      if ($this->groupInvitationLoader->loadByGroup($this->group, NULL, $email)) {
        $invalid_emails[$line + 1] = $email;
        break;
      }
    }

    if (!empty($invalid_emails)) {
      $message_singular = "Invitation to @error_message already sent.";
      $message_plural = "Invitations to: @error_message already sent.";

      $this->displayErrorMessage($invalid_emails, $message_singular, $message_plural, $form_state);
    }
  }

  /**
   * Prepares form error message if there is invalid emails.
   *
   * @param array $invalid_emails
   *   List of invalid emails.
   * @param string $message_singular
   *   Error message for one invalid email.
   * @param string $message_plural
   *   Error message for multiple invalid emails.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  private function displayErrorMessage(array $invalid_emails, $message_singular, $message_plural, FormStateInterface $form_state) {
    if (($count = count($invalid_emails)) > 1) {
      $error_message = '<ul>';
      foreach ($invalid_emails as $line => $invalid_email) {
        $error_message .= "<li>{$invalid_email} on line {$line}</li>";
      }
      $error_message .= '</ul>';
      $form_state->setErrorByName('email_address', $this->formatPlural($count, $message_singular, $message_plural, ['@error_message' => new FormattableMarkup($error_message, [])]));
    }
    elseif ($count == 1) {
      $error_message = reset($invalid_emails);
      $form_state->setErrorByName('email_address', $this->formatPlural($count, $message_singular, $message_plural, ['@error_message' => $error_message]));
    }
  }

}
