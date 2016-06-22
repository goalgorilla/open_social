<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Sc" ("Symbol, Currency").
 */
class Sc implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0024}\x{00A2}\x{00A3}\x{00A4}\x{00A5}\x{058F}\x{060B}' .
      '\x{09F2}\x{09F3}\x{09FB}\x{0AF1}\x{0BF9}\x{0E3F}\x{17DB}' .
      '\x{20A0}\x{20A1}\x{20A2}\x{20A3}\x{20A4}\x{20A5}\x{20A6}' .
      '\x{20A7}\x{20A8}\x{20A9}\x{20AA}\x{20AB}\x{20AC}\x{20AD}' .
      '\x{20AE}\x{20AF}\x{20B0}\x{20B1}\x{20B2}\x{20B3}\x{20B4}' .
      '\x{20B5}\x{20B6}\x{20B7}\x{20B8}\x{20B9}\x{20BA}\x{A838}' .
      '\x{FDFC}\x{FE69}\x{FF04}\x{FFE0}\x{FFE1}\x{FFE5}\x{FFE6}';
  }

}
