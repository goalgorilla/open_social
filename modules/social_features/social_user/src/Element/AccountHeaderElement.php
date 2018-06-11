<?php

namespace Drupal\social_user\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;

/**
 * Provides an account header item element.
 *
 * Example
 * @code
 * $build['account_block'] = [
 *   '#type' => 'account_header_element',
 * ];
 * @endcode
 *
 * @see plugin_api
 *
 * @RenderElement("account_header_element")
 */
class AccountHeaderElement extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    // Returns an array of default properties that will be merged with any
    // properties defined in a render array when using this element type.
    // You can use any standard render array property here, and you can also
    // custom properties that are specific to your new element type.
    return [
      // Define a default #pre_render method. We will use this to handle
      // additional processing for the custom attributes we add below.
      '#pre_render' => array(
        array($class, 'preRenderAccountHeaderElement'),
      ),
      // This is a custom property for our element type. We set it to blank by
      // default. The expectation is that a user will add the content that they
      // would like to see inside the marquee tag. This custom property is
      // accounted for in the associated template file.
      '#title' => '', // The title attribute for the link
      '#url' => Url::fromRoute('<none>'), // The href for the link
      '#image' => NULL, // An optional image used in the link (supersedes the icon)
      '#icon' => NULL, // An optional icon used in the link
      '#label' => '', // A label for the link, used on mobile
    ];
  }

  /**
   * Returns an array that can be provided as an item in an item_list.
   *
   * @param $item
   *
   * @return array
   */
  public static function preRenderAccountHeaderElement($item) {
    $link = [
      '#type' => 'link',
      '#attributes' => [
        '#title' => $item['title'],
      ],
      '#url' => $item['#url'],
    ];

    $element = [
      '#wrapper_attributes' => [],
      'value' => [

      ]
    ];

    return $element;
  }
}