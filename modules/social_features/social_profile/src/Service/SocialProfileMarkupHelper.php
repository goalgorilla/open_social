<?php

namespace Drupal\social_profile\Service;

use Drupal\Component\Utility\Html;

/**
 * Provides helper functions for the profile markup.
 *
 * @package Drupal\social_profile\Service
 */
class SocialProfileMarkupHelper {

  private const string PREFIX = '<a href="';
  public const string SUFFIX = '>';

  /**
   * Generates the properties for a profile link tag.
   *
   * @param array $variables
   *   The variables from preprocess functions.
   *
   * @return string
   *   The properties for a profile link tag.
   */
  public static function generateProfileLinkTagProperties(array $variables): string {
    if (isset($variables['temp_attributes']) === FALSE) {
      return '';
    }

    $properties = '';
    foreach ($variables['temp_attributes'] as $attribute => $value) {
      if (is_array($value)) {
        $properties .= ' ' . Html::escape($attribute) . '="' . Html::escape(implode(' ', $value)) . '"';
      }
      else {
        $properties .= ' ' . Html::escape($attribute) . '="' . Html::escape($value) . '"';
      }
    }

    return $properties;
  }

  /**
   * Generates the prefix for a profile link.
   *
   * @param string $url
   *   The url of the profile.
   *
   * @return string
   *   The prefix for a profile link.
   */
  public static function generateProfileLinkPrefix(string $url): string {
    return self::PREFIX . Html::escape($url) . '" ';
  }

}
