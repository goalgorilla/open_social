<?php

namespace Drupal\social_user\Plugin\Action;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
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
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queue;

  /**
   * TRUE if the current user can use the "Mail HTML" text format.
   *
   * @var bool
   */
  protected $allowTextFormat;

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
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Egulias\EmailValidator\EmailValidator $email_validator
   *   The email validator.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   * @param bool $allow_text_format
   *   TRUE if the current user can use the "Mail HTML" text format.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Token $token, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger, LanguageManagerInterface $language_manager, EmailValidator $email_validator, QueueFactory $queue_factory, $allow_text_format) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->token = $token;
    $this->storage = $entity_type_manager;
    $this->logger = $logger;
    $this->languageManager = $language_manager;
    $this->emailValidator = $email_validator;
    $this->queue = $queue_factory;
    $this->allowTextFormat = $allow_text_format;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition,
      $container->get('token'),
      $container->get('entity_type.manager'),
      $container->get('logger.factory')->get('action'),
      $container->get('language_manager'),
      $container->get('email.validator'),
      $container->get('queue'),
      $container->get('current_user')->hasPermission('use text format mail_html')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(array &$context) {
    parent::setContext($context);
    // @todo make the batch size configurable.
    $context['batch_size'] = Settings::get('social_mail_batch_size', 25);
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $objects) {
    // Array $objects contain all the entities of this bulk operation batch.
    // We want smaller queue items then this so we chunk these.
    // @todo make the chunk size configurable or dependable on the batch size.
    $chunk_size = Settings::get('social_mail_chunk_size', 10);
    $chunks = array_chunk($objects, $chunk_size);
    $data = [];
    foreach ($chunks as $chunk) {
      $users = [];
      // The chunk items contain entities, we want to perform an action on this.
      foreach ($chunk as $entity) {
        // The action retrieves the user ID of the user.
        $users[] = $this->execute($entity);
      }

      // Get the entity ID of the email that is send.
      $data['mail'] = $this->configuration['queue_storage_id'];
      // Add the list of user IDs.
      $data['users'] = $users;
      // Create the Queue Item.
      $this->createQueueItem('user_email_queue', $data);
    }

    // Add a clarifying message.
    $this->messenger()->addMessage($this->t('The email(s) will be send in the background. You will be notified upon completion.'));
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\user\UserInterface $entity */
    return $entity->id();
  }

  /**
   * Create Queue Item.
   *
   * @param string $name
   *   The name of the queue.
   * @param array $data
   *   The queue data.
   */
  public function createQueueItem($name, array $data) {
    // Put the $data in the queue item.
    /** @var \Drupal\Core\Queue\QueueInterface $queue */
    $queue = $this->queue->get($name);
    $queue->createItem($data);
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
    $form['reply'] = [
      '#type' => 'email',
      '#title' => $this->t('Reply-to'),
      '#description' => $this->t("The email you are about to send is sent from the platform's email address. If you wish to receive replies on this email on your own email address, please specify your email address in this field."),
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
      '#description' => $this->t("You can use the token [social_user:recipient] for a personalized salutation, to add the users name in your email"),
    ];

    if ($this->allowTextFormat) {
      $form['message']['#type'] = 'text_format';

      $form['message']['#allowed_formats'] = [
        'mail_html',
      ];
    }

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
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Clean form values.
    $form_state->cleanValues();
    // Get the queue storage entity and create a new entry.
    $queue_storage = $this->storage->getStorage('queue_storage_entity');
    $entity = $queue_storage->create([
      'name' => 'user_email_queue',
      'type' => 'email',
      'finished' => FALSE,
      'field_reply_to' => $form_state->getValue('reply'),
      'field_subject' => $form_state->getValue('subject'),
      'field_message' => $form_state->getValue('message')['value'],
    ]);

    // When the new entity is saved, get the ID and save it within the bulk
    // operation action configuration.
    if ($entity->save()) {
      $this->configuration['queue_storage_id'] = $entity->id();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // @todo Check for proper access here.
    return TRUE;
  }

}
