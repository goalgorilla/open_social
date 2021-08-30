<?php

namespace Drupal\social_management_overview\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Social management overview group plugins.
 */
abstract class SocialManagementOverviewGroupBase extends PluginBase implements SocialManagementOverviewGroupInterface {

  /**
   * The overview item manager service.
   *
   * @var \Drupal\social_management_overview\Plugin\SocialManagementOverviewItemManager
   */
  protected $overviewItemManager;

  /**
   * Constructs a new SocialManagementOverviewGroupBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\social_management_overview\Plugin\SocialManagementOverviewItemManager $overview_item_manager
   *   The overview item manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SocialManagementOverviewItemManager $overview_item_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->overviewItemManager = $overview_item_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.social_management_overview_item')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): TranslatableMarkup {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getChildren(): array {
    $items = [];
    $overview_items = $this->overviewItemManager->getChildren($this->getPluginId());

    // Skip group if it does not have children.
    if (empty($overview_items)) {
      return [];
    }

    // Sort children by weight.
    $weights = array_column($overview_items, 'weight');
    array_multisort($weights, SORT_ASC, $overview_items);

    // Add children o the list.
    foreach ($overview_items as $overview_item) {
      if (($url = Url::fromRoute($overview_item['route']))->access()) {
        $items[] = [
          'title' => $overview_item['label'],
          'description' => $overview_item['description'],
          'url' => $url,
        ];
      }
    }

    return $items;
  }

}
