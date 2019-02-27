<?php

namespace Drupal\social_event_managers\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
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
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * SocialTopicController constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(RouteMatchInterface $routeMatch, EntityTypeManagerInterface $entityTypeManager) {
    $this->routeMatch = $routeMatch;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Checks access for manage all enrollment page.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Check standard and custom permissions.
   */
  public function access(AccountInterface $account) {
    if ($account->hasPermission('manage everything enrollments')) {
      return AccessResult::allowed();
    }
    else {
      /** @var \Drupal\node\NodeInterface $node */
      $node = $this->routeMatch->getParameter('node');

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

      // If we minimize the amount of tabs we can allow LU that can see this
      // event to see the tab as well.
      // Set author of event as event organiser automatically.
      if (social_event_managers_minimize_tabs()) {
        if ($node->access('view', $account)) {
          return AccessResult::allowed();
        }
      }

    }

    return AccessResult::forbidden();
  }

}
