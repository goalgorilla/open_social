<?php

namespace Drupal\social_album\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display images count and a button for adding new images.
 *
 * @Block(
 *   id = "social_album_count_and_add_block",
 *   admin_label = @Translation("Album(s)"),
 * )
 */
class SocialAlbumCountAndAddBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a SocialAlbumCountAndAddBlock object.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
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
    if (!($properties = $this->getProperties())) {
      return [];
    }

    $view = Views::getView($properties['view']['id']);

    if ($this->routeMatch->getRouteName() === 'view.albums.page_overview') {
      $view->setArguments([$this->routeMatch->getRawParameter('user')]);
    }

    $view->execute($properties['view']['display']);

    return [
      'count' => [
        '#markup' => $this->formatPlural(
          $view->total_rows,
          $properties['count']['singular'],
          $properties['count']['plural']
        ),
      ],
      'link' => Link::createFromRoute(
        $properties['link']['text'],
        $properties['link']['route']['name'],
        $properties['link']['route']['parameters'],
        [
          'attributes' => [
            'class' => ['btn', 'btn-primary'],
          ],
        ]
      )->toRenderable(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($this->getProperties()) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Returns block properties for the current route.
   *
   * @return array|null
   *   The renderable data if block is allowed for the current route otherwise
   *   NULL.
   */
  protected function getProperties() {
    $items = [
      'entity.node.canonical' => [
        'view' => [
          'id' => 'album',
          'display' => 'embed_overview',
        ],
        'count' => [
          'singular' => '@count image',
          'plural' => '@count images',
        ],
        'link' => [
          'text' => $this->t('Add images'),
          'route' => [
            'name' => 'entity.post.add_form',
            'parameters' => ['post_type' => 'photo'],
          ],
        ],
      ],
      'view.albums.page_overview' => [
        'view' => [
          'id' => 'albums',
          'display' => 'page_overview',
        ],
        'count' => [
          'singular' => '@count album',
          'plural' => '@count albums',
        ],
        'link' => [
          'text' => $this->t('Create new album'),
          'route' => [
            'name' => 'node.add',
            'parameters' => ['node_type' => 'album'],
          ],
        ],
      ],
    ];

    return $items[$this->routeMatch->getRouteName()] ?? NULL;
  }

}
