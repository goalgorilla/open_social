<?php

namespace Drupal\social_group_default_route;

use Drupal\Component\Discovery\YamlDiscovery as YamlDiscoveryComponent;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;

/**
 * Provides the default group landing tabs using YML as primary definition.
 */
class GroupLandingTabManager extends DefaultPluginManager implements GroupLandingTabManagerInterface {

  use StringTranslationTrait;

  /**
   * The object that discovers plugins managed by this manager.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $discovery;

  /**
   * The YAML discovery class to find all .group_landing_tabs.yml files.
   *
   * @var \Drupal\Component\Discovery\YamlDiscovery
   */
  protected $yamlDiscovery;

  /**
   * Provides default values for a group landing tab definition.
   *
   * @var array
   */
  protected $defaults = [
    // (required) The name of the route to link to.
    'route_name' => '',
    // (required) The group landing tab title.
    'title' => '',
    // The weight of the tab.
    'weight' => NULL,
    // The group membership: member, non-member or all.
    'membership' => 'all',
    // The group types for witch tab will be available.
    'group_types' => [],
    // Conditions by group fields.
    'conditions' => [],
  ];

  /**
   * GroupLandingTabManager constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    $this->alterInfo('group_landing_tabs');
    $this->setCacheBackend($cache_backend, 'group_landing_tabs_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface|ContainerDerivativeDiscoveryDecorator {
    // @phpstan-ignore-next-line
    if (!isset($this->discovery)) {
      $yaml_discovery = new YamlDiscovery('group_landing_tabs', $this->moduleHandler->getModuleDirectories());
      $yaml_discovery->addTranslatableProperty('title', 'title_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($yaml_discovery);
    }
    return $this->discovery;
  }

  /**
   * Gets the YAML discovery.
   *
   * @return \Drupal\Component\Discovery\YamlDiscovery
   *   The YAML discovery.
   */
  protected function getYamlDiscovery(): YamlDiscoveryComponent {
    // @phpstan-ignore-next-line
    if (!isset($this->yamlDiscovery)) {
      $this->yamlDiscovery = new YamlDiscoveryComponent('group_landing_tabs', $this->moduleHandler->getModuleDirectories());
    }
    return $this->yamlDiscovery;
  }

  /**
   * Get all available group lending tabs.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group object.
   *
   * @return array
   *   The array of all group landing tabs.
   */
  protected function getAllGroupManagementTabs(GroupInterface $group): array {
    $all_tabs = [];
    foreach ($this->getYamlDiscovery()->findAll() as $tabs) {
      foreach ($tabs as $name => $data) {
        $tab_data = [
          'title' => isset($data['title']) ? $this->t('@title', ['@title' => $data['title']]) : NULL,
          'route_name' => $data['route_name'] ?? NULL,
          'weight' => $data['weight'] ?? NULL,
          'membership' => $data['membership'] ?? NULL,
          'group_types' => $data['group_types'] ?? NULL,
          'conditions' => $data['conditions'] ?? NULL,
        ];
        // Skip if current tab isn't available for current group.
        if (is_array($tab_data['group_types']) && !in_array($group->bundle(), $tab_data['group_types'])) {
          continue;
        }
        // Skip by conditions.
        $skip_by_conditions = FALSE;
        if (is_array($tab_data['conditions'])) {
          $skip_by_conditions = TRUE;
          foreach ($tab_data['conditions'] as $group_field => $value) {
            if ($group->hasField($group_field) && $group->get($group_field)->getString() === $value) {
              $skip_by_conditions = FALSE;
            }
          }

        }

        if ($skip_by_conditions) {
          continue;
        }

        if ($tab_data['title'] && $tab_data['route_name']) {
          $membership = $tab_data['membership'];

          switch ($membership) {
            case self::NON_MEMBER:
            case self::MEMBER:
              $all_tabs[$membership][$name] = $tab_data;
              break;

            case self::ALL:
              $all_tabs[self::NON_MEMBER][$name] = $tab_data;
              $all_tabs[self::MEMBER][$name] = $tab_data;
              break;
          }
        }
      }
    }

    return $all_tabs;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableLendingTabs(GroupInterface $group, string $type): array {
    $all_tabs = $this->getAllGroupManagementTabs($group);
    $members_tabs = $all_tabs[$type];
    // Sort by weight.
    uasort($members_tabs, function ($a, $b) {
      return $a['weight'] <=> $b['weight'];
    });
    $result = [];

    foreach ($members_tabs as $tab) {
      $result[$tab['route_name']] = $tab['title'];
    }

    return $result;
  }

}
