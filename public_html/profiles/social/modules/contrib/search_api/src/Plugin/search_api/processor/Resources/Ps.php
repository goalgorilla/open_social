<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Ps" ("Punctuation, Open").
 */
class Ps implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0028}\x{005B}\x{007B}\x{0F3A}\x{0F3C}\x{169B}\x{201A}' .
      '\x{201E}\x{2045}\x{207D}\x{208D}\x{2308}\x{230A}\x{2329}' .
      '\x{2768}\x{276A}\x{276C}\x{276E}\x{2770}\x{2772}\x{2774}' .
      '\x{27C5}\x{27E6}\x{27E8}\x{27EA}\x{27EC}\x{27EE}\x{2983}' .
      '\x{2985}\x{2987}\x{2989}\x{298B}\x{298D}\x{298F}\x{2991}' .
      '\x{2993}\x{2995}\x{2997}\x{29D8}\x{29DA}\x{29FC}\x{2E22}' .
      '\x{2E24}\x{2E26}\x{2E28}\x{3008}\x{300A}\x{300C}\x{300E}' .
      '\x{3010}\x{3014}\x{3016}\x{3018}\x{301A}\x{301D}\x{FD3E}' .
      '\x{FE17}\x{FE35}\x{FE37}\x{FE39}\x{FE3B}\x{FE3D}\x{FE3F}' .
      '\x{FE41}\x{FE43}\x{FE47}\x{FE59}\x{FE5B}\x{FE5D}\x{FF08}' .
      '\x{FF3B}\x{FF5B}\x{FF5F}\x{FF62}';
  }

}
