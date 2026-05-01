<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;

/**
 * Type class for Actor.user data.
 */
class ActorUser {

  /**
   * Constructs the User type.
   *
   * @param string $id
   *   The UUID.
   * @param string $displayName
   *   The display name.
   */
  public function __construct(
    public readonly string $id,
    public readonly string $displayName,
  ) {}

  /**
   * Get formatted Actor.user output.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   The account that is performing the action.
   *
   * @return ?self
   *   The ActorUser data object.
   */
  public static function fromAccount(?AccountInterface $account): ?self {
    if ($account === NULL) {
      return NULL;
    }

    // Unwrap in case we received the proxy since `uuid` would not be available
    // on the AccountProxyInterface.
    $resolved = $account instanceof AccountProxyInterface
      ? $account->getAccount()
      : $account;

    if ($resolved->isAnonymous()) {
      return NULL;
    }

    // The account proxy can return a UserSession instance which is still not a
    // user. If we don't have a user entity we must load one.
    if (!$resolved instanceof UserInterface) {
      $uid = $resolved->id();
      // Since we're in static methods here we can unfortunately not do proper
      // dependency injection but there's no way to load the user without
      // reaching out to the storage in this way.
      $resolved = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
      if ($resolved === NULL) {
        // The same goes for the logger.
        \Drupal::logger('social_eda')->notice(
          'Got event with actor that has a UID but no user attached.',
          [
            'uid' => $uid,
            'exception' => new \RuntimeException(),
          ],
        );
        return NULL;
      }
    }

    return new self(
      id: (string) $resolved->uuid(),
      displayName: (string) $resolved->getDisplayName(),
    );
  }

}
