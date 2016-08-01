<?php

/**
 * @file
 * Contains \Drupal\entity\Access\EntityRevisionRouteAccessChecker
 */

namespace Drupal\entity\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Checks access to a entity revision.
 */
class EntityRevisionRouteAccessChecker implements AccessInterface {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Stores calculated access check results.
   *
   * @var array
   */
  protected $accessCache = array();

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Creates a new EntityRevisionRouteAccessChecker instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, AccountInterface $account, Request $request = NULL) {
    if (empty($request)) {
      $request = $this->requestStack->getCurrentRequest();
    }

    $operation = $route->getRequirement('_entity_access_revision');
    list(, $operation) = explode('.', $operation, 2);

    if ($operation === 'list') {
      $_entity = $request->attributes->get('_entity', $request->attributes->get($route->getOption('entity_type_id')));
      return AccessResult::allowedIf($this->checkAccess($_entity, $account, $operation))->cachePerPermissions();
    }
    else {
      $_entity_revision = $request->attributes->get('_entity_revision');
      return AccessResult::allowedIf($_entity_revision && $this->checkAccess($_entity_revision, $account, $operation))->cachePerPermissions();
    }
  }

  protected function checkAccess(ContentEntityInterface $entity, AccountInterface $account, $operation = 'view') {
    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity->getEntityTypeId();
    $entity_access = $this->entityTypeManager->getAccessControlHandler($entity_type_id);

    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
    $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);

    $map = [
      'view' => "view all $entity_type_id revisions",
      'list' => "view all $entity_type_id revisions",
      'update' => "revert all $entity_type_id revisions",
      'delete' => "delete all $entity_type_id revisions",
    ];
    $bundle = $entity->bundle();
    $type_map = [
      'view' => "view $entity_type_id $bundle revisions",
      'list' => "view $entity_type_id $bundle revisions",
      'update' => "revert $entity_type_id $bundle revisions",
      'delete' => "delete $entity_type_id $bundle revisions",
    ];

    if (!$entity || !isset($map[$operation]) || !isset($type_map[$operation])) {
      // If there was no node to check against, or the $op was not one of the
      // supported ones, we return access denied.
      return FALSE;
    }

    // Statically cache access by revision ID, language code, user account ID,
    // and operation.
    $langcode = $entity->language()->getId();
    $cid = $entity->getRevisionId() . ':' . $langcode . ':' . $account->id() . ':' . $operation;

    if (!isset($this->accessCache[$cid])) {
      // Perform basic permission checks first.
      if (!$account->hasPermission($map[$operation]) && !$account->hasPermission($type_map[$operation]) && !$account->hasPermission('administer nodes')) {
        $this->accessCache[$cid] = FALSE;
        return FALSE;
      }

      if (($admin_permission = $entity_type->getAdminPermission()) && $account->hasPermission($admin_permission)) {
        $this->accessCache[$cid] = TRUE;
      }
      else {
        // First check the access to the default revision and finally, if the
        // node passed in is not the default revision then access to that, too.
        $this->accessCache[$cid] = $entity_access->access($entity_storage->load($entity->id()), $operation, $account) && ($entity->isDefaultRevision() || $entity_access->access($entity, $operation, $account));
      }
    }

    return $this->accessCache[$cid];
  }


  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  protected function countDefaultLanguageRevisions(ContentEntityInterface $entity, EntityStorageInterface $entity_storage) {
    $entity_type = $entity->getEntityType();
    $count = $entity_storage->getQuery()
      ->allRevisions()
      ->condition($entity_type->getKey('id'), $entity->id())
      ->condition($entity_type->getKey('default_langcode'), 1)
      ->count()
      ->execute();
    return $count;
  }

  /**
   * Resets the access cache.
   *
   * @return $this
   */
  public function resetAccessCache() {
    $this->accessCache = [];
    return $this;
  }

}
