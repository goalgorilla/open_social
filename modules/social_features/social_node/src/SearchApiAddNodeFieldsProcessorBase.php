<?php

namespace Drupal\social_node;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\LoggerTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\SearchApiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for adding node fields to search api indexes.
 */
abstract class SearchApiAddNodeFieldsProcessorBase extends ProcessorPluginBase implements ContainerFactoryPluginInterface {

  use LoggerTrait;

  /**
   * Constructs an "AddNodeFields" object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index): bool {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() === 'node') {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave(): void {
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      // We want to process node datasource only.
      if ($datasource->getEntityTypeId() !== 'node') {
        continue;
      }

      try {
        // We need to make sure that at least one node bundle has the field.
        foreach (array_keys($datasource->getBundles()) as $bundle) {
          $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $bundle);
          // Before adding field to the index, we should make sure it doesn't
          // exist, otherwise we will add a duplicate.
          foreach ($this->getNodeFieldsName() as $name => $settings) {
            if (!isset($field_definitions[$name])) {
              // Field doesn't exist in node bundle definition.
              // Probably, wrong field name.
              continue;
            }

            if ($this->findField($datasource_id, 'uid')) {
              // Already exists in index.
              continue;
            }

            // "Type" should always be provided.
            $type = $settings['type'];
            $this->ensureField($datasource_id, 'uid', $type);
          }
        }
      }
      catch (SearchApiException $e) {
        $this->getLogger()->error($e->getMessage());
      }
    }
  }

  /**
   * Returns the node fields names list should be added to search api index.
   *
   * @return array
   *   The node field names list with additional settings (type, etc.)
   */
  protected function getNodeFieldsName(): array {
    return [];
  }

}
