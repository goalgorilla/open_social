<?php
/**
 * @file
 * Enables modules and site configuration for a social site installation.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_install_tasks().
 */
function social_install_tasks(&$install_state) {
  $tasks = array(
    'social_install_profile_modules' => array(
      'display_name' => t('Install social features'),
      'type' => 'batch',
    ),
    'social_final_site_setup' => array(
    ),
  );
  return $tasks;
}

/**
 * Installs required modules via a batch process.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   The batch definition.
 */
function social_install_profile_modules(&$install_state) {

  $files = system_rebuild_module_data();

  $modules = array(
    'social_core' => 'social_core',
    'social_user' => 'social_user',
    'social_group' => 'social_group',
    'social_event' => 'social_event',
    'social_topic' => 'social_topic',
    'social_profile' => 'social_profile',
    'social_editor' => 'social_editor',
    'social_comment' => 'social_comment',
    'social_search' => 'social_search',
    'social_post' => 'social_post',
    'social_activity' => 'social_activity',
  );
  $social_modules = $modules;
  // Always install required modules first. Respect the dependencies between
  // the modules.
  $required = array();
  $non_required = array();

  // Add modules that other modules depend on.
  foreach ($modules as $module) {
    if ($files[$module]->requires) {
      $module_requires = array_keys($files[$module]->requires);
      // Remove the social modules from required modules.
      $module_requires = array_diff_key($module_requires, $social_modules);
      $modules = array_merge($modules, $module_requires);
    }
  }
  $modules = array_unique($modules);
  // Remove the social modules from to install modules.
  $modules = array_diff_key($modules, $social_modules);
  foreach ($modules as $module) {
    if (!empty($files[$module]->info['required'])) {
      $required[$module] = $files[$module]->sort;
    }
    else {
      $non_required[$module] = $files[$module]->sort;
    }
  }
  arsort($required);

  $operations = array();
  foreach ($required + $non_required + $social_modules as $module => $weight) {
    $operations[] = array('_social_install_module_batch', array(array($module), $module));
  }

  $batch = array(
    'operations' => $operations,
    'title' => t('Installing Social features'),
    'error_message' => t('The installation has encountered an error.'),
  );
  return $batch;
}

/**
 * Implements callback_batch_operation().
 *
 * Performs batch installation of modules.
 */
function _social_install_module_batch($module, $module_name, &$context) {
  set_time_limit(0);
  \Drupal::service('module_installer')->install($module, $dependencies = TRUE);
  $context['results'][] = $module;
  $context['message'] = t('Installed %module_name modules.', array('%module_name' => $module_name));
}

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function social_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  // Add a placeholder as example that one can choose an arbitrary site name.
  $form['site_information']['site_name']['#attributes']['placeholder'] = t('Open Social');
}

/**
 * @param $install_state
 */
function social_final_site_setup(&$install_state) {
  // Rebuild permissions.
  node_access_rebuild(); // TODO Do not set message?
  // TODO node_access_needs_rebuild(FALSE) is also good because no content yet?
  // TODO Enable demo and devel, generate demo content via batch?
}
