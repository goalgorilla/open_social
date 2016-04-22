<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Co" ("Other, Private Use").
 */
class Co implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{E000}\x{F8FF}\x{F0000}\x{FFFFD}\x{100000}\x{10FFFD}';
  }

}
