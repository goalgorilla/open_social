<?php

namespace Drupal\social_editor\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Template\Attribute;

/**
 * Provides a filter that wraps <table> tags with a <div> tag.
 *
 * @Filter(
 *   id = "filter_responsive_table",
 *   title = @Translation("Responsive Table filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "wrapper_element" = "div",
 *     "wrapper_classes" = "table-responsive"
 *   }
 * )
 */
class FilterResponsiveTable extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $result = new FilterProcessResult($text);
    $text = preg_replace_callback('@<table([^>]*)>(.+?)</table>@s', [
      $this,
      'processTableCallback',
    ], $text);
    $result->setProcessedText($text);
    return $result;
  }
  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE, $context = []) {
    return $this->t('Wraps a %table tags with a %div tag.', [
      '%table' => '<table>',
      '%div' => '<' . $this->getWrapperElement() . '>',
    ]);
  }
  /**
   * Callback to replace content of the <table> elements.
   */
  private function processTableCallback(array $matches): string {
    $attributes = $matches[1];
    $text = $matches[2];
    return '<' . $this->getWrapperElement() . $this->getWrapperAttributes() . '><table' . $attributes . '>' . $text . '</table></' . $this->getWrapperElement() . '>';
  }

  /**
   * Get the wrapper HTML element.
   */
  private function getWrapperElement(): string {
    return Xss::filter($this->settings['wrapper_element']);
  }

  /**
   * Get the wrapper CSS class(es).
   */
  private function getWrapperAttributes(): Attribute {
    return new Attribute([
      'class' => [$this->settings['wrapper_classes']],
    ]);
  }
}
