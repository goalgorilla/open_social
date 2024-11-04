<?php

declare(strict_types=1);

namespace Drupal\social_core;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for social_entity_query_alter plugins.
 */
abstract class SocialEntityQueryAlterPluginBase extends PluginBase implements SocialEntityQueryAlterInterface, ContainerFactoryPluginInterface {

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
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
   * Finds a certain field in the index.
   *
   * @param string|null $datasource_id
   *   The ID of the field's datasource, or NULL for a datasource-independent
   *   field.
   * @param string $property_path
   *   The field's property path on the datasource.
   * @param string|null $type
   *   (optional) If set, only return a field if it has this type.
   *
   * @return \Drupal\search_api\Item\FieldInterface|null
   *   A field on the index with the desired properties, or NULL if none could
   *   be found.
   */
  protected function searchApiFindField(IndexInterface $search_api_index, ?string $datasource_id, string $property_path, ?string $type = NULL): ?FieldInterface {
    foreach ($search_api_index->getFieldsByDatasource($datasource_id) as $field) {
      if ($field->getPropertyPath() === $property_path) {
        if ($type === NULL || $field->getType() === $type) {
          return $field;
        }
      }
    }

    return NULL;
  }

  /**
   * Returns the list of entity types for which this plugin should apply.
   *
   * @return string[]
   *   The list of entity type ids.
   */
  public function getSupportedEntityTypeIds(): array {
    return array_keys($this->pluginDefinition['apply_on']);
  }


  /**
   * Tells if the plugin can be applied on given entity type id.
   *
   * @return bool
   *   TRUE if the plugin is applicable on a given entity type id,
   *   otherwise FALSE.
   */
  public function applicableOnEntityType(string $entity_type_id): bool {
    return isset($this->pluginDefinition['apply_on'][$entity_type_id]);
  }

  /**
   * Returns the list of entity types fields for which this plugin should apply.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return string[]
   *   The list of entity type field names.
   */
  public function getSupportedFieldsByEntityType(string $entity_type_id): array {
    return $this->pluginDefinition['apply_on'][$entity_type_id]['fields'] ?? [];
  }

  /**
   * Returns search api query tags that plugin should alter.
   *
   * @return string[]
   *   The list of query tags.
   */
  public function getSearchApiQueryTags(): array {
    return $this->pluginDefinition['search_api_query_tags'] ?? [];
  }

  /**
   * Check if the plugin supports the given search query tag.
   *
   * @param string $query_tag
   *   Search api query tag.
   *
   * @return bool
   *   The plugin support or no the query tag.
   */
  public function applicableOnSearchApiQueryTag(string $query_tag): bool {
    return in_array($query_tag, $this->getSearchApiQueryTags());
  }

  /**
   * {@inheritdoc}
   */
  public function searchApiOnDataIndex(ItemInterface $item): void {
    // By default, we won't alter anything.
    return;
  }

  /**
   * {@inheritdoc}
   */
  public function searchApiFieldProperties(?string $entity_type_id = NULL): array {
    if (NULL !== $entity_type_id) {
      $fields[$entity_type_id] = $this->pluginDefinition['apply_on'][$entity_type_id]['fields'] ?? [];
    }
    else {
      foreach ($this->pluginDefinition['apply_on'] as $entity_type_id => $data) {
        $fields[$entity_type_id] = $data[$entity_type_id]['fields'] ?? [];
      }
    }

    if (empty($fields)) {
      return [];
    }

    foreach ($fields as $entity_type_id => $field_names) {
      $field_definitions = $this->entityFieldManager->getBaseFieldDefinitions($entity_type_id) +
        $this->entityFieldManager->getActiveFieldStorageDefinitions($entity_type_id);

      foreach ($field_names as $field_name) {
        /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $storage_field */
        $field_definition = $field_definitions[$field_name];

        $type = match($field_definition->getType()) {
          'boolean' => 'boolean',
          'datetime' => 'date',
          'integer', 'created', 'changed', 'entity_reference' => 'integer',
          default => 'string',
        };

        $property_definition = [
          'label' => $field_definition->getLabel(),
          'description' => $field_definition->getDescription(),
          'type' => $type,
          'processor_id' => 'social_node_content_visibility',
          'hidden' => TRUE,
          'is_list' => $field_definition->getCardinality() === -1,
        ];

        $properties[$field_definition->getName()] = new ProcessorProperty($property_definition);
      }
    }

    return $properties ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAccessAlter(SelectInterface $query, ConditionInterface $conditions, AccountInterface $account): void {
    // @todo Move implementation from https://github.com/goalgorilla/open_social/pull/4098
  }

}
