<?php

declare(strict_types=1);

namespace Drupal\social_node\QueryAccess;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\social_node\Event\NodeQueryAccessEvent;
use Drupal\social_node\Event\SocialNodeEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines a class for altering node entity queries.
 */
class NodeEntityQueryAlter implements ContainerInjectionInterface {

  use SocialNodeEnsureTablesTrait;

  /**
   * The query to alter.
   */
  protected SelectInterface $query;

  /**
   * The query cacheable metadata.
   */
  protected CacheableMetadata $cacheableMetadata;

  /**
   * Constructs a new RoleVisibilityNodeEntityQueryAlter object.
   *
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entities field manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity bundle info provider.
   */
  public function __construct(
    private readonly AccountInterface $currentUser,
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly EventDispatcherInterface $eventDispatcher,
    private readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,

  ) {
    $this->cacheableMetadata = new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): NodeEntityQueryAlter {
    return new static(
      $container->get('current_user'),
      $container->get('entity_field.manager'),
      $container->get('event_dispatcher'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * Alter the entity query for "node" entity type.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query object.
   *
   * @throws \Exception
   */
  public function alterQuery(SelectInterface $query): void {
    if ($this->currentUser->hasPermission('bypass node query access')) {
      return;
    }

    $this->attachQuery($query);

    // Get all node bundles we have on a platform.
    $node_bundles = array_keys($this->entityTypeBundleInfo->getBundleInfo('node'));

    // Make sure we have joined a node data table.
    $node_table = $this->ensureNodeDataTable();

    $or = $this->query->orConditionGroup();

    foreach ($node_bundles as $bundle) {
      // Owner should have access to own nodes.
      // "Node" module doesn't provide the permission to
      // "view own topic content", so we check if user can edit node.
      if ($this->currentUser->hasPermission("edit own $bundle content")) {
        $or->condition(
          $this->query->andConditionGroup()
            ->condition("$node_table.type", $bundle)
            ->condition("$node_table.uid", $this->currentUser->id())
        );
      }
    }

    // Allow altering the access conditions.
    $event = new NodeQueryAccessEvent($query, $or, $this->currentUser);
    $this->eventDispatcher->dispatch($event, SocialNodeEvents::NODE_QUERY_ACCESS_ALTER);

    if (!count(Element::children($or->conditions()))) {
      // If no access rule was met, we should hardly restrict access.
      $or->alwaysFalse();
    }

    $this->query->condition($or);
  }

}
