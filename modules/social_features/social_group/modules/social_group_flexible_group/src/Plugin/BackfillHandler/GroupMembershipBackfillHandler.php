<?php

declare(strict_types=1);

namespace Drupal\social_group_flexible_group\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\group\Entity\GroupMembershipInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for group membership entities.
 *
 * @BackfillHandler(
 *   id = "group_membership",
 *   label = @Translation("Group Membership"),
 *   entity_type = "group_content",
 *   handler_service =
 *     "social_group_flexible_group.group_membership.eda_handler",
 *   handler_method = "groupMembershipCreate"
 * )
 */
final class GroupMembershipBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   *
   * Adds filter by plugin_id to only get group memberships (excluding
   * requests and invites).
   */
  protected function getQuery(string $entity_type, string $bundle, ?int $from = NULL, ?int $to = NULL): QueryInterface {
    $query = parent::getQuery($entity_type, $bundle, $from, $to);
    // Filter by plugin_id to only get group memberships.
    $query->condition('plugin_id', 'group_membership');
    // Filter by group type to only get flexible groups.
    $query->condition('group_type', 'flexible_group');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity): void {
    if (!$entity instanceof GroupMembershipInterface) {
      throw new \InvalidArgumentException(sprintf(
        'Expected GroupMembershipInterface, got %s',
        get_class($entity)
      ));
    }

    parent::process($entity);
  }

}
