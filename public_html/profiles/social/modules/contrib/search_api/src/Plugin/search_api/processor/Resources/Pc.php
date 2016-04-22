<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the category "Pc" ("Punctuation, Connector").
 */
class Pc implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{005F}\x{203F}\x{2040}\x{2054}\x{FE33}\x{FE34}\x{FE4D}' .
      '\x{FE4E}\x{FE4F}\x{FF3F}';
  }

}
