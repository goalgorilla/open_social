<?php

namespace Drupal\activity_creator;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Activity entity.
 *
 * @see \Drupal\activity_creator\Entity\Activity.
 */
class ActivityAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * PostAccessControlHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($entity_type);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\activity_creator\ActivityInterface $entity */
    switch ($operation) {
      case 'view':
        $recipient = $entity->getRecipient();
        if ($recipient === NULL) {
          // This is simple, the message is not specific for group / user.
          // So we should check, does the user have permission to view entity?
          return $this->returnAccessRelatedObject($entity, $operation, $account);
        }

        $recipient_type = $recipient['0']['target_type'];

        if ($recipient_type === 'user') {
          $recipient_id = $recipient['0']['target_id'];
          // If it is personalised, lets check recipient id vs account id.
          if ($this->checkIfPersonalNotification($entity) === TRUE) {
            return AccessResult::allowedIf($account->id() === $recipient_id);
          }

          // Lets fallback to the related object access permission.
          return $this->returnAccessRelatedObject($entity, $operation, $account);
        }
        return AccessResult::allowedIfHasPermission($account, 'view all published activity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit activity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete activity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add activity entities');
  }

  /**
   * Return access control from the related entity.
   */
  protected function returnAccessRelatedObject(EntityInterface $entity, $operation, $account) {
    $related_object = $entity->get('field_activity_entity')->getValue();
    if (!empty($related_object)) {
      $ref_entity_type = $related_object['0']['target_type'];
      $ref_entity_id = $related_object['0']['target_id'];
      try {
        /** @var \Drupal\Core\Entity\EntityInterface $ref_entity */
        $ref_entity = $this->entityTypeManager->getStorage($ref_entity_type)
          ->load($ref_entity_id);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        return AccessResult::neutral(sprintf('No opinion on access due to: %s', $e->getMessage()));
      }

      return AccessResult::allowedIf($ref_entity->access($operation, $account));
    }
    return AccessResult::neutral('No opinion on access due to: no related object found');
  }

  /**
   * Check if this is a personal notification.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object.
   *
   * @return bool
   *   Returns TRUE if entity is personal notification, FALSE if it isn't.
   */
  protected function checkIfPersonalNotification(EntityInterface $entity) {
    $recipient = $entity->getRecipient();
    $value = FALSE;
    if (!empty($recipient) && $recipient['0']['target_type'] === 'user') {
      // This could be personalised, but first lets check the destinations.
      $destinations = $entity->getDestinations();
      $is_notification = in_array('notifications', $destinations, TRUE);
      // It is only personal if the only destination is notifications.
      if ($is_notification === TRUE && count($destinations) <= 1) {
        $value = TRUE;
      }
    }
    return $value;
  }

}
