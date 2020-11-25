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

    $form['permissions'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Group permissions'),
      '#options' => [
        'allow_group_create' => $this->t('Allow regular users to create new groups'),
        'allow_group_selection_in_node' => $this->t('Allow regular users to change the group their content belong to'),
        'address_visibility_settings' => $this->t('Only show the group address to the group members'),
      ],
    ];

    foreach (array_keys($form['permissions']['#options']) as $permission) {
      if ($config->get($permission)) {
        $form['permissions']['#default_value'][] = $permission;
      }
    }

    $form['default_hero'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default group hero size'),
      '#description' => $this->t('The default hero size used on this platform. Only applicable when logged-in users cannot choose a different hero size on each group.'),
      '#default_value' => $config->get('default_hero'),
      '#options' => $this->getCropTypes(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('social_group.settings');

    foreach ($form_state->getValue('permissions') as $key => $value) {
      $config->set($key, !empty($value));
    }

    $config->set('default_hero', $form_state->getValue('default_hero'))->save();

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

}
