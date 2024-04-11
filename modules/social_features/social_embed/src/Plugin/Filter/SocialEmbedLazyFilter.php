<?php

namespace Drupal\social_embed\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to lazy-load iframes.
 *
 * @Filter(
 *   id = "social_embed_lazy_filter",
 *   title = @Translation("Lazy-load for iframes"),
 *   description = @Translation("Only selected tags will be lazy-loaded in activated text-formats."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = { },
 *   weight = 999
 * )
 */
class SocialEmbedLazyFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $result = new FilterProcessResult($text);

    if ($this->status) {
      $html_dom = Html::load($text);
      $xpath = new \DOMXPath($html_dom);

      // Return early when the query-result is false/empty.
      $query_result = $xpath->query('//iframe');
      if (!$query_result) {
        return $result;
      }

      /** @var \DOMElement $node */
      foreach ($query_result as $node) {
        // When is an iframe, set loading has lazy.
        if ($node->tagName === 'iframe') {
          $node->setAttribute('loading', 'lazy');
        }
      }

      $result->setProcessedText(Html::serialize($html_dom));
    }

    return $result;
  }

}
