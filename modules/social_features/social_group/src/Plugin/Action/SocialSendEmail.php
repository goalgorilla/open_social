<?php

namespace Drupal\social_group\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Send email to group members.
 *
 * @Action(
 *   id = "social_group_send_email_action",
 *   label = @Translation("Send email to group members"),
 *   type = "group_content",
 *   confirm = FALSE,
 * )
 */
class SocialSendEmail extends ViewsBulkOperationsActionBase implements ContainerFactoryPluginInterface, PluginFormInterface {

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
   * Constructs a ViewsBulkOperationSendEmail object.
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
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Token $token,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger,
    MailManagerInterface $mail_manager,
    LanguageManagerInterface $language_manager,
    EmailValidator $email_validator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    $this->storage = $entity_type_manager->getStorage('user');
    $this->logger = $logger->get('action');
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\group\Entity\GroupContentInterface $entity */
    $entity = $entity->getEntity();

    /** @var \Drupal\Core\Entity\Entity $entity */
    if ($entity->getEntityTypeId() !== 'user') {
      $this->logger->notice('Can not send e-mail for %entity', [
        '%entity' => $entity->getEntityTypeId() . ':' . $entity->id(),
      ]);

      return;
    }
    /** @var \Drupal\user\UserInterface $entity */
    if ($entity) {
      $langcode = $entity->getPreferredLangcode();
    }
    else {
      $langcode = $this->languageManager->getDefaultLanguage()->getId();
    }

    $params = ['context' => $this->configuration];

    $message = $this->mailManager->mail('system', 'action_send_email', $entity->getEmail(), $langcode, $params);

    // Error logging is handled by \Drupal\Core\Mail\MailManager::mail().
    if ($message['result']) {
      $this->logger->notice('Sent email to %recipient', [
        '%recipient' => $entity->getEmail(),
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#required' => TRUE,
      '#default_value' => $form_state->getValue('subject'),
      '#maxlength' => '254',
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => t('Message'),
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

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($object instanceof GroupContentInterface) {
      /** @var \Drupal\group\Entity\GroupContentInterface $object */
      return $object->access('view', $account, $return_as_object);
    }

    return TRUE;
  }

}
