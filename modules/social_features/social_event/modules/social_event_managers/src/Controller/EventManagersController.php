<?php

namespace Drupal\social_event_managers\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EventManagersController.
 *
 * @package Drupal\social_event_managers\Controller
 */
class EventManagersController extends ControllerBase {

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
   * Checks access for manage all enrollment page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param \Drupal\Core\Routing\RouteMatch $route_match
   *   Current route.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Check standard and custom permissions.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function access(AccountInterface $account, RouteMatch $route_match) {
    if ($account->hasPermission('manage everything enrollments')) {
      return AccessResult::allowed();
    }
    else {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $route_match->getParameter('node');

      if (!$node instanceof NodeInterface) {
        $node = $this->entityTypeManager->getStorage('node')->load($node);
      }

      if ($node instanceof NodeInterface && $node->bundle() === 'event' && !$node->field_event_managers->isEmpty()) {
        foreach ($node->field_event_managers->getValue() as $value) {
          if ($value && $value['target_id'] === $account->id()) {
            return AccessResult::allowed();
          }
        }
      }
    }

    // If we minimize the amount of tabs we can allow LU that can see this
    // event to see the tab as well.
    if ($node->access('view', $account) && !$account->isAnonymous()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
