<?php

namespace Drupal\social_tagging\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\social_tagging\SocialTaggingServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'social_tagging_usage' widget.
 *
 * @FieldWidget(
 *   id = "social_tagging_usage",
 *   label = @Translation("Social tagging usage"),
 *   field_types = {
 *     "string_long"
 *   }
 * )
 */
class TaggingUsageWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, private SocialTaggingServiceInterface $taggingService) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
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
      $container->get('social_tagging.tag_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $value = unserialize($items[$delta]->value ?? '');
    $options = $this->taggingService->getKeyValueOptions();
    $element['#type'] = 'checkboxes';
    $element['#options'] = $options;
    $element['#default_value'] = empty($value) ? [] : $value;
    $element['#description_display'] = 'before';
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state): void {
    // Check parent value.
    $parent = $form_state->getValue('parent') ?? [];
    $parent = reset($parent);
    // If parent is set, no usage value is needed.
    if (!empty($parent)) {
      $items->setValue('');
      return;
    }
    $field_name = $this->fieldDefinition->getName();
    // Extract the values from $form_state->getValues().
    $path = array_merge($form['#parents'], [$field_name]);
    $key_exists = NULL;
    $values = NestedArray::getValue($form_state->getValues(), $path, $key_exists);
    $values = reset($values);
    // We have dynamic amount of items.
    // Save as string.
    $items->setValue(serialize($values));
  }

}
