<?php

/**
 * @file
 * Contains \Drupal\field_group\Element\AccordionItem.
 */

namespace Drupal\field_group\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a render element for an accordion item.
 *
 * @FormElement("field_group_accordion_item")
 */
class AccordionItem extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#collapsed' => FALSE,
      '#theme_wrappers' => array('field_group_accordion_item'),
    );
  }

}
