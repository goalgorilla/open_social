<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\crop\Entity\CropType;

/**
 * Class SocialGroupSettings.
 *
 * @package Drupal\social_event_managers\Form
 */
class SocialGroupSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'social_group.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_group_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_group.settings');

    $form['allow_group_selection_in_node'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow logged-in users to change or remove a group when editing content'),
      '#description' => $this->t('When checked, logged-in users can also move content to or out of a group after the content is created. Users can only move content to a group the author is a member of.'),
      '#default_value' => $config->get('allow_group_selection_in_node'),
    ];

    $form['default_hero'] = [
      '#type' => 'select',
      '#title' => $this->t('The default hero image.'),
      '#description' => $this->t('The default hero size used on this platform. Only applicable when logged-in users cannot choose a different hero size on each group.'),
      '#default_value' => $config->get('default_hero'),
      '#options' => $this->getCropTypes(),
    ];

    // Group the settings for visibility options.
    $form['visibility_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Group visibility settings'),
    ];

    $form['visibility_settings']['available_visibility_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select available visibility options'),
      '#description' => $this->t('Determines which visibility options should be available when creating a new group.'),
      '#default_value' => $config->get('available_visibility_options'),
      '#options' => $this->getVisibilityOptions(),
    ];

    $form['visibility_settings']['hide_visibility_options'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide visibility options'),
      '#description' => $this->t('Hide all visibility options.'),
      '#default_value' => $config->get('hide_visibility_options'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('social_group.settings')
      ->set('allow_group_selection_in_node', $form_state->getValue('allow_group_selection_in_node'))
      ->set('default_hero', $form_state->getValue('default_hero'))
      ->set('available_visibility_options', $form_state->getValue('available_visibility_options'))
      ->set('hide_visibility_options', $form_state->getValue('hide_visibility_options'))
      ->save();

    Cache::invalidateTags(['group_view']);
  }

  /**
   * Function that gets the available crop types.
   *
   * @return array
   *   The croptypes.
   */
  protected function getCropTypes() {
    $croptypes = [
      'hero',
      'hero_small',
    ];

    $options = [];

    foreach ($croptypes as $croptype) {
      $type = CropType::load($croptype);
      if ($type instanceof CropType) {
        $options[$type->id()] = $type->label();
      }
    }

    return $options;
  }

  /**
   * Return the available group content visibility options.
   *
   * @return array
   *   Array with options.
   */
  protected function getVisibilityOptions() {
    $options = [];
    $visibility_options = social_group_get_allowed_visibility_options_per_group_type(NULL);
    foreach ($visibility_options as $key => $value) {
      $options[$key] = ucfirst($key);
    }

    return $options;
  }

}
