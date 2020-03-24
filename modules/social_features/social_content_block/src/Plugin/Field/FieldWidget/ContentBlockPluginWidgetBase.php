<?php

namespace Drupal\social_content_block\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\social_content_block\ContentBlockManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ContentBlockPluginWidgetBase.
 *
 * @package Drupal\social_content_block\Plugin\Field\FieldWidget
 */
abstract class ContentBlockPluginWidgetBase extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The content block manager.
   *
   * @var \Drupal\social_content_block\ContentBlockManagerInterface
   */
  protected $contentBlockManager;

  /**
   * Constructs a ContentBlockPluginWidgetBase object.
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
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    ContentBlockManagerInterface $content_block_manager
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings
    );

    $this->contentBlockManager = $content_block_manager;
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
    $element['value'] = $element + [
      '#default_value' => $items[$delta]->value ?? NULL,
    ];

    return $element;
  }

}
