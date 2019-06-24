<?php

namespace Drupal\social_user\Plugin\Action;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\user\UserInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsPreconfigurationInterface;
use Egulias\EmailValidator\EmailValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An example action covering most of the possible options.
 *
 * @Action(
 *   id = "social_user_send_email",
 *   label = @Translation("Send email"),
 *   type = "user",
 *   confirm = TRUE,
 * )
 */
class SocialSendEmail extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, ViewsBulkOperationsPreconfigurationInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The user storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The email validator.
   *
   * @var \Egulias\EmailValidator\EmailValidator
   */
  protected $emailValidator;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a SocialSendEmail object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Token $token,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerInterface $logger,
    MailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    EmailValidator $email_validator,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    $this->storage = $entity_type_manager->getStorage('user');
    $this->logger = $logger;
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->emailValidator = $email_validator;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('action'),
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
      $container->get('email.validator'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if (!$entity->getEntityTypeId() === 'user') {
      $this->logger->notice('Can not send e-mail for %entity', [
        '%entity' => $entity->getEntityTypeId() . ':' . $entity->id(),
      ]);

      return;
    }

    /** @var \Drupal\user\Entity\User $entity */
    if ($entity) {
      $langcode = $entity->getPreferredLangcode();
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    $params = ['context' => $this->configuration];
    $email = $this->getEmail($entity);

    $message = $this->mailManager->mail('system', 'action_send_email', $email, $langcode, $params);

    // Error logging is handled by \Drupal\Core\Mail\MailManager::mail().
    if ($message['result']) {
      $this->logger->notice('Sent email to %recipient', [
        '%recipient' => $email,
      ]);
    }

    return $this->t('Send email');
  }

  /**
   * Returns the email address of this account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user object.
   *
   * @return string|null
   *   The email address, or NULL if the account is anonymous or the user does
   *   not have an email address.
   */
  public function getEmail(UserInterface $account) {
    return $account->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPreConfigurationForm(array $form, array $values, FormStateInterface $form_state) {
  }

  /**
   * Configuration form builder.
   *
   * If this method has implementation, the action is
   * considered to be configurable.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   The configuration form.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    if (!$from = $form_state->getValue('from')) {
      if (!$from = $this->configFactory->get('system.site')->get('mail')) {
        $from = ini_get('sendmail_from');
      }
    }

    $form['from'] = [
      '#type' => 'email',
      '#title' => $this->t('From'),
      '#default_value' => $from,
      '#required' => TRUE,
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('subject'),
      '#maxlength' => '254',
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('message'),
      '#cols' => '80',
      '#rows' => '20',
    ];

    $form['#title'] = $this->t('Send an email to :selected_count members', [
      ':selected_count' => $this->context['selected_count'],
    ]);

    if (isset($form['list'])) {
      unset($form['list']);
    }

    $form['actions']['submit']['#value'] = $this->t('Send email');

    $classes = ['button', 'btn', 'waves-effect', 'waves-btn'];

    $form['actions']['submit']['#attributes']['class'] = [
      'button--primary',
      'js-form-submit',
      'form-submit',
      'js-form-submit',
      'btn-raised',
      'btn-primary',
      'waves-light',
    ] + $classes;

    $form['actions']['cancel']['#attributes']['class'] = [
      'button--danger',
      'btn-flat',
    ] + $classes;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // @TODO Check for proper access here.
    return TRUE;
  }

}
