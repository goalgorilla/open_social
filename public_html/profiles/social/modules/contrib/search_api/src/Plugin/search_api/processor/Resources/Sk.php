<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Sk" ("Symbol, Modifier").
 */
class Sk implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{005E}\x{0060}\x{00A8}\x{00AF}\x{00B4}\x{00B8}\x{02C2}' .
      '\x{02C3}\x{02C4}\x{02C5}\x{02D2}\x{02D3}\x{02D4}\x{02D5}' .
      '\x{02D6}\x{02D7}\x{02D8}\x{02D9}\x{02DA}\x{02DB}\x{02DC}' .
      '\x{02DD}\x{02DE}\x{02DF}\x{02E5}\x{02E6}\x{02E7}\x{02E8}' .
      '\x{02E9}\x{02EA}\x{02EB}\x{02ED}\x{02EF}\x{02F0}\x{02F1}' .
      '\x{02F2}\x{02F3}\x{02F4}\x{02F5}\x{02F6}\x{02F7}\x{02F8}' .
      '\x{02F9}\x{02FA}\x{02FB}\x{02FC}\x{02FD}\x{02FE}\x{02FF}' .
      '\x{0375}\x{0384}\x{0385}\x{1FBD}\x{1FBF}\x{1FC0}\x{1FC1}' .
      '\x{1FCD}\x{1FCE}\x{1FCF}\x{1FDD}\x{1FDE}\x{1FDF}\x{1FED}' .
      '\x{1FEE}\x{1FEF}\x{1FFD}\x{1FFE}\x{309B}\x{309C}\x{A700}' .
      '\x{A701}\x{A702}\x{A703}\x{A704}\x{A705}\x{A706}\x{A707}' .
      '\x{A708}\x{A709}\x{A70A}\x{A70B}\x{A70C}\x{A70D}\x{A70E}' .
      '\x{A70F}\x{A710}\x{A711}\x{A712}\x{A713}\x{A714}\x{A715}' .
      '\x{A716}\x{A720}\x{A721}\x{A789}\x{A78A}\x{FBB2}\x{FBB3}' .
      '\x{FBB4}\x{FBB5}\x{FBB6}\x{FBB7}\x{FBB8}\x{FBB9}\x{FBBA}' .
      '\x{FBBB}\x{FBBC}\x{FBBD}\x{FBBE}\x{FBBF}\x{FBC0}\x{FBC1}' .
      '\x{FF3E}\x{FF40}\x{FFE3}';
  }

}
