<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\user\UserInterface;

/**
 * Type class for Actor data.
 */
class Actor {

  /**
   * Constructs the Actor type.
   *
   * @param \Drupal\social_eda\Types\Application|null $application
   *   The application actor.
   * @param \Drupal\social_eda\Types\User|null $user
   *   The user actor.
   */
  public function __construct(
    public readonly ?Application $application,
    public readonly ?User $user,
  ) {}

  /**
   * Get Actor from context (current guser and route name).
   *
   * @param \Drupal\user\UserInterface|null $currentUser
   *   The current logged-in user.
   * @param string $routeName
   *   The current route name.
   *
   * @return self
   *   The Actor data object.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function fromContext(?UserInterface $currentUser, string $routeName): self {
    $application = NULL;
    $user = NULL;

    if ($currentUser instanceof UserInterface) {
      $user = User::fromEntity($currentUser);
    }

    if ($routeName == 'entity.ultimate_cron_job.run') {
      $application = Application::fromId('cron');
    }

    return new self(
      application: $application,
      user: $user,
    );
  }

}
