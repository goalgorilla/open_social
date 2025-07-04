<?php

namespace Drupal\activity_send_email\Plugin\QueueWorker;

use Drupal\activity_creator\ActivityInterface;
use Drupal\activity_send\Plugin\QueueWorker\ActivitySendWorkerBase;
use Drupal\activity_send_email\EmailFrequencyManager;
use Drupal\activity_send_email\Plugin\ActivityDestination\EmailActivityDestination;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An activity send email worker.
 *
 * @QueueWorker(
 *   id = "activity_digest_worker",
 *   title = @Translation("Process activity_digest_worker queue."),
 *   cron = {"time" = 60}
 * )
 *
 * This QueueWorker is responsible for sending emails from the queue
 */
class ActivityDigestWorker extends ActivitySendWorkerBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The email frequency manager.
   *
   * @var \Drupal\activity_send_email\EmailFrequencyManager
   */
  protected $emailFrequencyManager;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    EmailFrequencyManager $email_frequency_manager,
    MailManagerInterface $mail_manager,
    RendererInterface $renderer,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->emailFrequencyManager = $email_frequency_manager;
    $this->mailManager = $mail_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.emailfrequency'),
      $container->get('plugin.manager.mail'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    if (!empty($data['uid']) && !empty($data['frequency']) && !empty($data['activities'])) {
      /** @var \Drupal\user\UserStorage $user_storage */
      $user_storage = $this->entityTypeManager->getStorage('user');
      /** @var \Drupal\Core\Entity\ContentEntityNullStorage $message_storage */
      $message_storage = $this->entityTypeManager->getStorage('message');

      // Get target account.
      /** @var \Drupal\user\UserInterface $target */
      $target = $user_storage->load($data['uid']);

      // Make sure we have an actual user account to work with.
      if (($target instanceof UserInterface)
        && $target->isActive()
        && $target->isAuthenticated()
      ) {
        $langcode = $target->getPreferredLangcode();
        $digest_notifications = [
          '#theme' => 'digestmail',
        ];
        $activity_storage = $this->entityTypeManager->getStorage('activity');

        foreach ($data['activities'] as $activity_id) {
          /** @var \Drupal\activity_creator\Entity\Activity $activity */
          $activity = $activity_storage->load($activity_id);

          // Only for users that have access to related content.
          if (
            !($activity instanceof ActivityInterface) ||
            ($related_entity = $activity->getRelatedEntity()) === NULL ||
            !$related_entity->access('view', $target)
          ) {
            continue;
          }

          // Continue if we have text to send.
          if (isset($activity->field_activity_output_text)) {
            // Load the message.
            /** @var \Drupal\message\Entity\Message $message */
            $message = $message_storage->load($activity->field_activity_message->target_id);
            $body_text = EmailActivityDestination::getSendEmailOutputText($message, $langcode);

            if ($langcode && !empty($body_text)) {
              $digest_notifications['#notifications'][] = $body_text;
            }
          }
        }

        // If we have notification to send continue preparing the email.
        if (!empty($digest_notifications['#notifications'])) {
          $notification_count = count($digest_notifications['#notifications']);

          // Get the notification count for the email template.
          $digest_notifications['#notification_count'] = $this->formatPlural(
            $notification_count,
            'You have received <strong>:count</strong> notification',
            'You have received <strong>:count</strong> notifications',
            [':count' => $notification_count],
            ['langcode' => $langcode]
          );

          if ($this->emailFrequencyManager->hasDefinition($data['frequency'])) {

            /** @var \Drupal\activity_send_email\EmailFrequencyInterface $instance */
            $instance = $this->emailFrequencyManager->createInstance($data['frequency']);

            // Translating frequency instance in the language of the user.
            // @codingStandardsIgnoreStart
            $frequency_translated = $this->t(
              $instance->getName()->getUntranslatedString(),
              [],
              ['langcode' => $langcode]
            );
            // @codingStandardsIgnoreEnd

            // Get the notification settings for the email template.
            $digest_notifications['#notification_settings'] = $this->formatPlural(
              $notification_count,
              'Based on your @settings, the notification above is sent to you as a <strong>:frequency mail</strong>',
              'Based on your @settings, the notifications above are sent to you as a <strong>:frequency mail</strong>',
              [
                '@settings' => Link::fromTextAndUrl(
                  $this->t('email notification settings'),
                  Url::fromRoute('social_user.my_settings')->setAbsolute())->toString(),
                ':frequency' => $frequency_translated,
              ],
              ['langcode' => $langcode]
            );

            // Render the notifications using the digestmail.html.twig template.
            $params['body'] = $this->renderer->renderRoot($digest_notifications);

            // Send the email.
            $this->mailManager->mail(
              'activity_send_email',
              'activity_send_email',
              $target->getEmail(),
              $langcode,
              $params,
              NULL,
              TRUE
            );
          }
        }
      }
    }
  }

}
