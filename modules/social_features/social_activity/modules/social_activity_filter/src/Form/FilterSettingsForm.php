<?php

namespace Drupal\social_activity_filter\Form;

use Drupal\social_activity\Form\FilterSettingsForm as BaseFilterSettingsForm;

/**
 * Provides a settings form of activity filter.
 *
 * @deprecated in social:13.0.0 and is removed from social:14.0.0. Use
 *   \Drupal\social_activity\Form\FilterSettingsForm instead.
 * @see https://www.drupal.org/project/social/issues/3559851
 *
 * @package Drupal\social_activity_filter\Form
 */
class FilterSettingsForm extends BaseFilterSettingsForm {

  /**
   * Constructs a FilterSettingsForm object.
   *
   * @param mixed ...$args
   *   The constructor arguments to pass to the parent class.
   */
  public function __construct(mixed ...$args) {
    @trigger_error('Drupal\social_activity_filter\Form\FilterSettingsForm is deprecated in social:13.0.0 and is removed from social:14.0.0. Use \Drupal\social_activity\Form\FilterSettingsForm instead. See https://www.drupal.org/project/social/issues/3559851', E_USER_DEPRECATED);
    parent::__construct(...$args);
  }

}
