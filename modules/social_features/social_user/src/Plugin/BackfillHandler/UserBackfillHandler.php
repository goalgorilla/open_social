<?php

declare(strict_types=1);

namespace Drupal\social_user\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;
use Drupal\user\UserInterface;

/**
 * Backfill handler for user entities.
 *
 * @BackfillHandler(
 *   id = "user",
 *   label = @Translation("User"),
 *   entity_type = "user",
 *   bundle = "user",
 *   handler_service = "social_user.eda_handler",
 *   handler_method = "userCreate"
 * )
 */
final class UserBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   */
  public function getEntityIds(?int $from = NULL, ?int $to = NULL): array {
    // Exclude anonymous user (UID 0) and administrator (UID 1) from backfill.
    $ids = parent::getEntityIds($from, $to);
    unset($ids[0], $ids[1]);
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  protected function getActorFromEntity(EntityInterface $entity): ?UserInterface {
    // For user creation, the actor is the user being created.
    if ($entity instanceof UserInterface) {
      return $entity;
    }
    return NULL;
  }

}
