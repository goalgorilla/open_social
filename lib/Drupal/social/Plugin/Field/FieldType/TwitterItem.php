<?php

/**
 * @file
 * Contains \Drupal\social\Plugin\Field\FieldType\TwitterItem.
 */

namespace Drupal\social\Plugin\Field\FieldType;

/**
 * Plugin implementation of the 'social_twitter' field type.
 *
 * @FieldType(
 *   id = "social_twitter",
 *   label = @Translation("Social (Twitter)"),
 *   description = @Translation("Stores a URL string."),
 *   default_widget = "social",
 *   default_formatter = "social_twitter"
 * )
 */
class TwitterItem extends SocialItemBase {

}
