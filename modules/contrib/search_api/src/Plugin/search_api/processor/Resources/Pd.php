<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Pd" ("Punctuation, Dash").
 */
class Pd implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{002D}\x{058A}\x{05BE}\x{1400}\x{1806}\x{2010}\x{2011}' .
      '\x{2012}\x{2013}\x{2014}\x{2015}\x{2E17}\x{2E1A}\x{2E3A}' .
      '\x{2E3B}\x{301C}\x{3030}\x{30A0}\x{FE31}\x{FE32}\x{FE58}' .
      '\x{FE63}\x{FF0D}';

  }

}
