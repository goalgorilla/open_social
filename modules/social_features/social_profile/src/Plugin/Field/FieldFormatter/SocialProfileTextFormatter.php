<?php

namespace Drupal\social_profile\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\text\Plugin\Field\FieldFormatter\TextTrimmedFormatter;

/**
 * Plugin implementation of the 'social_profile_text' formatter.
 *
 * @FieldFormatter(
 *   id = "social_profile_text",
 *   label = @Translation("Trimmed plain text"),
 *   field_types = {
 *     "text",
 *     "text_long",
 *     "text_with_summary",
 *   },
 *   quickedit = {
 *     "editor" = "form",
 *   },
 * )
 */
class SocialProfileTextFormatter extends TextTrimmedFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($items as $delta => $item) {
      $elements[$delta]['#text'] = strip_tags($elements[$delta]['#text']);
    }

    return $elements;
  }

}
