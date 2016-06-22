<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Zs" ("Separator, Space").
 */
class Zs implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0020}\x{00A0}\x{1680}\x{2000}\x{2001}\x{2002}\x{2003}' .
      '\x{2004}\x{2005}\x{2006}\x{2007}\x{2008}\x{2009}\x{200A}' .
      '\x{202F}\x{205F}\x{3000}';
  }

}
