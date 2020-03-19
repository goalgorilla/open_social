<?php

namespace Drupal\social_content_block\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_content_block\ContentBlockManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'content_block_plugin_id' widget.
 *
 * @FieldWidget(
 *   id = "content_block_plugin_id",
 *   label = @Translation("Content block plugin ID"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ContentBlockPluginIdWidget extends ContentBlockPluginWidgetBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a ContentBlockPluginIdWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\social_content_block\ContentBlockManagerInterface $content_block_manager
   *   The content block manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ContentBlockManagerInterface $content_block_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $content_block_manager
    );

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
      $configuration['third_party_settings'],
      $container->get('plugin.manager.content_block'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $value = &$element['value'];
    $value['#type'] = 'select';
    $definitions = $this->contentBlockManager->getDefinitions();

    if (!$element['value']['#default_value']) {
      $element['value']['#default_value'] = key($definitions);
    }

    foreach ($definitions as $plugin_id => $plugin_definition) {
      $entity_type = $this->entityTypeManager->getDefinition($plugin_definition['entityTypeId']);

      if (isset($plugin_definition['bundle'])) {
        $value['#options'][$plugin_id] = $this->entityTypeManager
          ->getStorage($entity_type->getBundleEntityType())
          ->load($plugin_definition['bundle'])
          ->label();
      }
      else {
        $value['#options'][$plugin_id] = $entity_type->getLabel();
      }
    }

    if (count($definitions) === 1) {
      $value['#empty_value'] = key($value['#options']);
      $value['#empty_option'] = reset($value['#options']);
      $value['#disabled'] = TRUE;
    }

    return $element;
  }

}
