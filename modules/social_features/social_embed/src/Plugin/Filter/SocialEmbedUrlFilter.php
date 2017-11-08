<?php

namespace Drupal\social_embed\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\url_embed\Plugin\Filter\ConvertUrlToEmbedFilter;

/**
 * Provides a filter to display embedded entities based on data attributes.
 *
 * @Filter(
 *   id = "social_embed_convert_url",
 *   title = @Translation("Convert SUPPORTED URLs to URL embeds"),
 *   description = @Translation("Convert only URLs that are supported to URL embeds."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "url_prefix" = "",
 *   },
 * )
 */
class SocialEmbedUrlFilter extends ConvertUrlToEmbedFilter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Check for whitelisted URL.
    if ($this->whiteList($text)) {
      return parent::process($text, $langcode);
    }
    // Not whitelisted is return the string as is.
    return new FilterProcessResult($text);
  }

  /**
   * Checks if item is on the whitelist.
   *
   * @param string $text
   *   The item to check for.
   *
   * @return bool
   *   Return if the item is on the whitelist or not.
   */
  public function whiteList($text) {
    // Fetch allowed patterns.
    $patterns = $this->getPatterns();

    // Check if the URL provided is from a whitelisted site.
    foreach ($patterns as $pattern) {
      // Testing pattern.
      $testing_pattern = '/' . $pattern . '/';
      // Check if it matches.
      if (preg_match($testing_pattern, $text)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * A list of whitelisted patterns.
   *
   * @return array
   *   The list of patterns.
   */
  private function getPatterns() {
    return [
      'facebook.com\/(.*)\/videos\/(.*)',
      'facebook.com\/(.*)\/photos\/(.*)',
      'facebook.com\/(.*)\/posts\/(.*)',
      'flickr.com\/photos\/(.*)',
      'flic.kr\/p\/(.*)',
      'instagram.com\/p\/(.*)',
      'open.spotify.com\/track\/(.*)',
      'twitter.com\/(.*)\/status\/(.*)',
      'vimeo.com\/\d{7,9}',
      'youtube.com\/watch[?]v=(.*)',
      'youtu.be\/(.*)',
      'ted.com\/talks\/(.*)',
    ];
  }

}
