<?php

namespace Drupal\socialbase\Plugin\Preprocess;

use Drupal\bootstrap\Plugin\Preprocess\MenuLocalAction as BaseMenuLocalAction;
use Drupal\bootstrap\Utility\Element;
use Drupal\bootstrap\Utility\Variables;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Pre-processes variables for the "menu_local_action" theme hook.
 *
 * @ingroup plugins_preprocess
 *
 * @BootstrapPreprocess("menu_local_action")
 */
class MenuLocalAction extends BaseMenuLocalAction implements ContainerFactoryPluginInterface {

  /**
   * Route Match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    RouteMatchInterface $route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessElement(Element $element, Variables $variables): void {

    parent::preprocessElement($element, $variables);

    $route_names = [
      // Identify the `Add member` button on the manage members page of a group.
      'view.group_manage_members.page_group_manage_members',
      // Identify the `Add enrollee` button on the manage enrollments page.
      'view.event_manage_enrollments.page_manage_enrollments',
      // Identify the `New message` button on the private message page.
      'entity.private_message_thread.canonical',
    ];

    if (in_array($this->routeMatch->getRouteName(), $route_names)) {

      $variables['link']['#options']['attributes']['class'] = 'btn btn-primary btn-raised';
      $variables['attributes']['class'][] = 'margin-bottom-l';

    }

  }

}
