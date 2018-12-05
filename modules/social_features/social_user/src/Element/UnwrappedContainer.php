<?php

namespace Drupal\social_user\Element;

use Drupal\Core\Render\Element\Container;

/**
 * Provides a container element without a div wrapper.
 *
 * Example:
 * @code
 * $build['container'] = [
 *   '#type' => 'unwrapped_container',
 *   'first_child' => [
 *     '#markup' => 'Hello',
 *   ],
 *   'second_child' => [
 *     '#markup' => 'World',
 *   ],
 * ];
 * @endcode
 *
 * @see plugin_api
 *
 * @RenderElement("unwrapped_container")
 */
class UnwrappedContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();

    $info['#theme_wrappers'] = ['unwrapped_container'];

    return $info;
  }

}
