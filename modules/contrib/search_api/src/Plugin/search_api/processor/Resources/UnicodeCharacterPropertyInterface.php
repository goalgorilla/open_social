<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Defines an interface for classes representing a Unicode character property.
 */
interface UnicodeCharacterPropertyInterface {

  /**
   * Returns a regular expression matching this character class.
   *
   * @return string
   *   A PCRE regular expression.
   */
  public static function getRegularExpression();

}
