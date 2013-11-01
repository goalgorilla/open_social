<?php

/**
 * @file
 * Contains \Drupal\social\Plugin\Field\FieldType\GoogleItem.
 */

namespace Drupal\social\Plugin\Field\FieldType;

use Drupal\field\FieldInterface;

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
class GoogleItem extends SocialItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldInterface $field) {
    return array(
      'columns' => array(
        'url' => array(
          'description' => 'The URL of the link.',
          'type' => 'varchar',
          'length' => 2048,
          'not null' => FALSE,
        ),
      ),
    );
  }
}
