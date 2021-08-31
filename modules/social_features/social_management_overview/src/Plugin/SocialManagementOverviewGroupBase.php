<?php

namespace Drupal\social_management_overview\Plugin;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

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
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SocialManagementOverviewItemManager $overview_item_manager, MessengerInterface $messenger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->overviewItemManager = $overview_item_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.social_management_overview_item'),
      $container->get('messenger')
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
    foreach ($overview_items as $id => $overview_item) {
      /** @var \Drupal\social_management_overview\Plugin\SocialManagementOverviewItemInterface $plugin */
      try {
        $plugin = $this->overviewItemManager->createInstance($id);
        $url = $plugin->getUrl();
        if (!is_null($url) && $url->access()) {
          $items[$id] = [
            'title' => $overview_item['label'] ?? '',
            'description' => $overview_item['description'] ?? '',
            'url' => $url,
          ];
        }
      }
      catch (PluginException $e) {
        $message = $e->getMessage();
        $this->messenger->addWarning($this->t('@message', ['@message' => $message]));
      }
    }

    return $items;
  }

}
