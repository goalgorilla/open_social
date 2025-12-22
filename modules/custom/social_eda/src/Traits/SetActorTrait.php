<?php

declare(strict_types=1);

namespace Drupal\social_eda\Traits;

use Drupal\user\UserInterface;

/**
 * Trait for setting the actor.
 *
 * This trait provides a common implementation for handlers that need to
 * override the current user context. For example, during backfill operations.
 */
trait SetActorTrait {

  /**
   * The actor for the current operation.
   *
   * This can represent either the current logged-in user or be overridden
   * to a specific user (e.g., author, owner) during backfill operations.
   *
   * @var \Drupal\user\UserInterface|null
   */
  protected ?UserInterface $currentUser = NULL;

  /**
   * Sets the actor.
   *
   * This allows handlers to override the current user context.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user to use as the actor, or NULL to reset.
   */
  public function setActor(?UserInterface $user): void {
    $this->currentUser = $user;
  }

  /**
   * Gets the actor.
   *
   * @return \Drupal\user\UserInterface|null
   *   The current actor, or NULL if not set.
   */
  public function getActor(): ?UserInterface {
    return $this->currentUser;
  }

}
