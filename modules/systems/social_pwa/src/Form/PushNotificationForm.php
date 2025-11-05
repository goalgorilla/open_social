<?php

namespace Drupal\social_pwa\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\social_pwa\WebPushManagerInterface;
use Drupal\social_pwa\WebPushPayload;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Minishlink\WebPush\WebPush;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Push Notifications form.
 */
class PushNotificationForm extends FormBase {

  use MessengerTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\social_pwa\WebPushManagerInterface
   */
  protected $webPushManager;

  /**
   * Create a new PushNotificationForm instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   * @param \Drupal\social_pwa\WebPushManagerInterface $webPushManager
   *   The web push manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, WebPushManagerInterface $webPushManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->webPushManager = $webPushManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('social_pwa.web_push_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'push_notification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory()->get('social_pwa.settings');

    // Check first if sending push notifications is enabled.
    $push_enabled = $settings->get('status.all');
    if (!$push_enabled) {
      $this->messenger()->addWarning($this->t('Sending push notifications is disabled.'));

      return $form;
    }

    // First we check if there are users on the platform that have a
    // subscription.
    // Retrieve all uid.
    $all_users = $this->entityTypeManager
      ->getStorage('user')
      ->getQuery()
      ->condition('uid', 0, '>')
      ->accessCheck()
      ->execute();

    $push_enabled_users = [];

    // Filter to check which users have subscription.
    foreach ($all_users as $uid) {
      /** @var \Drupal\user\Entity\User $account */
      if ($account = User::load($uid)) {
        $user_subscriptions = $this->webPushManager->getSubscriptionsForUser($account);
        if (empty($user_subscriptions)) {
          continue;
        }

        $push_enabled_users[$uid] = $account->getDisplayName() . ' (' . $account->getAccountName() . ')';
      }
    }

    // Check if the $user_list does have values.
    if (empty($push_enabled_users)) {
      $this->messenger()->addWarning($this->t('There are currently no users subscribed to receive push notifications.'));
      return $form;
    }

    // Start the form for sending push notifications.
    $form['push_notification'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Send a Push Notification'),
      '#open' => FALSE,
    ];
    $form['push_notification']['selected-user'] = [
      '#type' => 'select',
      '#title' => $this->t('To user'),
      '#description' => $this->t('This is a list of users that have given permission to receive notifications.'),
      '#options' => $push_enabled_users,
    ];
    $form['push_notification']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#size' => 47,
      '#default_value' => 'Open Social',
      '#disabled' => TRUE,
      '#description' => $this->t('This will be the <b>title</b> of the Push Notification. <i>(Static value for now)</i>'),
    ];
    $form['push_notification']['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#size' => 47,
      '#maxlength' => 120,
      '#default_value' => 'Enter your message here...',
      '#description' => $this->t('This will be the <b>message</b> of the Push Notification.'),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Push Notification'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('selected-user');
    if (empty($uid)) {
      return;
    }

    $user = User::load($uid);
    if (!$user instanceof UserInterface) {
      $this->messenger()->addError(new TranslatableMarkup("Selected user does not exist."));
      return;
    }

    $push_data = [
      'message' => strip_tags($form_state->getValue('message')),
      'site_name' => $form_state->getValue('title'),
      'url' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    ];

    $pwa_settings = $this->configFactory()->get('social_pwa.settings');
    $icon = $pwa_settings->get('icons.icon');
    if (!empty($icon)) {
      // Get the file id and path.
      $fid = $icon[0];
      /** @var \Drupal\file\Entity\File $file */
      $file = File::load($fid);
      $path = $file->createFileUrl(FALSE);

      $push_data['icon'] = \Drupal::service('file_url_generator')->transformRelative($path);
    }

    $serialized_payload = (new WebPushPayload('legacy', $push_data))->toJson();

    $auth = $this->webPushManager->getAuth();
    $webPush = new WebPush($auth);

    foreach ($this->webPushManager->getSubscriptionsForUser($user) as $subscription) {
      $webPush->queueNotification($subscription, $serialized_payload);
    }

    $outdated_subscriptions = [];
    // Send each notification and check the results.
    // flush() returns a generator that won't actually send all batches until
    // we've consumed all the results of the previous batch.
    /** @var \Minishlink\WebPush\MessageSentReport $push_result */
    foreach ($webPush->flush() as $push_result) {
      if ($push_result->isSubscriptionExpired()) {
        $outdated_subscriptions[] = $push_result->getEndpoint();
      }
    }

    if (!empty($outdated_subscriptions)) {
      $this->webPushManager->removeSubscriptionsForUser($user, $outdated_subscriptions);
    }

    $this->messenger()->addStatus($this->t('Message was successfully sent!'));
  }

}
