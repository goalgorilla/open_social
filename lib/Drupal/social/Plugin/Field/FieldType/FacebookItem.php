<?php

/**
 * @file
 * Contains \Drupal\social\Plugin\Field\FieldType\FacebookItem.
 */

namespace Drupal\social\Plugin\Field\FieldType;

/**
 * Plugin implementation of the 'social_facebook' field type.
 *
 * @FieldType(
 *   id = "social_facebook",
 *   label = @Translation("Social (Facebook)"),
 *   description = @Translation("Stores a URL string."),
 *   default_widget = "social",
 *   default_formatter = "social_facebook"
 * )
 */
class FacebookItem extends SocialItemBase {

}
