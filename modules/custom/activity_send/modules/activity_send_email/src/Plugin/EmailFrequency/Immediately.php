<?php

namespace Drupal\activity_send_email\Plugin\EmailFrequency;

use Drupal\activity_creator\ActivityFactory;
use Drupal\activity_creator\ActivityInterface;
use Drupal\activity_send_email\EmailFrequencyBase;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\message\MessageInterface;
use Drupal\social_core\Service\ConfigLanguageManager;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define a concrete class for immediate emails.
 *
 * @EmailFrequency(
 *   id = "immediately",
 *   name = @Translation("Immediately"),
 *   weight = 10,
 *   interval = 0
 * )
 */
class Immediately extends EmailFrequencyBase implements ContainerFactoryPluginInterface {
  use LoggerChannelTrait;
  use StringTranslationTrait;

  /**
   * The config language manager object.
   *
   * @var \Drupal\social_core\Service\ConfigLanguageManager
   */
  protected ConfigLanguageManager $configLanguageManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The renderer services.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The activity factory service.
   *
   * @var \Drupal\activity_creator\ActivityFactory
   */
  private ActivityFactory $activityFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigLanguageManager $config_language_manager,
    MailManagerInterface $mail_manager,
    RendererInterface $renderer,
    EntityTypeManagerInterface $entity_type_manager,
    ActivityFactory $activity_factory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configLanguageManager = $config_language_manager;
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
    $this->entityTypeManager = $entity_type_manager;
    $this->activityFactory = $activity_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_core.config_language_manager'),
      $container->get('plugin.manager.mail'),
      $container->get('renderer'),
      $container->get('entity_type.manager'),
      $container->get('activity_creator.activity_factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem(ActivityInterface $activity, MessageInterface $message, User $target, $body_text = NULL) {
    // If the user is blocked, we don't want to process this item further.
    if ($target->isBlocked() || $activity->getRelatedEntity() === NULL) {
      return;
    }

    // Continue if we have text to send and the user is currently offline.
    if (isset($activity->field_activity_output_text) && EmailActivityDestination::isUserOffline($target)) {
      // Get the users preferred language.
      $langcode = $target->getPreferredLangcode();

      // Ask our Drupal to load configuration in user's preferred language.
      $this->configLanguageManager->configOverrideLanguageStart($langcode);

      $subject = '';
      // If configured grab the email subject.
      /** @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage('message_template');
      /** @var \Drupal\message\MessageTemplateInterface $template */
      $template = $storage->load($message->bundle());

      if ($template !== NULL) {
        $subject_array = $this->activityFactory->getMessageSubject($message, $langcode);
        $subject = $this->renderer->renderInIsolation($subject_array);
      }

      // Revert the config override.
      $this->configLanguageManager->configOverrideLanguageEnd();

      // If no body text is provided, get it from message for given language.
      if (!$body_text) {
        $this->configLanguageManager->stringTranslationOverrideLanguageStart($langcode);
        $body_text = EmailActivityDestination::getSendEmailOutputText($message, $langcode);
        $this->configLanguageManager->stringTranslationOverrideLanguageEnd();
      }

      if ($langcode && !empty($body_text)) {
        $this->sendEmail($body_text, $langcode, $target, $subject);
      }
    }
  }

  /**
   * Send an email with a single notification.
   *
   * @param string $body_text
   *   The text to send to the target user.
   * @param string $langcode
   *   The langcode of the target user.
   * @param \Drupal\user\Entity\User $target
   *   The target account to send the email to.
   * @param string $subject
   *   The email subject.
   */
  protected function sendEmail(string $body_text, string $langcode, User $target, string $subject = '') {
    $params = [];
    // Translating frequency instance in the language of the user.
    $frequency_translated = $this->t('@frequency_name', [
      '@frequency_name' => $this->getName()->getUntranslatedString(),
    ],
      ['langcode' => $langcode]
    );

    // Construct the render array.
    $notification = [
      '#theme' => 'directmail',
      '#notification' => $body_text,
      '#notification_settings' => $this->t('Based on your @settings, the notification above is sent to you <strong>:frequency</strong>', [
        '@settings' => Link::fromTextAndUrl($this->t('email notification settings', [], ['langcode' => $langcode]), Url::fromRoute('social_user.my_settings')->setAbsolute())->toString(),
        ':frequency' => $frequency_translated,
      ],
      ['langcode' => $langcode]),
    ];

    // Construct the body & subject for email sending.
    $params['body'] = $this->renderer->renderInIsolation($notification);
    if ($subject !== '') {
      $params['subject'] = $subject;
    }

    if (!empty($target->getEmail())) {
      // Send the email.
      $this->mailManager->mail(
        'activity_send_email',
        'activity_send_email',
        $target->getEmail(),
        $langcode,
        $params
      );
    }
    else {
      $this->getLogger('activity_send')->alert($this->t('Email of the @id is missing', [
        '@id' => $target->id(),
      ])->render());
    }
  }

}
