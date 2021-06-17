<?php

namespace Drupal\social_group;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagServiceInterface;
use Drupal\group\Entity\GroupContentInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Class GroupMuteNotify.
 *
 * Helps to work with mute/unmute group notifications.
 *
 * @package Drupal\social_group
 */
class GroupMuteNotify {

  /**
   * Flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * GroupMuteNotify constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   Flag service.
   */
  public function __construct(
    FlagServiceInterface $flag_service
  ) {
    $this->flagService = $flag_service;
  }

  /**
   * Check if group notifications are muted.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return bool
   *   TRUE if a user muted notifications for a group.
   */
  public function groupNotifyIsMuted(GroupInterface $group, AccountInterface $account): bool {
    $flaggings = $this->flagService->getAllEntityFlaggings($group, $account);

    return !empty($flaggings);
  }

  /**
   * Get group by content.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\group\Entity\GroupInterface|null
   *   Returns the group object.
   */
  public function getGroupByContent(EntityInterface $entity = NULL): ?GroupInterface {
    // Ensure the $entity is not NULL.
    if ($entity == NULL) {
      return NULL;
    }

    if ($entity instanceof GroupContentInterface) {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $entity->getGroup();
    }
    elseif ($entity->getEntityTypeId() === 'post') {
      /** @var \Drupal\social_post\Entity\Post $entity */
      if ($entity->hasField('field_recipient_group') && !$entity->get('field_recipient_group')->isEmpty()) {
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $reference_item **/
        $reference_item = $entity->get('field_recipient_group')->first();
        /** @var \Drupal\Core\Entity\Plugin\DataType\EntityReference $entity_reference **/
        $entity_reference = $reference_item->get('entity');
        /** @var \Drupal\Core\Entity\Plugin\DataType\EntityAdapter $entity_adapter **/
        $entity_adapter = $entity_reference->getTarget();
        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = $entity_adapter->getValue();
      }
    }

    return $group ?? NULL;
  }

}
