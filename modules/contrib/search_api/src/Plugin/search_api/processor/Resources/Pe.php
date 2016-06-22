<?php

namespace Drupal\search_api\Plugin\search_api\processor\Resources;

/**
 * Represents characters of the Unicode category "Pe" ("Punctuation, Close").
 */
class Pe implements UnicodeCharacterPropertyInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRegularExpression() {
    return
      '\x{0029}\x{005D}\x{007D}\x{0F3B}\x{0F3D}\x{169C}\x{2046}' .
      '\x{207E}\x{208E}\x{2309}\x{230B}\x{232A}\x{2769}\x{276B}' .
      '\x{276D}\x{276F}\x{2771}\x{2773}\x{2775}\x{27C6}\x{27E7}' .
      '\x{27E9}\x{27EB}\x{27ED}\x{27EF}\x{2984}\x{2986}\x{2988}' .
      '\x{298A}\x{298C}\x{298E}\x{2990}\x{2992}\x{2994}\x{2996}' .
      '\x{2998}\x{29D9}\x{29DB}\x{29FD}\x{2E23}\x{2E25}\x{2E27}' .
      '\x{2E29}\x{3009}\x{300B}\x{300D}\x{300F}\x{3011}\x{3015}' .
      '\x{3017}\x{3019}\x{301B}\x{301E}\x{301F}\x{FD3F}\x{FE18}' .
      '\x{FE36}\x{FE38}\x{FE3A}\x{FE3C}\x{FE3E}\x{FE40}\x{FE42}' .
      '\x{FE44}\x{FE48}\x{FE5A}\x{FE5C}\x{FE5E}\x{FF09}\x{FF3D}' .
      '\x{FF5D}\x{FF60}\x{FF63}';
  }

}
