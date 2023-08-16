<?php

declare(strict_types=1);

namespace Drupal\social_tag_split\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A formatter that uses the top taxonomy level as categories for split fields.
 *
 * This allows users who don't have access to the Field UI to create
 * multiple related taxonomy fields on an entity. This happens by pretending
 * that one field is multiple fields, splitting them no the top taxonomy level.
 *
 * @FieldFormatter(
 *   id = "social_tag_split",
 *   label = @Translation("Tag split"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TagSplitFormatter extends EntityReferenceLabelFormatter {

  /**
   * The Drupal entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get("entity_type.manager"),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    assert($items instanceof EntityReferenceFieldItemListInterface);

    // The EntityReferenceLabelFormatter performs some processing before
    // `viewElements` in `prepareView` which sets a `_loaded` property on the
    // `$item`. This means we can't create a new item list because it would
    // automatically create new items without these properties. Instead, we must
    // clone and filter the list for each parent to group them to preserve those
    // properties.
    $entities = $items->referencedEntities();
    $parents = [];
    foreach ($entities as $term) {
      assert($term instanceof FieldableEntityInterface);
      $parent_id = (int) $term->get('parent')->target_id;
      if ($parent_id !== 0) {
        $parents[] = $parent_id;
      }
    }

    // We must load all the parents because if they weren't part of the field's
    // values they're not in memory yet, contrary to all the selected field
    // values.
    $parents = $this->entityTypeManager->getStorage("taxonomy_term")->loadMultiple(array_unique($parents));

    $elements = [];
    foreach ($parents as $parent_id => $parent) {
      // Create a new list containing only the items for this parent.
      $list = (clone $items)->filter(
        function (EntityReferenceItem $item) use ($parent_id) {
          $entity = $item->entity;
          assert($entity instanceof FieldableEntityInterface);
          return (int) $entity->get('parent')->target_id === $parent_id;
        }
      );
      $elements[$parent_id] = $this->viewHierarchy($parent, $list, $langcode);
    }

    return $elements;
  }

  /**
   * Render a single hierarchy within the field.
   *
   * @param \Drupal\taxonomy\TermInterface $parent
   *   The loaded parent term for this hierarchy.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $items
   *   The child items that belong to this parent.
   * @param string $langcode
   *   The langcode in which this field is being rendered.
   *
   * @return array
   *   A render array.
   */
  protected function viewHierarchy(TermInterface $parent, EntityReferenceFieldItemListInterface $items, string $langcode) : array {
    return [
      'label' => ["#plain_text" => $parent->label()],
      'items' => parent::viewElements($items, $langcode),
    ];
  }

}
