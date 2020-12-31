<?php

namespace Drupal\social_group\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\crop\Entity\CropType;

/**
 * Settings form which enables site managers to configure different options.
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

    $form['address_visibility_settings'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Address visibility settings'),
      '#options' => [
        'street_code_private' => $this->t('Only show street and postal code to group members'),
      ],
      '#default_value' => $config->get('address_visibility_settings'),
    ];

    // Add an option for site manager to enable/disable option to choose group
    // type on page to add flexible groups.
    if (\Drupal::moduleHandler()->moduleExists('social_group_flexible_group')) {
      $form['social_group_type_required'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Group types'),
        '#description' => $this->t('When checked, a new option will appear on the group add form on flexible groups to select group type. Please see available @link.', [
          '@link' => Link::fromTextAndUrl('group types', Url::fromUserInput('/admin/structure/taxonomy/manage/group_type/overview'))->toString(),
        ]),
        '#default_value' => $config->get('social_group_type_required'),
      ];
    }

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
      ->set('address_visibility_settings', $form_state->getValue('address_visibility_settings'))
      ->set('social_group_type_required', $form_state->getValue('social_group_type_required'))
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

}
