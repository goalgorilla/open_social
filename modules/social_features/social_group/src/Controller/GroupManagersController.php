<?php

namespace Drupal\social_group\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GroupManagersController.
 *
 * @package Drupal\social_group\Controller
 */
class GroupManagersController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
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
   * Checks access for group management page.
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
    // CM+ are allowed!
    if ($account->hasPermission('administer members')) {
      return AccessResult::allowed();
    }

    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = _social_group_get_current_group();

    // Lets allow group managers as well.
    if ($group instanceof GroupInterface && $group->hasPermission('administer members', $account)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

}
