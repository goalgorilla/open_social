<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\Link.
 */

namespace Drupal\message\Plugin\views\field;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\message\Entity\Message;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to the node.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("get_text")
 */
class GetText extends FieldPluginBase {

  /**
   * Stores the result of node_view_multiple for all rows to reuse it later.
   *
   * @var array
   */
  protected $build;

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return new FormattableMarkup(implode($values->_entity->getText(), "\n"), []);
  }

}
