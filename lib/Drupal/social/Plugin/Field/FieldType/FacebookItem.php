<?php

/**
 * @file
 * Contains \Drupal\social\Plugin\Field\FieldType\FacebookItem.
 */

namespace Drupal\social\Plugin\Field\FieldType;

use Drupal\field\FieldInterface;

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
