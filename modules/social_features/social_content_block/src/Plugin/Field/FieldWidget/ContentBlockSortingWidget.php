<?php

namespace Drupal\social_content_block\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsSelectWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_content_block\ContentBlockManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'content_block_sorting' widget.
 *
 * @FieldWidget(
 *   id = "content_block_sorting",
 *   label = @Translation("Content block sorting"),
 *   field_types = {
 *     "list_string"
 *   },
 *   multiple_values = TRUE
 * )
 */
class ContentBlockSortingWidget extends OptionsSelectWidget {

  /**
   * The content block manager.
   *
   * @var \Drupal\social_content_block\ContentBlockManagerInterface
   */
  protected ContentBlockManagerInterface $manager;

  /**
   * Constructs a ContentBlockSortingWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param mixed[] $settings
   *   The widget settings.
   * @param mixed[] $third_party_settings
   *   Any third party settings.
   * @param \Drupal\social_content_block\ContentBlockManagerInterface $manager
   *   The content block manager.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ContentBlockManagerInterface $manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings
    );

    $this->manager = $manager;
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param mixed[] $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('plugin.manager.content_block')
    );
  }

  /**
   * Returns the form for a single field widget.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Array of default values for this field.
   * @param int $delta
   *   The order of this item in the array of sub-elements (0, 1, 2, etc.).
   * @param array $element
   *   A form element array containing basic properties for the widget.
   * @param array $form
   *   The form structure where widgets are being attached to. This might be a
   *   full form structure, or a sub-element of a larger form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form elements for a single widget for this field.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if (!is_string($name = $items->getName())) {
      return $element;
    }

    $element['#description'] = $names = [];

    $selector = $this->manager->getSelector($name, NULL, NULL, TRUE);

    foreach (array_keys($this->manager->getDefinitions()) as $plugin_id) {
      $plugin = $this->manager->createInstance($plugin_id);

      foreach ($plugin->supportedSortOptions() as $name => $settings) {
        if (
          in_array($name, $names) ||
          !is_array($settings) ||
          empty($settings['description'])
        ) {
          continue;
        }

        $element['#description'][$name] = [
          '#type' => 'item',
          '#plain_text' => $settings['description'],
          '#states' => [
            'visible' => [
              $selector => [
                'value' => $name,
              ],
            ],
          ],
        ];

        $names[] = $name;
      }
    }

    return $element;
  }

}
