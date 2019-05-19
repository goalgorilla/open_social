<?php

namespace Drupal\social_group_flexible_group\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class GroupAddFlexibilityForm.
 *
 * @package Drupal\social_group_flexible_group\Form
 */
class GroupAddFlexibilityForm {

  use StringTranslationTrait;
  /**
   * Add the options for the FlexibilityForm
   *
   * Only if the group_type is set to flexible_group.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function addFlexibilityOptions(array &$form, FormStateInterface $form_state) {
    $form['flexible_group_visibility_options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Available content visibility'),
      '#description' => t('Choose the possible visibility settings for the group content.'),
      '#options' => self::contentVisibilityOptions(),
      '#states' => [
        'visible' => [
          ':input[name="group_type"]' => ['value' => 'flexible_group']
        ],
      ],
    ];

    $form['flexible_group_join_methods'] = [
      '#type' => 'checkboxes',
      '#title' => t('Method to join'),
      '#description' => t('Choose how people can join this group.'),
      '#options' => self::methodsToJoin(),
      '#states' => [
        'visible' => [
          ':input[name="group_type"]' => ['value' => 'flexible_group']
        ],
      ],
    ];
  }

  /**
   * Possible content visibility options.
   *
   * @return array
   *   Array containing checkbox options.
   */
  public static function contentVisibilityOptions() {
    return ['1' => 'test 1'];
  }

  /**
   * Possible join methods.
   *
   * @return array
   *   Array containing checkbox options.
   */
  public static function methodsToJoin() {
    return ['2' => 'test 2'];
  }

}
