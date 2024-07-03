<?php

namespace Drupal\entity_access_by_field\EventSubscriber;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\entity\QueryAccess\ConditionGroup;
use Drupal\entity\QueryAccess\QueryAccessEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class QueryAccessSubscriber implements EventSubscriberInterface {

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
  public function nodeQueryAccess(QueryAccessEvent $event) {
    if ($event->getOperation() !== 'view') {
      return;
    }

    $account = $event->getAccount();

    if ($account->hasPermission('administer nodes')) {
      return;
    }

    $conditions = $event->getConditions();

    $accessByVisibility = new ConditionGroup('OR');

    // Content is queryable only if the user has permission.
    $bundle_info_service = \Drupal::service('entity_type.bundle.info');
    foreach ($bundle_info_service->getBundleInfo('node') as $bundle => $info) {
      foreach (['public', 'community', 'group'] as $visibility) {
        if ($account->hasPermission("view node.$bundle.field_content_visibility:$visibility content")) {
          $accessByVisibility->addCondition(
            (new ConditionGroup())
              ->addCondition('bundle', $bundle)
              ->addCondition('field_content_visibility', $visibility)
          );
        }
      }
    }
    $conditions->addCondition($accessByVisibility);

  }

}
