<?php

namespace Drupal\social_content_block\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_content_block\ContentBlockManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'content_block_plugin_field' widget.
 *
 * @FieldWidget(
 *   id = "content_block_plugin_field",
 *   label = @Translation("Content block plugin field"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ContentBlockPluginFieldWidget extends ContentBlockPluginWidgetBase {

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
   * Constructs a ContentBlockPluginFieldWidget object.
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
    array $field_configs
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $content_block_manager
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
        '#type' => 'select',
        '#title' => $element['value']['#title'],
        '#description' => $element['value']['#description'],
        '#empty_value' => 'all',
        '#empty_option' => t('All'),
        '#weight' => -1,
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

      if (!empty($plugin_definition['fields'])) {
        foreach ($plugin_definition['fields'] as $field) {
          if (isset($form[$field])) {
            // Depending on the field type the field title to filter by is in
            // different places.
            // For entity reference fields.
            if (isset($form[$field]['widget']['target_id']['#title'])) {
              $element[$plugin_id]['#options'][$field] = $form[$field]['widget']['target_id']['#title'];
            }
            // For other field types (e.g. select)
            elseif (isset($form[$field]['widget']['#title'])) {
              $element[$plugin_id]['#options'][$field] = $form[$field]['widget']['#title'];
            }
            // Otherwise we show a helpful message to the developer or QA that
            // they should implement an additional clause.
            else {
              $element[$plugin_id]['#options'][$field] = "-- Could not find widget title for '{$field}' in " . self::class . ' --';
            }

            $form[$field]['#states'] = [
              'visible' => [
                $selector => [
                  'value' => $plugin_id,
                ],
                $this->contentBlockManager->getSelector('field_plugin_field', $plugin_id) => [
                  ['value' => 'all'],
                  ['value' => $field],
                ],
              ],
            ];
          }
          elseif (in_array(self::CONFIG_PREFIX . $field, $this->fieldConfigs)) {
            // Add the field machine name instead of the field label when the
            // field still not added to the form structure. The field will be
            // processed in the following place:
            // @see \Drupal\social_content_block\ContentBuilder::processBlockForm()
            $element[$plugin_id]['#options'][$field] = $field;
          }
        }
      }
      else {
        $element[$plugin_id]['#options'] = [];
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
      'field_plugin_field',
      0,
      $form_state->getValue(['field_plugin_id', 0, 'value']),
    ]);

    if ($value === 'all') {
      $form_state->setValueForElement($element, NULL);
    }
    else {
      $form_state->setValueForElement($element, ['value' => $value]);
    }
  }

}
