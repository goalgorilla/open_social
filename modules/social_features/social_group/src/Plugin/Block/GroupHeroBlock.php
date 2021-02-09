<?php

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GroupHeroBlock' block.
 *
 * @Block(
 *  id = "group_hero_block",
 *  admin_label = @Translation("Group hero block"),
 *  context_definitions = {
 *    "group" = @ContextDefinition("entity:group", required = FALSE)
 *  }
 * )
 */
class GroupHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Creates a GroupHeroBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
  public function build() {
    $build = [];

    $group = _social_group_get_current_group();

    if (!empty($group)) {
      // Content.
      $content = \Drupal::entityTypeManager()
        ->getViewBuilder('group')
        ->view($group, 'hero');

      $build['content'] = $content;
      // Cache tags.
      $build['#cache']['tags'][] = 'group_block:' . $group->id();
    }
    // Cache contexts.
    $build['#cache']['contexts'][] = 'url.path';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $current_route = $this->routeMatch->getRouteName();

    if ($current_route == 'entity.group_content.create_form') {
      return AccessResult::forbidden();
    }

    return parent::blockAccess($account);
  }

}
