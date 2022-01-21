<?php

namespace Drupal\social_mailer\Plugin\EmailAdjuster;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailAdjusterBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\RoleStorageInterface;
use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Defines the Reroute Email Adjuster.
 *
 * @EmailAdjuster(
 *   id = "reroute",
 *   label = @Translation("Reroute emails"),
 *   description = @Translation("Reroute emails to a specific email address."),
 *   weight = 1000,
 * )
 */
class Reroute extends EmailAdjusterBase implements ContainerFactoryPluginInterface, TrustedCallbackInterface {

  /**
   * The configuration factory.
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The role storage.
   */
  protected RoleStorageInterface $roleStorage;

  /**
   * The email validator.
   */
  protected EmailValidatorInterface $emailValidator;

  /**
   * The logger instance.
   */
  protected LoggerInterface $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')->getStorage('user_role'),
      $container->get('email.validator'),
      $container->get('logger.factory')->get('social_mailer')
    );
  }

  /**
   * Constructs a Reroute object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\user\RoleStorageInterface $role_storage
   *   The role storage.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    RoleStorageInterface $role_storage,
    EmailValidatorInterface $email_validator,
    LoggerInterface $logger
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->roleStorage = $role_storage;
    $this->emailValidator = $email_validator;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $default_address = $this->configuration['address'] ?: $this->configFactory->get('system.site')->get('mail');
    $form['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Rerouting email addresses'),
      '#default_value' => $default_address,
      '#description' => $this->t('Provide a comma-delimited list of email addresses. Every destination email address which is not fit with "Skip email rerouting for" lists will be rerouted to these addresses.<br/>If this field is empty and no value is provided, all outgoing emails would be aborted and the email would be recorded in the recent log entries (if enabled).'),
      '#element_validate' => [
        [$this, 'validateEmails'],
      ],
      '#reroute_config_delimiter' => ',',
      '#required' => TRUE,
    ];

    $form['allowed'] = [
      '#type' => 'textarea',
      '#rows' => 2,
      '#title' => $this->t('Skip email rerouting for email addresses:'),
      '#default_value' => $this->configuration['allowed'] ?? '',
      '#description' => $this->t('Provide a line-delimited list of email addresses to pass through. All emails to addresses from this list will not be rerouted.<br/>A patterns like "*@example.com" and "myname+*@example.com" can be used to add all emails by its domain or the pattern.'),
      '#element_validate' => [
        [$this, 'validateEmails'],
      ],
      '#pre_render' => [[$this, 'textareaRowsValue']],
    ];

    $roles = [];

    foreach ($this->roleStorage->loadMultiple() as $role) {
      /** @var \Drupal\user\RoleInterface $role */
      if ($role->id() !== 'anonymous') {
        $roles[$role->id()] = $role->get('label');
      }
    }

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Skip email rerouting for roles:'),
      '#description' => $this->t("Emails that belong to users with selected roles won't be rerouted."),
      '#options' => $roles,
      '#default_value' => $this->configuration['roles'] ?? [],
    ];

    $form['description'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show rerouting description in mail body'),
      '#default_value' => $this->configuration['description'] ?? '',
      '#description' => $this->t('Check this box if you want a message to be inserted into the email body when the mail is being rerouted. Otherwise, SMTP headers will be used to describe the rerouting. If sending rich-text email, leave this unchecked so that the body of the email will not be disturbed.'),
    ];

    $form['message'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a Drupal status message after rerouting'),
      '#default_value' => $this->configuration['message'] ?? FALSE,
      '#description' => $this->t('Check this box if you would like a Drupal status message to be displayed to users after submitting an email to let them know it was aborted to send or rerouted to a different email address.'),
    ];

    return $form;
  }

  /**
   * Adjust rows value according to the content size.
   *
   * @param array $element
   *   The render array to add the access denied message to.
   *
   * @return array
   *   The updated render array.
   */
  public static function textareaRowsValue(array $element): array {
    $size = substr_count($element['#default_value'], PHP_EOL) + 1;
    if ($size > $element['#rows']) {
      $element['#rows'] = min($size, 10);
    }
    return $element;
  }

  /**
   * Validate multiple email addresses field.
   *
   * @param array $element
   *   A field array to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateEmails(array $element, FormStateInterface $form_state): void {
    // Remove duplicates.
    $addresses = preg_split('/[\s,;\n]+/', $form_state->getValue($element['#name']), -1, PREG_SPLIT_NO_EMPTY);

    if (!is_array($addresses)) {
      return;
    }

    $addresses = array_unique($addresses);
    $addresses = array_map('mb_strtolower', $addresses);

    // Allow only valid email addresses.
    foreach ($addresses as $address) {
      if (!$this->emailValidator->isValid($address)) {
        $form_state->setErrorByName($element['#name'], $this->t('@address is not a valid email address.', [
          '@address' => $address,
        ]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks(): array {
    return ['textareaRowsValue'];
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(EmailInterface $email): void {
    global $base_url;

    $addresses = $email->getTo();
    $to = [];

    foreach ($addresses as $address) {
      $to[] = $address->toString();
    }

    // Save information about the original recipient to a header.
    $this
      ->addOriginalHeader($email, 'to')
      ->addOriginalHeader($email, 'cc')
      ->addOriginalHeader($email, 'bcc');

    // Add information about the email was rerouted.
    if ($this->configuration['description']) {
      $message_lines = [
        $this->t('This email was rerouted.'),
        $this->t('Web site: @site', [
          '@site' => $base_url,
        ]),
        $this->t('Mail key: @key', [
          '@key' => $email->getSubType(),
        ]),
        $this->t('Originally to: @to', [
          '@to' => implode(', ', $to),
        ]),
      ];

      // Add Cc/Bcc values to the message only if they are set.
      $headers = $email->getHeaders();

      if ($header = $headers->get('X-Rerouted-Original-cc')) {
        $message_lines[] = $this->t('Originally cc: @cc', [
          '@cc' => $header->toString(),
        ]);
      }

      if ($header = $headers->get('X-Rerouted-Original-bcc')) {
        $message_lines[] = $this->t('Originally bcc: @bcc', [
          '@cc' => $header->toString(),
        ]);
      }

      // Simple separator between reroute and original messages.
      $message_lines[] = '-----------------------';
      $message_lines[] = '';
      $message = implode('<br />', $message_lines);
      /** @var mixed $body */
      $body = $email->getBody();

      if (is_array($body)) {
        array_unshift($body, $message);
      }
      elseif (is_string($body) || $body instanceof MarkupInterface) {
        $body = Markup::create($message . $body);
      }

      $email->setBody($body);
    }

    if ($this->configuration['message']) {
      $this->messenger()->addMessage(t('An email (ID: %sub_type) either aborted or rerouted to the configured address. For more details please refer to Email policy settings.', [
        '%sub_type' => $email->getSubType(),
      ]));
    }

    $email->setTo($this->configuration['address']);
  }

  /**
   * Adds information about the original "cc", "bcc" and "to" headers.
   *
   * @param \Drupal\symfony_mailer\EmailInterface $email
   *   The email to process.
   * @param string $key
   *   The header key.
   */
  protected function addOriginalHeader(EmailInterface $email, string $key): self {
    $method = 'get' . ucfirst($key);

    if (method_exists($email, $method) && ($original = $email->$method())) {
      $value = [];

      foreach ($original as $address) {
        $value[] = $address->toString();
      }

      $email->addTextHeader("X-Rerouted-Original-{$key}", implode(', ', $value));

      $headers = $email->getHeaders();

      if ($headers->has($key)) {
        $headers->remove($key);
      }
    }

    return $this;
  }

}
