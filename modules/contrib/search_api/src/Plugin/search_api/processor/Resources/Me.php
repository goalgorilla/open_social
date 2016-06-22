<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Me" ("Mark, Enclosing").
 */
class Me implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0488}\x{0489}\x{20DD}\x{20DE}\x{20DF}\x{20E0}\x{20E2}' .
      '\x{20E3}\x{20E4}\x{A670}\x{A671}\x{A672}';
  }

}
