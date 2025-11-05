<?php

namespace Drupal\social_pwa;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;
use Minishlink\WebPush\Subscription;

/**
 * The web push manager helps in administering web push notification data.
 */
class WebPushManager implements WebPushManagerInterface {

  /**
   * The Drupal state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Drupal user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Create a new WebPushManager instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The Drupal state.
   * @param \Drupal\user\UserDataInterface $userData
   *   Drupal user data.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   *   Drupal logger channel.
   */
  public function __construct(StateInterface $state, UserDataInterface $userData, LoggerChannelFactoryInterface $logger) {
    $this->state = $state;
    $this->userData = $userData;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuth() : array {
    // Get the VAPID keys that were generated before.
    $vapid_keys = $this->state->get('social_pwa.vapid_keys');

    return [
      'VAPID' => [
        'subject' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
        'publicKey' => $vapid_keys['public'],
        'privateKey' => $vapid_keys['private'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionsForUser(UserInterface $user) : array {
    $user_data = $this->userData->get('social_pwa', $user->id(), 'subscription');

    // Make sure that user data is always as an array because get() has a mixed
    // type of return value: array, null, bool.
    if (!is_array($user_data)) {
      $user_data = [];
    }

    // Check if any mandatory field is empty.
    $subscription_data = current($user_data);
    if (
      empty($subscription_data['endpoint'])
      || empty($subscription_data['key'])
      || empty($subscription_data['token'])
    ) {
      $this->logger
        ->get('social_pwa')
        ->error('The subscription data from user id @user-id is wrong, check this user subscription to fix.', [
          '@user-id' => $user->id(),
        ]);

      return [];
    }

    return array_map(
      static function ($subscription) {
        return new Subscription(
          $subscription['endpoint'],
          $subscription['key'],
          $subscription['token']
        );
      },
      $user_data
    );
  }

  /**
   * {@inheritdoc}
   */
  public function removeSubscriptionsForUser(UserInterface $user, array $endpoints) : void {
    $user_data = $this->userData->get('social_pwa', $user->id(), 'subscription');
    if (!is_array($user_data)) {
      $user_data = [];
    }
    $this->userData->set('social_pwa', $user->id(), 'subscription',
      array_filter(
        $user_data,
        static function ($subscription) use ($endpoints) {
          return !in_array($subscription['endpoint'], $endpoints, TRUE);
        }
      )
    );
  }

}
