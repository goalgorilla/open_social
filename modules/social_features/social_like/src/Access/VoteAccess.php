<?php

namespace Drupal\social_like\Access;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Checks access for vote routing.
 */
class VoteAccess implements AccessInterface {

  /**
   * A custom access check for Vote routing.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $vote_type_id
   *   The vote type ID.
   * @param string $entity_id
   *   The entity ID.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(string $entity_type_id, string $vote_type_id, string $entity_id): AccessResultInterface {
    // @todo This should do dependency injection but unfortunately this code was
    // not built with PHPStan so we fix this 'later'.
    // @phpstan-ignore-next-line
    $referenced_entity = \Drupal::entityTypeManager()->getStorage($entity_type_id)->load($entity_id);
    if (empty($referenced_entity)) {
      return AccessResult::neutral();
    }

    return ($referenced_entity->access('view')) ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
