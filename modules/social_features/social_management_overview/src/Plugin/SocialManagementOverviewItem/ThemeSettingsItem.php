<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a new overview item "Change colors and styling".
 *
 * @SocialManagementOverviewItem(
 *   id = "theme_settings_item",
 *   label = @Translation("Change colors and styling"),
 *   description = @Translation("Determine the look and feel of your site."),
 *   weight = 0,
 *   group = "appearance_group",
 *   route = "system.theme_settings_theme"
 * )
 */
class ThemeSettingsItem extends SocialManagementOverviewItemBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a ThemeSettingsItem object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThemeHandlerInterface $theme_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters(): array {
    return [
      'theme' => $this->themeHandler->getDefault(),
    ];
  }

}
