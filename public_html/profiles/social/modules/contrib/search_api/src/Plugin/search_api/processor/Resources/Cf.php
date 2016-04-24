<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Cf" ("Other, Format").
 */
class Cf implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{00AD}\x{0600}\x{0601}\x{0602}\x{0603}\x{0604}\x{061C}' .
      '\x{06DD}\x{070F}\x{180E}\x{200B}\x{200C}\x{200D}\x{200E}' .
      '\x{200F}\x{202A}\x{202B}\x{202C}\x{202D}\x{202E}\x{2060}' .
      '\x{2061}\x{2062}\x{2063}\x{2064}\x{2066}\x{2067}\x{2068}' .
      '\x{2069}\x{206A}\x{206B}\x{206C}\x{206D}\x{206E}\x{206F}' .
      '\x{FEFF}\x{FFF9}\x{FFFA}\x{FFFB}\x{110BD}\x{1D173}\x{1D174}' .
      '\x{1D175}\x{1D176}\x{1D177}\x{1D178}\x{1D179}\x{1D17A}\x{E0001}' .
      '\x{E0020}\x{E0021}\x{E0022}\x{E0023}\x{E0024}\x{E0025}\x{E0026}' .
      '\x{E0027}\x{E0028}\x{E0029}\x{E002A}\x{E002B}\x{E002C}\x{E002D}' .
      '\x{E002E}\x{E002F}\x{E0030}\x{E0031}\x{E0032}\x{E0033}\x{E0034}' .
      '\x{E0035}\x{E0036}\x{E0037}\x{E0038}\x{E0039}\x{E003A}\x{E003B}' .
      '\x{E003C}\x{E003D}\x{E003E}\x{E003F}\x{E0040}\x{E0041}\x{E0042}' .
      '\x{E0043}\x{E0044}\x{E0045}\x{E0046}\x{E0047}\x{E0048}\x{E0049}' .
      '\x{E004A}\x{E004B}\x{E004C}\x{E004D}\x{E004E}\x{E004F}\x{E0050}' .
      '\x{E0051}\x{E0052}\x{E0053}\x{E0054}\x{E0055}\x{E0056}\x{E0057}' .
      '\x{E0058}\x{E0059}\x{E005A}\x{E005B}\x{E005C}\x{E005D}\x{E005E}' .
      '\x{E005F}\x{E0060}\x{E0061}\x{E0062}\x{E0063}\x{E0064}\x{E0065}' .
      '\x{E0066}\x{E0067}\x{E0068}\x{E0069}\x{E006A}\x{E006B}\x{E006C}' .
      '\x{E006D}\x{E006E}\x{E006F}\x{E0070}\x{E0071}\x{E0072}\x{E0073}' .
      '\x{E0074}\x{E0075}\x{E0076}\x{E0077}\x{E0078}\x{E0079}\x{E007A}' .
      '\x{E007B}\x{E007C}\x{E007D}\x{E007E}\x{E007F}';

  }

}
