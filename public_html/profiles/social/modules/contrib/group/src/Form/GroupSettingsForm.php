<?php
/**
 * @file
 * Contains Drupal\group\Form\GroupSettingsForm.
 */

namespace Drupal\group\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GroupSettingsForm.
 */
class GroupSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'group_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['group.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $config = $this->config('group.settings');
    $form['use_admin_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use admin theme'),
      '#description' => $this->t("Enables the administration theme for editing groups, members, etc."),
      '#default_value' => $config->get('use_admin_theme'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('group.settings');
    $conf_admin_theme = $config->get('use_admin_theme');
    $form_admin_theme = $form_state->getValue('use_admin_theme');

    // Only rebuild the routes if the admin theme switch has changed.
    if ($conf_admin_theme != $form_admin_theme) {
      $config->set('use_admin_theme', $form_admin_theme)->save();
      \Drupal::service('router.builder')->setRebuildNeeded();
    }

    parent::submitForm($form, $form_state);
  }

}
