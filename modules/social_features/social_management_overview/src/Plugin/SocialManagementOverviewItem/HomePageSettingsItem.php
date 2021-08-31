<?php

namespace Drupal\social_management_overview\Plugin\SocialManagementOverviewItem;

use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Drupal\social_management_overview\Plugin\SocialManagementOverviewItemBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a new overview item "Customize home page".
 *
 * @SocialManagementOverviewItem(
 *   id = "home_page_settings_item",
 *   label = @Translation("Customize home page"),
 *   description = @Translation("Change the image and text on the home page."),
 *   weight = 1,
 *   group = "appearance_group",
 *   route = "entity.block_content.edit_form"
 * )
 */
class HomePageSettingsItem extends SocialManagementOverviewItemBase {

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ThemeSettingsItem object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   The database.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): ?Url {
    $query = $this->database->select('block_content', 'bc');
    $query->addField('bc', 'id');
    $query->condition('bc.type', 'hero_call_to_action_block');
    $query->range(0, 1);
    $block_id = $query->execute()->fetchField();
    if ($block_id) {
      return Url::fromRoute('entity.block_content.edit_form', ['block_content' => $block_id]);
    }
    return NULL;
  }

}
