<?php

namespace Drupal\social_management_overview\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the Social management overview group plugin manager.
 */
class SocialManagementOverviewGroupManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new SocialManagementOverviewGroupManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, MessengerInterface $messenger) {
    parent::__construct('Plugin/SocialManagementOverviewGroup', $namespaces, $module_handler, 'Drupal\social_management_overview\Plugin\SocialManagementOverviewGroupInterface', 'Drupal\social_management_overview\Annotation\SocialManagementOverviewGroup');

    $this->alterInfo('social_management_overview_group_info');
    $this->setCacheBackend($cache_backend, 'social_management_overview_group_plugins');
    $this->messenger = $messenger;
  }

  /**
   * Returns render for management overview groups.
   */
  public function renderGroups(): array {
    $groups = [];
    $overview_groups = $this->getDefinitions();

    // Skip if no overview group was found.
    if (empty($overview_groups)) {
      return [];
    }

    // Sort overview groups by weight.
    $weights = array_column($overview_groups, 'weight');
    array_multisort($weights, SORT_ASC, $overview_groups);

    // Render every overview group.
    foreach ($overview_groups as $id => $overview_group) {
      try {
        /** @var \Drupal\social_management_overview\Plugin\SocialManagementOverviewGroupInterface $plugin */
        $plugin = $this->createInstance($id);
        $children = $plugin->getChildren();
        if (empty($children)) {
          continue;
        }
        $groups[$id] = [
          '#theme' => 'admin_block',
          '#block' => [
            'title' => $plugin->getLabel()->render(),
            'content' => [
              '#theme' => 'admin_block_content',
              '#content' => $children,
            ],
          ],
          '#cache' => [
            'contexts' => [
              'user.permissions',
            ],
          ],
        ];
      }
      catch (PluginException $e) {
        $message = $e->getMessage();
        $this->messenger->addWarning($this->t('@message', ['@message' => $message]));
      }
    }

    if (empty($groups)) {
      return [];
    }

    return $groups;
  }

}
