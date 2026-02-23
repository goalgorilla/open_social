<?php

declare(strict_types=1);

namespace Drupal\social_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides the current group from the request/route context.
 */
interface CurrentGroupProviderInterface {

  /**
   * Gets the current group entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) An entity to resolve the group from (e.g. a node).
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   The current group, or NULL if not in a group context.
   */
  public function getCurrentGroup(?EntityInterface $entity = NULL): ?GroupInterface;

}
