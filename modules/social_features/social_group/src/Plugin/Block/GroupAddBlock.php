<?php

namespace Drupal\social_group\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\group\Entity\GroupType;
use Drupal\social_group\SocialGroupHelperService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GroupAddBlock' block.
 *
 * @Block(
 *  id = "group_add_block",
 *  admin_label = @Translation("Group add block"),
 * )
 */
class GroupAddBlock extends BlockBase implements BlockPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The currently active route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The social group helper service.
   *
   * @var \Drupal\social_group\SocialGroupHelperService
   */
  protected $socialGroupHelper;

  /**
   * Constructs a GroupAddBlock object.
   *
   * @param array $configuration
   *   The block configuration.
   * @param string $plugin_id
   *   The ID of the plugin.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\social_group\SocialGroupHelperService $social_group_helper
   *   The social group helper service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The currently active route match object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $current_user,
    SocialGroupHelperService $social_group_helper,
    RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->currentUser = $current_user;
    $this->socialGroupHelper = $social_group_helper;
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
      $container->get('current_user'),
      $container->get('social_group.helper_service'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  public function blockAccess(AccountInterface $account) {
    if (!$this->getUrl()->access($account)) {
      return AccessResult::forbidden();
    }

    $route_user_id = $this->routeMatch->getParameter('user');

    // Show this block only on current user Groups page.
    $can_create_groups = FALSE;
    foreach (GroupType::loadMultiple() as $group_type) {
      $permissions = 'create ' . $group_type->id() . ' group';
      if ($account->hasPermission($permissions)) {
        $can_create_groups = TRUE;
        break;
      }
    }
    if ($account->id() == $route_user_id && $can_create_groups) {
      return AccessResult::allowed();
    }

    // By default, the block is not visible.
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // @todo Add caching when closed groups will be added.
    $url = $this->getUrl();
    $link_options = [
      'attributes' => [
        'class' => [
          'btn',
          'btn-primary',
          'btn-raised',
          'waves-effect',
          'brand-bg-primary',
        ],
      ],
    ];
    $url->setOptions($link_options);

    $build['content'] = Link::fromTextAndUrl(t('Add a group'), $url)
      ->toRenderable();

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    // Vary caching of this block per user.
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(parent::getCacheTags(), ['social_group_add_block:uid:' . $this->currentUser->id()]);
  }

  /**
   * Returns the URL of the button.
   *
   * @return \Drupal\Core\Url
   *   The URL object.
   */
  protected function getUrl() {
    return $this->socialGroupHelper->getGroupsToAddUrl($this->currentUser) ?? Url::fromRoute('entity.group.add_page');
  }

}
