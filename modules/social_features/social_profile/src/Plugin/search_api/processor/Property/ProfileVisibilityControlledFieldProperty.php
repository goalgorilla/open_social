<?php

declare(strict_types=1);

namespace Drupal\social_profile\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "profile visibility controlled field" property.
 *
 * This property is used to create fields that store visibility dependent values
 * for search index fields that contain profile data. By creating a new property
 * type we can create the derived value. Using an existing property would fill
 * all of the derived fields with the same value which is not what we want.
 *
 * @see \Drupal\social_profile\Plugin\search_api\processor\ProfileFieldVisibilityProcessor
 */
class ProfileVisibilityControlledFieldProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'value_property_path' => '',
      'visibility_property_path' => '',
      'property_visibility' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    // This property is not user configurable but only machine configurable.
    return [];
  }

}
