<?php

namespace Drupal\social_book\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'GroupAddBookBlock' block.
 *
 * @Block(
 *  id = "group_add_book_block",
 *  admin_label = @Translation("Group add book block"),
 * )
 */
class GroupAddBookBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GroupAddBookBlock constructor.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   *
   * Custom access logic to display the block.
   */
  public function blockAccess(AccountInterface $account) {
    if ($this->moduleHandler->moduleExists('social_group')) {
      $group = _social_group_get_current_group();
    }
    else {
      $group = NULL;
    }

    if (is_object($group) && $group->hasPermission('create group_node:book entity', $account) && $account->hasPermission("create book content")) {
      if ($group->getGroupType()->id() === 'public_group') {
        $config = $this->configFactory->get('entity_access_by_field.settings');
        if ($config->get('disable_public_visibility') === 1 && !$account->hasPermission('override disabled public visibility')) {
          return AccessResult::forbidden();
        }
      }
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

    if ($this->moduleHandler->moduleExists('social_group')) {
      $group = _social_group_get_current_group();
    }
    else {
      $group = NULL;
    }

    if (is_object($group)) {
      $url = Url::fromUserInput("/group/{$group->id()}/content/create/group_node:book");

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

      $build['content'] = Link::fromTextAndUrl(t('Create book page'), $url)->toRenderable();

      // Cache.
      $build['#cache']['contexts'][] = 'url.path';
      $build['#cache']['tags'][] = 'group:' . $group->id();
    }

    return $build;
  }

}
