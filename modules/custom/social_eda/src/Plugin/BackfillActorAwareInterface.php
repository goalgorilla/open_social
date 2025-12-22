<?php

declare(strict_types=1);

namespace Drupal\social_eda\Plugin;

use Drupal\user\UserInterface;

/**
 * Interface for handlers that can accept an actor to be set.
 *
 * Handlers implementing this interface can have an actor set, allowing them to
 * override the current user context.
 */
interface BackfillActorAwareInterface {

  /**
   * Sets the actor.
   *
   * This allows handlers to override the current user context.
   *
   * @param \Drupal\user\UserInterface|null $user
   *   The user to use as the actor, or NULL to reset.
   */
  public function setActor(?UserInterface $user): void;

  /**
   * Gets the actor.
   *
   * @return \Drupal\user\UserInterface|null
   *   The current actor, or NULL if not set.
   */
  public function getActor(): ?UserInterface;

}
