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
  protected $manager;

  /**
   * Constructs a ContentBlockSortingWidget object.
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
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
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element['#description'] = $names = [];

    $selector = $this->manager->getSelector($items->getName(), NULL, NULL, TRUE);

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
