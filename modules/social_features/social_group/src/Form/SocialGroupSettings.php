<?php

namespace Drupal\social_group\Form;

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
  protected function getEditableConfigNames() :array {
    return [
      'social_group.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() :string {
    return 'social_group_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) :array {
    $config = $this->config('social_group.settings');

    $form['allow_group_selection_in_node'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow logged-in users to change or remove a group when editing content'),
      '#description' => $this->t('When checked, logged-in users can also move content to or out of a group after the content is created. Users can only move content to a group the author is a member of.'),
      '#default_value' => $config->get('allow_group_selection_in_node'),
    ];

    $form['allow_hero_selection'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow logged-in users to choose a different hero size on each group.'),
      '#description' => $this->t('When checked, logged-in users can choose on each group they manage which hero size will be used.'),
      '#default_value' => $config->get('allow_hero_selection'),
    ];

    $form['default_hero'] = [
      '#type' => 'select',
      '#title' => $this->t('The default hero image.'),
      '#description' => $this->t('The default hero size used on this platform. Only applicable when logged-in users cannot choose a different hero size on each group.'),
      '#default_value' => $config->get('default_hero'),
      '#states' => [
        'visible' => [
          ':input[name="allow_hero_selection"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
      '#options' => $this->getCropTypes(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) :void {
    parent::submitForm($form, $form_state);

    $this->config('social_group.settings')
      ->set('allow_group_selection_in_node', $form_state->getValue('allow_group_selection_in_node'))
      ->set('allow_hero_selection', $form_state->getValue('allow_hero_selection'))
      ->set('default_hero', $form_state->getValue('default_hero'))
      ->save();
  }

  /**
   * Function that gets the available crop types.
   *
   * @return array
   *   The croptypes.
   */
  protected function getCropTypes() :array {
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
