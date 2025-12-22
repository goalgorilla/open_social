<?php

declare(strict_types=1);

namespace Drupal\social_group_invite\Plugin\BackfillHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ginvite\Plugin\Group\Relation\GroupInvitation;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\social_eda\Plugin\BackfillHandlerBase;
use Drupal\user\UserInterface;

/**
 * Backfill handler for group membership invite entities with declined status.
 *
 * @BackfillHandler(
 *   id = "group_membership_invite_decline",
 *   label = @Translation("Group Membership Invite Decline"),
 *   entity_type = "group_content",
 *   handler_service =
 *     "social_group_flexible_group.group_membership.eda_handler",
 *   handler_method = "groupMembershipInviteDecline"
 * )
 */
final class GroupMembershipInviteDeclineBackfillHandler extends BackfillHandlerBase {

  /**
   * {@inheritdoc}
   *
   * Adds filter by plugin_id and invitation_status for rejected invites.
   */
  protected function getQuery(string $entity_type, string $bundle, ?int $from = NULL, ?int $to = NULL): QueryInterface {
    $query = parent::getQuery($entity_type, $bundle, $from, $to);
    // Filter by plugin_id to only get group invitations.
    $query->condition('plugin_id', 'group_invitation');
    // Filter by group type to only get flexible groups.
    $query->condition('group_type', 'flexible_group');
    // Filter by invitation status to only get rejected invites.
    $query->condition('invitation_status', GroupInvitation::INVITATION_REJECTED);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  protected function getActorFromEntity(EntityInterface $entity): ?UserInterface {
    // For group invite decline, the actor is the invitee declining the invite.
    if ($entity instanceof GroupRelationshipInterface) {
      $user = $entity->getEntity();
      return $user instanceof UserInterface && !$user->isAnonymous() ? $user : NULL;
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
