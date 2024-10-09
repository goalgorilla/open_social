<?php

declare(strict_types=1);

namespace Drupal\social_node\EventSubscriber;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\social_node\Event\NodeQueryAccessEvent;
use Drupal\social_node\Event\SocialNodeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NodeQueryAccessAlterSubscriber implements EventSubscriberInterface {

  /**
   * Constructs NodeQueryAccessAlterSubscriber.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    private readonly EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[SocialNodeEvents::NODE_ACCESS_QUERY_ALTER][] = ['alterQueryConditions'];
    return $events;
  }

  /**
   * Alter query conditions.
   *
   * @param \Drupal\social_node\Event\NodeQueryAccessEvent $event
   *   The event object.
   *
   * @throws \Exception
   */
  public function alterQueryConditions(NodeQueryAccessEvent $event): void {
    $fields = $this->entityFieldManager->getFieldStorageDefinitions('node');
    if (!isset($fields['field_content_visibility'])) {
      return;
    }

    /** @var \Drupal\field\Entity\FieldStorageConfig $field */
    $field_storage = $fields['field_content_visibility'];

    // Gets allowed values from function if exists.
    $function = $field_storage->getSetting('allowed_values_function');
    $visibilities = !empty($function)
      ? array_keys((array) $function($field_storage))
      : array_keys((array) $field_storage->getSetting('allowed_values'));

    if (empty($visibilities)) {
      return;
    }

    $target_visibilities = array_intersect(['public', 'community'], $visibilities);

    $account = $event->account();
    $or = $event->getConditions();

    $node_table = $event->ensureNodeDataTable();
    $visibility_table = $event->ensureJoinNodeField('field_content_visibility');

    // Get all node types where we have visibility field.
    $field_storage = FieldStorageConfig::loadByName('node', 'field_content_visibility');
    $bundles = $field_storage->getBundles();

    foreach ($bundles as $bundle) {
      foreach ($target_visibilities as $visibility) {
        if ($account->hasPermission("view node.$bundle.field_content_visibility:$visibility content")) {
          $or->condition(
            $event->query()->andConditionGroup()
              ->condition("$node_table.type", $bundle)
              ->condition("$visibility_table.field_content_visibility_value", $visibility)
          );
        }
      }
    }
  }

}

