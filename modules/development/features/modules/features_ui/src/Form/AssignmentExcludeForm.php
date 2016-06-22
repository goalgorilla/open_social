<?php

/**
 * @file
 * Contains \Drupal\features_ui\Form\AssignmentExcludeForm.
 */

namespace Drupal\features_ui\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Configures the selected configuration assignment method for this site.
 */
class AssignmentExcludeForm extends AssignmentFormBase {

  const METHOD_ID = 'exclude';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'features_assignment_exclude_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $bundle_name = NULL) {
    $this->currentBundle = $this->assigner->loadBundle($bundle_name);

    $settings = $this->currentBundle->getAssignmentSettings(self::METHOD_ID);
    $this->setConfigTypeSelect($form, $settings['types']['config'], $this->t('exclude'));

    $module_settings = $settings['module'];
    $curated_settings = $settings['curated'];

    $form['curated'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude designated site-specific configuration'),
      '#default_value' => $curated_settings,
      '#description' => $this->t('Select this option to exclude from packaging items on a curated list of site-specific configuration.'),
    );

    $form['module'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
    );
    $form['module']['installed'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude installed module-provided entity configuration'),
      '#default_value' => $module_settings['installed'],
      '#description' => $this->t('Select this option to exclude from packaging any configuration that is provided by already installed modules.'),
      '#attributes' => array(
        'data-module-installed' => 'status',
      ),
    );

    $show_if_module_installed_checked = array(
      'visible' => array(
        ':input[data-module-installed="status"]' => array('checked' => TRUE),
      ),
    );

    $info = system_get_info('module', drupal_get_profile());
    $form['module']['profile'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Don't exclude install profile's configuration"),
      '#default_value' => $module_settings['profile'],
      '#description' => $this->t("Select this option to not exclude from packaging any configuration that is provided by this site's install profile, %profile.", array('%profile' => $info['name'])),
      '#states' => $show_if_module_installed_checked,
    );

    $machine_name = $this->currentBundle->getMachineName();
    $machine_name = !empty($machine_name) ? $machine_name : t('none');
    $form['module']['namespace'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Don't exclude non-installed configuration by namespace"),
      '#default_value' => $module_settings['namespace'],
      '#description' => $this->t("Select this option to not exclude from packaging any configuration that is provided by non-installed modules with the package namespace (currently %namespace).", array('%namespace' => $machine_name)),
      '#states' => $show_if_module_installed_checked,
      '#attributes' => array(
        'data-namespace' => 'status',
      ),
    );

    $show_if_namespace_checked = array(
      'visible' => array(
        ':input[data-namespace="status"]' => array('checked' => TRUE),
        ':input[data-module-installed="status"]' => array('checked' => TRUE),
      ),
    );

    $form['module']['namespace_any'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t("Don't exclude ANY configuration by namespace"),
      '#default_value' => $module_settings['namespace_any'],
      '#description' => $this->t("Select this option to not exclude from packaging any configuration that is provided by ANY modules with the package namespace (currently %namespace).
        Warning: Can cause installed configuration to be reassigned to different packages.", array('%namespace' => $machine_name)),
      '#states' => $show_if_namespace_checked,
    );

    $this->setActions($form);

    return $form;
  }

 /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('types', array_map('array_filter', $form_state->getValue('types')));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Merge in selections.
    $settings = $this->currentBundle->getAssignmentSettings(self::METHOD_ID);
    $settings = array_merge($settings, [
      'types' => $form_state->getValue('types'),
      'curated' => $form_state->getValue('curated'),
      'module' => $form_state->getValue('module'),
    ]);

    $this->currentBundle->setAssignmentSettings(self::METHOD_ID, $settings)->save();

    $this->setRedirect($form_state);
    drupal_set_message($this->t('Package assignment configuration saved.'));
  }

}
