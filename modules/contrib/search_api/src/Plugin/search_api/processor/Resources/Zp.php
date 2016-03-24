<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\search_api\processor\Resources\Zp.
 */

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Zp" ("Separator, Paragraph").
 */
class Zp implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{2029}';
  }

}
