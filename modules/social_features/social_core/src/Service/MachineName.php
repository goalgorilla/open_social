<?php

namespace Drupal\social_core\Service;

use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\Element\Page;

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
class MachineName {

  /**
   * The transliteration service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;


  /**
   * SocialTaggingService constructor.
   *
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   */
  public function __construct(TransliterationInterface $transliteration) {
    $this->transliteration = $transliteration;
  }

  /**
   * Transforms given string to machine name.
   *
   * @param string $value
   *   The value to be transformed.
   *
   * @return string
   *   The newly transformed value.
   */
  public function transform(string $value, string $replacePattern = '/[^a-z0-9_]+/'): string {
    $new_value = $this->transliteration->transliterate($value, LanguageInterface::LANGCODE_DEFAULT, '_');
    $new_value = strtolower($new_value);
    $new_value = preg_replace($replacePattern, '_', $new_value);
    if (!is_null($new_value)) {
      $new_value = preg_replace('/_+/', '_', $new_value);
    }
    if (!is_null($new_value)) {
      return $new_value;
    }
    return '';
  }
}
