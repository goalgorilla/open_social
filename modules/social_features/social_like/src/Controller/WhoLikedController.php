<?php

namespace Drupal\social_like\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WhoLikedController.
 *
 * @package Drupal\social_like\Controller
 */
class WhoLikedController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SocialTopicController constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Checks access for who liked page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   Current route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Check standard and custom permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function access(AccountInterface $account, RouteMatch $route_match): AccessResult {
    // Get parameters to load the liked entity from views route.
    $entity_type_id = $route_match->getParameter('arg_0');
    $entity_id = $route_match->getParameter('arg_1');

    // We need both for proper access checking.
    if (!$entity_id || !$entity_type_id) {
      return AccessResult::neutral();
    }

    try {
      // See if we can load the entity from the storage.
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($entity_id);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // Return neutral if we don't have a Plugin for that.
      return AccessResult::neutral();
    }

    // Return access when the user has access to the content.
    if ($entity && $entity->access('view', $account)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
