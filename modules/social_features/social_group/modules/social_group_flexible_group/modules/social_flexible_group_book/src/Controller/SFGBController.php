<?php

namespace Drupal\social_flexible_group_book\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\group\Entity\GroupInterface;

/**
 * Implements the SFGBController class.
 *
 * @package Drupal\social_discussion_group\Controller
 */
class SFGBController extends ControllerBase {

  /**
   * The request.
   */
  protected RequestStack $requestStack;

  /**
   * SFGBController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('request_stack'),
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
    $group = _social_group_get_current_group();
    if (!($group instanceof GroupInterface)) {
      AccessResult::forbidden();
    }

    $is_books_enabled = !$group->hasField('enable_books') || (bool) $group->get('enable_books')->getString();

    return $is_books_enabled ? AccessResult::allowed() : AccessResult::forbidden();
  }

}
