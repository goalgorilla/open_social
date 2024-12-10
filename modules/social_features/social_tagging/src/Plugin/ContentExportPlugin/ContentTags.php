<?php

namespace Drupal\social_tagging\Plugin\ContentExportPlugin;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\node\NodeInterface;
use Drupal\social_content_export\Plugin\ContentExportPluginBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ContentTags' content export row.
 *
 * @ContentExportPlugin(
 *   id = "content_tags",
 *   label = @Translation("Tags"),
 *   weight = -100,
 * )
 */
class ContentTags extends ContentExportPluginBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs of the ContentTags class.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, Connection $database, ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $database);

    $this->moduleHandler = $module_handler;
  }

  /**
   * The create method.
   *
   * @param ContainerInterface $container
   *   Container interface.
   * @param array $configuration
   *   An array of configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return ContentTags Returns the ContentTags plugin.
   *   Returns the ContentTags plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(NodeInterface $entity): string {
    if ($this->moduleHandler->moduleExists('social_tagging')) {
      $tags = $entity->get('social_tagging')->getValue();
      if (!$tags) {
        return '';
      }

      $ids = array_map(function ($item) {
        return $item['target_id'];
      }, $tags);

      $terms = Term::loadMultiple($ids);

      $terms_names = [];

      foreach ($terms as $term) {
        if ($term instanceof TermInterface) {
          $terms_names[] = $term->getName();
        }
      }

      return implode(', ', $terms_names);
    }

    return '';
  }

}
