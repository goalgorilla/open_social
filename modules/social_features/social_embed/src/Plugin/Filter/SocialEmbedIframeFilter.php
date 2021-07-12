<?php

namespace Drupal\social_embed\Plugin\Filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to wrap iframe items.
 *
 * @Filter(
 *   id = "social_embed_iframe",
 *   title = @Translation("Wrap iframe elements"),
 *   description = @Translation("Wraps all iframe elements within a container."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 * )
 */
class SocialEmbedIframeFilter extends FilterBase {

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
    $result = new FilterProcessResult($text);

    if (stristr($text, '<iframe') === FALSE) {
      return $result;
    }

    $dom = Html::load($text);

    $new_div = $dom->createElement('div');
    $new_div->setAttribute('class', 'iframe-wrapper');

    $iframes = $dom->getElementsByTagName('iframe');

    foreach ($iframes as $iframe) {
      $new_div_clone = $new_div->cloneNode();
      $iframe->parentNode->replaceChild($new_div_clone, $iframe);
      $new_div_clone->appendChild($iframe);
    }

    $result->setProcessedText(Html::serialize($dom));

    return $result;
  }

}
