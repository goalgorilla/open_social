<?php

namespace Drupal\social_tagging\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Group;
use Drupal\social_tagging\SocialTaggingService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'SocialGroupTags' block.
 *
 * @Block(
 *  id = "social_group_tags_block",
 *  admin_label = @Translation("Social Group Tags block"),
 * )
 */
class SocialGroupTagsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    if (!$this->tagService->active() || !$this->tagService->groupActive()) {
      return AccessResult::forbidden();
    }

    // Don't display on group edit.
    if ($this->routeMatch->getRouteName() == 'entity.group.edit_form') {
      return AccessResult::forbidden();
    }

    // Get group from route.
    $group = $this->routeMatch->getParameter('group');

    if ($group instanceof Group) {
      if ($group->hasField('social_tagging')) {
        if (!empty(($group->get('social_tagging')->getValue()))) {
          // We only show the block if the field contains values.
          return AccessResult::allowed();
        }
      }
    }

    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = parent::getCacheTags();
    $group = $this->routeMatch->getParameter('group');

    if ($group instanceof Group) {
      $cache_tags[] = 'group:' . $group->id();
    }

    return $cache_tags;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $group = $this->routeMatch->getParameter('group');

    if ($group instanceof Group) {
      $build['content']['#markup'] = social_tagging_process_tags($group);
    }

    return $build;
  }

}
