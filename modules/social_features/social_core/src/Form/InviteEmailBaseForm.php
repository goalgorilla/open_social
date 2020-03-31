<?php

namespace Drupal\social_core\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Class InviteBaseForm.
 */
class InviteEmailBaseForm extends FormBase {
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
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new BulkGroupInvitation Form.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(
    RouteMatchInterface $route_match,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->group = $this->routeMatch->getParameter('group');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invite_email_base_form';
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

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Invite(s)'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {

      switch ($key) {
        case 'email_address':

          $this->validateEmails($form_state);
          break;
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
   * Get array of submitted emails.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   List of emails to invite .
   */
  public function getSubmittedEmails(FormStateInterface $form_state) {
    return array_map(
      'trim',
      array_unique(
          $this->extract_emails_from(
            $form_state->getValue('email_address')
        )
      )
    );
  }

  private function extract_emails_from($string){
    preg_match_all("/[\._a-zA-Z0-9+-]+@[\._a-zA-Z0-9+-]+/i", $string, $matches);
    return $matches[0];
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
