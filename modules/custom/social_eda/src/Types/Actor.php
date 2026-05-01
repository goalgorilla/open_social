<?php

declare(strict_types=1);

namespace Drupal\social_eda\Types;

use Drupal\Core\Session\AccountInterface;

/**
 * Type class for Actor data.
 */
class Actor {

  /**
   * Constructs the Actor type.
   *
   * @param \Drupal\social_eda\Types\Application|null $application
   *   The application actor.
   * @param \Drupal\social_eda\Types\ActorUser|null $user
   *   The user actor.
   */
  public function __construct(
    public readonly ?Application $application,
    public readonly ?ActorUser $user,
  ) {}

  /**
   * Get Actor from context (current guser and route name).
   *
   * @param \Drupal\Core\Session\AccountInterface|null $currentUser
   *   The current logged-in user.
   * @param string $routeName
   *   The current route name.
   *
   * @return self
   *   The Actor data object.
   */
  public static function fromContext(?AccountInterface $currentUser, string $routeName): self {
    $application = NULL;

    if ($routeName == 'entity.ultimate_cron_job.run') {
      $application = Application::fromId('cron');
    }

    return new self(
      application: $application,
      user: ActorUser::fromAccount($currentUser),
    );
  }

}
