<?php

/**
 * @file
 * Contains \Drupal\social\Plugin\Field\FieldType\GoogleItem.
 */

namespace Drupal\social\Plugin\Field\FieldType;

/**
 * Plugin implementation of the 'social_google' field type.
 *
 * @FieldType(
 *   id = "social_google",
 *   label = @Translation("Social (Google)"),
 *   description = @Translation("Stores a URL string."),
 *   default_widget = "social",
 *   default_formatter = "social_google"
 * )
 */
class GoogleItem extends SocialItemBase {}
