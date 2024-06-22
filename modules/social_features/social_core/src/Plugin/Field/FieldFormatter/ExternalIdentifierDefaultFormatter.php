<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldFormatter\DynamicEntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'External Identifier Default' formatter.
 *
 * @FieldFormatter(
 *   id = "social_external_identifier_default_formatter",
 *   label = @Translation("External Identifier Default"),
 *   field_types = {"social_external_identifier"},
 * )
 */
final class ExternalIdentifierDefaultFormatter extends DynamicEntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];
    foreach ($items as $delta => $item) {
      $element[$delta] = [
        '#theme' => 'external_id_formatter',
        '#external_id' => $item->external_id,
        '#target_type' => $item->target_type,
        '#target_id' => $item->target_id,
      ];
    }
    return $element;
  }

}
