<?php

namespace Drupal\social_album\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
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
   * The templates for labels with the number of entities.
   */
  const ITEM = [
    'count' => [
      'singular' => '@count album',
      'plural' => '@count albums',
    ],
  ];

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
    $build = [];

    if (!($properties = $this->getProperties())) {
      return $build;
    }

    $view = Views::getView('albums');
    $view->setArguments([$this->routeMatch->getRawParameter($properties['type'])]);
    $view->execute($properties['display']);

    $build['count'] = [
      '#markup' => $this->formatPlural(
        $view->total_rows,
        $properties['count']['singular'],
        $properties['count']['plural']
      ),
    ];

    $url = Url::fromRoute(
      $properties['link']['route']['name'],
      $properties['link']['route']['parameters'],
      [
        'attributes' => [
          'class' => ['btn', 'btn-primary'],
        ],
      ]
    );

    if ($url->access()) {
      $build['link'] = Link::fromTextAndUrl($properties['link']['text'], $url)->toRenderable();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIf($this->getProperties() !== NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();

    if ($this->getProperties()) {
      $cache_contexts = Cache::mergeContexts($cache_contexts, ['url']);
    }

    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();

    if ($properties = $this->getProperties()) {
      $type = $properties['type'];
      $tags = Cache::mergeTags($tags, [$type . ':' . $this->routeMatch->getRawParameter($type)]);
    }

    return $tags;
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
        'type' => 'node',
        'display' => 'embed_album_overview',
        'count' => [
          'singular' => '@count image',
          'plural' => '@count images',
        ],
        'link' => [
          'text' => $this->t('Add images'),
          'route' => [
            'name' => 'social_album.post',
            'parameters' => [
              'node' => $this->routeMatch->getRawParameter('node'),
            ],
          ],
        ],
      ],
      'view.albums.page_albums_overview' => [
        'type' => 'user',
        'display' => 'page_albums_overview',
        'link' => [
          'text' => $this->t('Create new album'),
          'route' => [
            'name' => 'node.add',
            'parameters' => ['node_type' => 'album'],
          ],
        ],
      ] + self::ITEM,
      'view.albums.page_group_albums_overview' => [
        'type' => 'group',
        'display' => 'page_group_albums_overview',
        'link' => [
          'text' => $this->t('Create new album'),
          'route' => [
            'name' => 'social_album.add',
            'parameters' => [
              'group' => $this->routeMatch->getRawParameter('group'),
            ],
          ],
        ],
      ] + self::ITEM,
    ];

    return $items[$this->routeMatch->getRouteName()] ?? NULL;
  }

}
