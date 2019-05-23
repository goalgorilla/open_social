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
    $form['group_settings']['flexible_settings'] = [
      '#type' => 'fieldset',
    ];

    $form['group_settings']['flexible_settings']['flexible_group_visibility_options'] = [
      '#type' => 'checkboxes',
      '#title' => t('Available content visibility'),
      '#description_display' => 'before',
      '#description' => t('Choose the possible visibility settings for the group content.'),
      '#options' => self::contentVisibilityOptions(),
      '#states' => [
        'visible' => [
          ':input[name="group_type"]' => ['value' => 'flexible_group']
        ],
      ],
    ];

    $form['group_settings']['flexible_settings']['flexible_group_join_methods'] = [
      '#type' => 'checkboxes',
      '#description_display' => 'before',
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
    return [
      'public' => t('Public'),
      'community' => t('Community'),
      'group_member' => t('Member Only'),
    ];
  }

  /**
   * Possible join methods.
   *
   * @return array
   *   Array containing checkbox options.
   */
  public static function methodsToJoin() {
    return [
      'directly' => t('Join Directly'),
      'added' => t('Be added'),
    ];
  }

}
