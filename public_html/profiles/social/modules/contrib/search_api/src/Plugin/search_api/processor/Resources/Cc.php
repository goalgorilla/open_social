<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Cc" ("Other, Control").
 */
class Cc implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0000}\x{0001}\x{0002}\x{0003}\x{0004}\x{0005}\x{0006}' .
      '\x{0007}\x{0008}\x{0009}\x{000A}\x{000B}\x{000C}\x{000D}' .
      '\x{000E}\x{000F}\x{0010}\x{0011}\x{0012}\x{0013}\x{0014}' .
      '\x{0015}\x{0016}\x{0017}\x{0018}\x{0019}\x{001A}\x{001B}' .
      '\x{001C}\x{001D}\x{001E}\x{001F}\x{007F}\x{0080}\x{0081}' .
      '\x{0082}\x{0083}\x{0084}\x{0085}\x{0086}\x{0087}\x{0088}' .
      '\x{0089}\x{008A}\x{008B}\x{008C}\x{008D}\x{008E}\x{008F}' .
      '\x{0090}\x{0091}\x{0092}\x{0093}\x{0094}\x{0095}\x{0096}' .
      '\x{0097}\x{0098}\x{0099}\x{009A}\x{009B}\x{009C}\x{009D}' .
      '\x{009E}\x{009F}';
  }

}
