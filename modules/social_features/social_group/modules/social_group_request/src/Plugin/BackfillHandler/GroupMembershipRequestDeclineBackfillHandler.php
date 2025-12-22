<?php

declare(strict_types=1);

namespace Drupal\social_group_request\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;
use Drupal\user\UserInterface;

/**
 * Backfill handler for group membership request entities with rejected status.
 *
 * @BackfillHandler(
 *   id = "group_membership_request_decline",
 *   label = @Translation("Group Membership Request Decline"),
 *   entity_type = "group_content",
 *   handler_service =
 *     "social_group_flexible_group.group_membership.eda_handler",
 *   handler_method = "groupMembershipRequestDecline"
 * )
 */
final class GroupMembershipRequestDeclineBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   *
   * Adds filter by plugin_id and grequest_status to only get rejected requests.
   */
  protected function getQuery(string $entity_type, string $bundle, ?int $from = NULL, ?int $to = NULL): QueryInterface {
    $query = parent::getQuery($entity_type, $bundle, $from, $to);
    // Filter by plugin_id to only get group membership requests.
    $query->condition('plugin_id', 'group_membership_request');
    // Filter by group type to only get flexible groups.
    $query->condition('group_type', 'flexible_group');
    // Filter by request status to only get rejected requests.
    $query->condition('grequest_status', 'rejected');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function getActorFromEntity(EntityInterface $entity): ?UserInterface {
    // For request decline, the actor is the user who declined (last updated).
    if ($entity instanceof GroupRelationshipInterface) {
      if ($entity->hasField('grequest_updated_by') && !$entity->get('grequest_updated_by')->isEmpty()) {
        $updated_by = $entity->get('grequest_updated_by')->entity;
        return $updated_by instanceof UserInterface && !$updated_by->isAnonymous() ? $updated_by : NULL;
      }
    }
    return parent::getActorFromEntity($entity);
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
