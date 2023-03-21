<?php

namespace Drupal\social_flexible_group_book\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Implements the SFGBController class.
 *
 * @package Drupal\social_flexible_group_book\Controller
 */
class SFGBController extends ControllerBase {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * SFGBController constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('current_route_match'),
    );
  }

  /**
   * Function that checks access to book pages.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account we need to check access for.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   If access is allowed.
   */
  public function booksAccess(AccountInterface $account): AccessResult {
    /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $this->routeMatch->getParameter('group');
    if (!($group instanceof GroupInterface)) {
      return AccessResult::forbidden();
    }

    $is_books_enabled = !$group->hasField('enable_books') || (bool) $group->get('enable_books')->getString();

    return $is_books_enabled ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
