<?php

namespace Drupal\social_embed\Plugin\Filter;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\url_embed\Plugin\Filter\ConvertUrlToEmbedFilter;
use Embed\Providers\OEmbed\Embedly;


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

  public function whiteList($text) {
    // First check if the string is a valid URL.
    if (!UrlHelper::isValid($text, TRUE)) {
      return FALSE;
    }

    // Fetch all patterns known by Embed/Embed.
    $patterns = Embedly::getPatterns();

    // Check if the URL provided is from a whitelisted site.
    foreach ($patterns as $pattern) {
      if (strpos($text, $pattern) !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
