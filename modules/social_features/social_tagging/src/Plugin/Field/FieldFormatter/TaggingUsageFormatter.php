<?php

namespace Drupal\social_tagging\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\social_tagging\SocialTaggingServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'social_tagging_usage_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "social_tagging_usage_formatter",
 *   label = @Translation("Social tagging usage"),
 *   field_types = {
 *     "string_long",
 *   }
 * )
 */
class TaggingUsageFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, private SocialTaggingServiceInterface $taggingService) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
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
      $container->get('social_tagging.tag_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      $keys = $this->taggingService->getKeyValueOptions();
      $values = unserialize($items[$delta]->value ?? '');
      foreach ($values as $value) {
        // Skip not selected items.
        if (empty($value) || empty($keys[$value])) {
          continue;
        }
        $elements[$delta][] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#value' => $keys[$value],
        ];
      }
    }

    return $elements;
  }

}
