<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'External Identifier Default' formatter.
 *
 * @FieldFormatter(
 *   id = "social_external_identifier_default_formatter",
 *   label = @Translation("External Identifier Default"),
 *   field_types = {"social_external_identifier"},
 * )
 */
final class ExternalIdentifierDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'external_id_formatter',
        '#external_id' => $item->external_id,
        '#external_owner_target_type' => $item->external_owner_target_type,
        '#external_owner_id' => $item->external_owner_id,
      ];
    }
    return $element;
  }

}
