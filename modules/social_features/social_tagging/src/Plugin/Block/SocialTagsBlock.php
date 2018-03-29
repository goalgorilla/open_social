<?php

namespace Drupal\social_tagging\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\social_tagging\SocialTaggingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SocialTags' block.
 *
 * @Block(
 *  id = "social_tags_block",
 *  admin_label = @Translation("Social Tags block"),
 * )
 */
class SocialTagsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The route match.
   *
   * @var \Drupal\social_tagging\SocialTaggingService
   */
  protected $tagService;

  /**
   * EventAddBlock constructor.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match.
   * @param \Drupal\social_tagging\SocialTaggingService $tagging_service
   *   The tag service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $routeMatch, SocialTaggingService $tagging_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->tagService = $tagging_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('social_tagging.tag_service')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Logic to display the block in the sidebar.
   */
  protected function blockAccess(AccountInterface $account) {
    // If tagging is off, deny access always.
    if (!$this->tagService->active()) {
      return AccessResult::forbidden();
    }

    $route_name = $this->routeMatch->getRouteName();

    if ($route_name == 'entity.node.canonical') {
      $node = $this->routeMatch->getParameter('node');

      if ($node instanceof Node) {
        if ($node->hasField('social_tagging')) {
          if (!empty(($node->get('social_tagging')->getValue()))) {
            // We only show the block if the field contains values.
            return AccessResult::allowed();
          }
        }
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = parent::getCacheContexts();
    $cache_contexts[] = 'url';
    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof Node) {
      $cache_tags[] = 'node:' . $node->id();
    }

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $node = $this->routeMatch->getParameter('node');

    if ($node instanceof Node) {
      $build['content']['#markup'] = social_tagging_process_tags($node);
    }

    return $build;
  }

}
