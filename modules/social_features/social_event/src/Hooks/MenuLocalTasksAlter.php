<?php

declare(strict_types=1);

namespace Drupal\social_event\Hooks;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;
use Drupal\hux\Attribute\Alter;
use Drupal\social_event\Entity\Node\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Hooks for menu local tasks alteration.
 */
final class MenuLocalTasksAlter implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * View IDs for enrollment tabs.
   */
  private const VIEW_EVENT_ENROLLMENTS_TAB_ID = 'views_view:view.event_enrollments.view_enrollments';
  private const VIEW_MANAGE_ENROLLMENTS_TAB_ID = 'views_view:view.manage_enrollments.page';
  private const VIEW_MANAGERS_TAB_ID = 'views_view:view.managers.view_managers';
  private const VIEW_EVENT_MANAGE_ENROLLMENT_TAB_ID = 'views_view:view.event_manage_enrollments.page_manage_enrollments';

  /**
   * Constructs a new MenuLocalTasksAlter.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route matches.
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_route_match'),
    );
  }

  /**
   * Implements hook_menu_local_tasks_alter().
   *
   * We want to do the following:
   * - Hiding enrollment tabs when enrollment is disabled for the event
   * - Consolidating enrollment tabs for event managers/organizers
   * - Adjusting tab titles based on user permissions
   * - Ensuring manager tabs are only shown on event pages.
   */
  #[Alter('menu_local_tasks')]
  public function alter(array &$data, string $route_name): void {
    // Get the event-related routes.
    $routes_to_check = _social_event_menu_local_tasks_routes();
    // Certain tabs should be hidden on other nodes.
    $showTabs = FALSE;

    if (in_array($route_name, $routes_to_check)) {
      // Get the current node from route parameters.
      $node = $this->routeMatch->getParameter('node');
      if (!is_null($node) && (!$node instanceof Node)) {
        $node = Node::load($node);
      }

      // Only proceed if we have a valid event node.
      if ($node instanceof Event) {
        $showTabs = TRUE;

        // We hide Guest enrollments as we show them in the management tab.
        if (isset($data['tabs'][0][self::VIEW_MANAGE_ENROLLMENTS_TAB_ID])) {
          unset($data['tabs'][0][self::VIEW_MANAGE_ENROLLMENTS_TAB_ID]);
        }

        if (isset($data['tabs'][0][self::VIEW_EVENT_ENROLLMENTS_TAB_ID])) {
          unset($data['tabs'][0][self::VIEW_EVENT_ENROLLMENTS_TAB_ID]);
        }

        // Check if the current user is a manager or organizer of the event.
        $is_manager_or_organizer = social_event_manager_or_organizer($node);

        // For non-managers, change the title of the management tab to be more
        // generic.
        if (!$is_manager_or_organizer) {
          $data['tabs'][0][self::VIEW_EVENT_MANAGE_ENROLLMENT_TAB_ID]['#link']['title'] = $this->t('Enrollments');
        }
      }
    }

    // These tabs should not be shown on any pages.
    if (!$showTabs) {
      unset($data['tabs'][0][self::VIEW_MANAGERS_TAB_ID]);
      unset($data['tabs'][0][self::VIEW_EVENT_ENROLLMENTS_TAB_ID]);
      unset($data['tabs'][0][self::VIEW_EVENT_MANAGE_ENROLLMENT_TAB_ID]);
    }
  }

}
