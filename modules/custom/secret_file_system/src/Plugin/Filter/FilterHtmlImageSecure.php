<?php

namespace Drupal\secret_file_system\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\Filter\FilterHtmlImageSecure as FilterHtmlImageSecureBase;

/**
 * Provides support for secret files in the secure image filter.
 */
class FilterHtmlImageSecure extends FilterHtmlImageSecureBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(_secret_file_system_filter_html_image_secure_process($text));
  }

}
