<?php

/**
 * @file
 * Contains \Drupal\social\Plugin\Field\FieldType\SocialItemBase.
 */

namespace Drupal\social\Plugin\Field\FieldType;

use Drupal\Core\Field\ConfigFieldItemBase;

/**
 * Base class for 'social' configurable field types.
 */
abstract class SocialItemBase extends ConfigFieldItemBase {

  /**
   * Definitions of the contained properties.
   *
   * @var array
   */
  static $propertyDefinitions;

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions() {
    if (!isset(static::$propertyDefinitions)) {
      static::$propertyDefinitions['url'] = array(
        'type' => 'uri',
        'label' => t('URL'),
      );
    }
    return static::$propertyDefinitions;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $item = $this->getValue();
    // Trim any spaces around the URL.
    $this->url = trim($this->url);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return $value === NULL || $value === '';
  }
}
