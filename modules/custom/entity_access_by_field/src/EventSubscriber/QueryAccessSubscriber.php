<?php

namespace Drupal\entity_access_by_field\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QueryAccessSubscriber implements EventSubscriberInterface {

  /**
   * Constructs QueryAccessSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection to be used.
   */
  public function __construct(
    protected AccountInterface $currentUser,
    protected Connection $database,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'entity.query_access.node' => 'nodeQueryAccess',
    ];
  }

  /**
   * Modifies the access conditions based on the visibility.
   *
   * @param \Drupal\entity\QueryAccess\QueryAccessEvent $event
   *   The event.
   */
  public function nodeQueryAccess(QueryAccessEvent $event): void {
    if ($event->getOperation() !== 'view') {
      return;
    }

    $account = $event->getAccount();

    if ($account->hasPermission('administer nodes')) {
      return;
    }

    $accessByVisibility = new ConditionGroup('OR');

    // Content is queryable only if the user has permission.
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    foreach ($bundle_info_service->getBundleInfo('node') as $bundle => $info) {
      foreach (['public', 'community'] as $visibility) {
        if ($account->hasPermission("view node.$bundle.field_content_visibility:$visibility content")) {
          $accessByVisibility->addCondition(
            (new ConditionGroup())
              ->addCondition('field_content_visibility', $visibility)
          );
        }
      }
    }

    // Get memberships.
    $memberships = $this->database->select('group_relationship_field_data')
      ->fields('group_relationship_field_data', ['gid'])
      ->condition('type', '%group_membership%', 'LIKE')
      ->condition('entity_id', $account->id());

    // Get group content.
    $nids = $this->database->select('group_relationship_field_data')
      ->fields('group_relationship_field_data', ['entity_id'])
      ->condition('type', 'flexible_group-group_node-%', 'LIKE')
      ->condition('gid', $memberships, 'IN');

    // Check if users has an access as a member of group.
    $accessByVisibility->addCondition(
      (new ConditionGroup())
        ->addCondition('nid', $nids, 'IN')
    );

    // Check if user is author.
    $accessByVisibility->addCondition('uid', $account->id());

    $conditions = $event->getConditions();
    $conditions->addCondition($accessByVisibility);
  }

}
