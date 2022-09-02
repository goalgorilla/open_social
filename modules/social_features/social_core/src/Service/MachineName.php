<?php

namespace Drupal\social_core\Service;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Creates a machine name.
 *
 * This functions takes a string input and turns it into a
 * machine-readable name via the following four steps:
 *
 * 1. Language decorations and accents are removed by transliterating the source
 *    value.
 * 2. The resulting value is made lowercase.
 * 3. Any special characters are replaced with an underscore. By default,
 *    anything that is not a number or a letter is replaced, but additional
 *    characters can be allowed or further restricted by using the
 *    replace_pattern configuration as described below.
 * 4. Any duplicate underscores either in the source value or as a result of
 *    replacing special characters are removed.
 *
 * This class is inspiration from the class
 * @link https://api.drupal.org/api/drupal/core%21modules%21migrate%21src%21Plugin%21migrate%21process%21MachineName.php/class/MachineName/9.4.x @endlink
 */
class MachineName implements MachineNameInterface {

  /**
   * The transliteration service.
   */
  protected TransliterationInterface $transliteration;

  /**
   * MachineName constructor.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

  /**
   * {@inheritdoc}
   */
  public function transform(
    string $value,
    string $pattern = '/[^a-z0-9_]+/'
  ): string {
    $value = $this->transliteration->transliterate(
      $value,
      LanguageInterface::LANGCODE_DEFAULT,
      '_',
    );

    if (
      ($value = preg_replace($pattern, '_', strtolower($value))) !== NULL &&
      ($value = preg_replace('/_+/', '_', $value)) !== NULL
    ) {
      return $value;
    }

    return '';
  }

}
