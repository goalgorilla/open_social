<?php

namespace Drupal\social_content_block\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_content_block\ContentBlockManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'content_block_plugin_filters' widget.
 *
 * @FieldWidget(
 *   id = "content_block_plugin_filters",
 *   label = @Translation("Content block plugin filters"),
 *   field_types = {
 *     "boolean"
 *   }
 * )
 */
class ContentBlockPluginFiltersWidget extends ContentBlockPluginWidgetBase {

  /**
   * The prefix to search for.
   */
  const CONFIG_PREFIX = 'field.field.block_content.custom_content_list.';

  /**
   * An array containing matching configuration object names.
   *
   * @var array
   */
  protected $fieldConfigs;

  /**
   * Constructs a ContentBlockPluginFiltersWidget object.
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
   * @param array $field_configs
   *   An array containing matching configuration object names.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ContentBlockManagerInterface $content_block_manager,
    EntityTypeManagerInterface $entity_type_manager,
    array $field_configs
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $content_block_manager,
      $entity_type_manager
    );

    $this->fieldConfigs = $field_configs;
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
      $container->get('entity_type.manager'),
      $container->get('config.factory')->listAll(self::CONFIG_PREFIX)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $selected_plugin_id = $items->getEntity()->field_plugin_id->value;
    $selector = $this->contentBlockManager->getSelector('field_plugin_id', 'value', $element['#field_parents']);

    foreach ($this->contentBlockManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $element[$plugin_id] = [
        '#type' => 'checkbox',
        '#title' => $this->t('All @type', [
          '@type' => mb_strtolower($this->getLabel($plugin_definition)),
        ]),
        '#description' => $element['value']['#description'],
        '#states' => [
          'visible' => [
            $selector => [
              'value' => $plugin_id,
            ],
          ],
        ],
      ];

      if ($selected_plugin_id === $plugin_id) {
        $element[$plugin_id]['#default_value'] = $element['value']['#default_value'];
      }

      foreach ($plugin_definition['fields'] as $field) {
        if (!isset($form[$field])) {
          continue;
        }

        $form[$field]['#states'] = [
          'visible' => [
            $selector => [
              'value' => $plugin_id,
            ],
          ],
          'disabled' => [
            $this->contentBlockManager->getSelector('field_plugin_filters', $plugin_id) => [
              'checked' => TRUE,
            ],
          ],
        ];
      }
    }

    $element['#element_validate'][] = [get_class($this), 'validateElement'];

    return $element;
  }

  /**
   * Form validation handler for widget elements.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    $value = $form_state->getValue([
      'field_plugin_filters',
      0,
      $form_state->getValue(['field_plugin_id', 0, 'value']),
    ]);

    $form_state->setValueForElement($element, ['value' => $value]);
  }

}
