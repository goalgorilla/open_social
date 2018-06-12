<?php

namespace Drupal\social_user\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Provides an account header item element.
 *
 * Example
 * @code
 * $build["account_block"] = [
 *   "#type" => "account_header_element",
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
      "#pre_render" => array(
        array($class, "preRenderAccountHeaderElement"),
      ),
      // This is a custom property for our element type. We set it to blank by
      // default. The expectation is that a user will add the content that they
      // would like to see inside the marquee tag. This custom property is
      // accounted for in the associated template file.
      "#title" => "", // The title attribute for the link
      "#url" => Url::fromRoute("<none>"), // The href for the link
      "#image" => NULL, // An optional image used in the link (supersedes the icon)
      "#icon" => NULL, // An optional icon used in the link
      "#label" => "", // A label for the link, used on mobile
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
    // Retrieve the item children, if any, sorted by weight.
    $children = Element::children($item, TRUE);

    // The wrapper attributes are for the <li> tag.
    $wrapper_attributes = [];

    // The link attributes are for the top level link containe in the <li>.
    $link_attributes = [];
    
    // The link text is a label with an optional icon or image. If an icon or
    // image is used the CSS hides the label for larger screens.
    $link_text = [];

    // If this link has children then it"s a dropdown.
    if (!empty($children)) {
      $wrapper_attributes = [
        "class" => ["dropdown"],
      ];

      $link_attributes = [
        "data-toggle" => "dropdown",
        "aria-expanded" => "true",
        "aria-haspopup" => "true",
        "role" => "button",
        "class" => "dropdown-toggle clearfix",
      ];
    }

    // We always add the title attribute to the link.
    $link_attributes["title"] = $item["#title"];

    // Depending on whether an icon, image or title was set. Configure the link.
    if (!empty($item["#image"])) {
      // The image should be a renderable array so we just add it as link text.
      $link_text[] = $item["#image"];
    }
    else if (!empty($item["#icon"])) {
      // The icon is an SVG icon name without prefix.
      $link_text[] = [
        "#type" => "inline_template",
        "#template" => "<svg class='navbar-nav__icon icon-{{ icon }}'><use xlink:href='#icon-{{ icon }}' /></svg>",
        '#context' => [
          'icon' => $item['#icon']
        ]
      ];
    }

    // We always add the label but hide it on non-mobile screens if there's an
    // image or icon.
    $label_class = !empty($item['#image']) || !empty($item['#icon']) ? 'sr-only' : NULL;
    $link_text[] = [
      "#type" => "inline_template",
      "#template" => "<span{{ attributes }}>{{ label }}</span>",
      '#context' => [
        'attributes' => new Attribute(['class' => [$label_class]]),
        'label' => $item['#label'],
      ]
    ];

    $element = [
      "#wrapper_attributes" => $wrapper_attributes,
      "value" => [
        "#type" => "unwrapped_container",
        "link" => [
          "#type" => "link",
          "#attributes" => $link_attributes,
          "#url" => $item["#url"],
          "#title" => $link_text,
        ],
      ]
    ];

    // If there are children we add them to a sublist.
    if (!empty($children)) {
      $element["value"]["menu_links"] = [
        "#theme" => "item_list",
        '#list_type' => 'ul',
        '#attributes' => [
          'class' => ['dropdown-menu'],
        ],
        "#items" => array_intersect_key($item, array_flip($children)),
      ];
    }

    return $element;
  }
}