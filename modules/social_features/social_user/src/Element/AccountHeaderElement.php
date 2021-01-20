<?php

namespace Drupal\social_user\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;

/**
 * Provides an account header item element.
 *
 * Example:
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

    return [
      "#pre_render" => [
        [$class, "preRenderAccountHeaderElement"],
      ],
      // The default title attribute for the link.
      "#title" => "",
      // The default href for the link.
      "#url" => Url::fromRoute("<none>"),
      // An optional image used in the link (supersedes the icon).
      "#image" => NULL,
      // An optional icon used in the link.
      "#icon" => NULL,
      // A label for the link, used on mobile.
      "#label" => "",
      // The number of notifications for this menu item.
      // Will be rendered as a visual indicator if greater than 0.
      "#notification_count" => NULL,
      // Allows attaching libraries to the account header item.
      "#attached" => NULL,
    ];
  }

  /**
   * Returns an array that can be provided as an item in an item_list.
   *
   * @param array $item
   *   The render array for this account header element as defined in getInfo.
   *
   * @return array
   *   A render array for an element usable in item_list.
   */
  public static function preRenderAccountHeaderElement(array $item) {
    // Retrieve the item children, if any, sorted by weight.
    $children = Element::children($item, TRUE);

    // The link attributes are for the top level link containe in the <li>.
    $link_attributes = [];

    // The link text is a label with an optional icon or image. If an icon or
    // image is used the CSS hides the label for larger screens.
    $link_text = [];

    // If this link has children then it"s a dropdown.
    if (!empty($children)) {
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
    elseif (!empty($item["#icon"])) {
      // The icon is an SVG icon name without prefix.
      $link_text[] = [
        "#type" => "inline_template",
        "#template" => "<svg class='navbar-nav__icon icon-{{ icon }}'><use xlink:href='#icon-{{ icon }}' /></svg>",
        '#context' => [
          'icon' => $item['#icon'],
        ],
      ];
    }

    // Allow this menu item to include a notification count.
    if ($item['#notification_count'] !== NULL) {
      $count_classes =
        $item['#notification_count'] > 0 ?
          ['badge', 'badge-accent', 'badge--pill'] :
          ['sr-only'];
      $link_text[] = [
        "#type" => "inline_template",
        "#template" => "<span{{ attributes }}>{{ count }}</span>",
        '#context' => [
          'attributes' => new Attribute(['class' => $count_classes]),
          'count' => $item['#notification_count'] > 99 ? "99+" : $item['#notification_count'],
        ],
      ];
    }

    // We always render the label but hide it for non-screenreader users in case
    // an image or icon is used.
    $label_class = !empty($item['#image']) || !empty($item['#icon']) ? 'sr-only' : NULL;
    $link_text[] = [
      "#type" => "inline_template",
      "#template" => "<span{{ attributes }}>{{ label }}</span>",
      '#context' => [
        'attributes' => new Attribute(['class' => [$label_class]]),
        'label' => $item['#label'],
      ],
    ];

    // If the URL is empty then we use a button instead.
    if ($item['#url'] === "") {
      // A custom button is rendered because the Drupal built in button element
      // is not meant to be used outside of forms.
      $element = [
        "#type" => "unwrapped_container",
        "link" => [
          "#type" => "inline_template",
          '#template' => "<button {{ attributes }}>{{ label }}</button>",
          '#context' => [
            "attributes" => new Attribute($link_attributes),
            "label" => $link_text,
          ],
        ],
      ];
    }
    else {
      $element = [
        "#type" => "unwrapped_container",
        "link" => [
          "#type" => "link",
          "#attributes" => $link_attributes,
          "#url" => $item["#url"],
          "#title" => $link_text,
        ],
      ];
    }

    // If there are libraries specified, add them to the element.
    if (!empty($item['#attached'])) {
      $element['#attached'] = $item['#attached'];
    }

    // If there are children we add them to a sublist.
    if (!empty($children)) {
      $element["menu_links"] = [
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
