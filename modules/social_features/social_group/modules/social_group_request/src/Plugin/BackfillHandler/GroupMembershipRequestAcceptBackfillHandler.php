<?php

declare(strict_types=1);

namespace Drupal\social_group_request\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;

/**
 * Backfill handler for group membership request entities with approved status.
 *
 * @BackfillHandler(
 *   id = "group_membership_request_accept",
 *   label = @Translation("Group Membership Request Accept"),
 *   entity_type = "group_content",
 *   handler_service =
 *     "social_group_flexible_group.group_membership.eda_handler",
 *   handler_method = "groupMembershipRequestAccept"
 * )
 */
final class GroupMembershipRequestAcceptBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   *
   * Adds filter by plugin_id and grequest_status to only get approved requests.
   */
  protected function getQuery(string $entity_type, string $bundle, ?int $from = NULL, ?int $to = NULL): QueryInterface {
    $query = parent::getQuery($entity_type, $bundle, $from, $to);
    // Filter by plugin_id to only get group membership requests.
    $query->condition('plugin_id', 'group_membership_request');
    // Filter by group type to only get flexible groups.
    $query->condition('group_type', 'flexible_group');
    // Filter by request status to only get approved requests.
    $query->condition('grequest_status', 'approved');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity): void {
    if (!$entity instanceof GroupRelationshipInterface) {
      throw new \InvalidArgumentException(sprintf(
        'Expected GroupRelationshipInterface, got %s',
        get_class($entity)
      ));
    }

    parent::process($entity);
  }

}
