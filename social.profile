<?php
/**
 * @file
 * Enables modules and site configuration for a social site installation.
 */

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_install_tasks().
 */
function social_install_tasks(&$install_state) {
  $tasks = array(
    'social_install_profile_modules' => array(
      'display_name' => t('Install Open Social modules'),
      'type' => 'batch',
    ),
    'social_final_site_setup' => array(
      'display_name' => t('Apply configuration'),
      'type' => 'batch',
      'display' => TRUE,
    ),
  );
  return $tasks;
}

/**
 * Implements hook_install_tasks_alter().
 *
 * Unfortunately we have to alter the verify requirements.
 * This is because of https://www.drupal.org/node/1253774. The dependencies of
 * dependencies are not tested. So adding requirements to our install profile
 * hook_requirements will not work :(. Also take a look at install.inc function
 * drupal_check_profile() it just checks for all the dependencies of our
 * install profile from the info file. And no actually hook_requirements in
 * there.
 */
function social_install_tasks_alter(&$tasks, $install_state) {
  // Override the core install_verify_requirements task function.
  $tasks['install_verify_requirements']['function'] = 'social_verify_custom_requirements';
  // Override the core finished task function.
  $tasks['install_finished']['function'] = 'social_install_finished';
}

/**
 * install_verify_requirements callback, make sure we meet custom requirement.
 *
 * @param array $install_state
 *   The current install state.

 * @return array
 *   All the requirements we need to meet.
 */
function social_verify_custom_requirements(&$install_state) {
  // Copy pasted from install_verify_requirements().
  // @todo when composer hits remove this.
  // Check the installation requirements for Drupal and this profile.
  $requirements = install_check_requirements($install_state);

  // Verify existence of all required modules.
  $requirements += drupal_verify_profile($install_state);

  // Added a custom check for users to see if the Address libraries are
  // downloaded.
  if (!class_exists('\CommerceGuys\Addressing\Repository\AddressFormatRepository')) {
    $requirements['addressing_library'] = [
      'title' => t('Address module requirements)'),
      'value' => t('Not installed'),
      'description' => t('The Address module requires the commerceguys/addressing library. <a href=":link" target="_blank">For more information check our readme</a>', array(':link' => 'https://github.com/goalgorilla/drupal_social/blob/master/readme.md#install-from-project-page-on-drupalorg')),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  if (!class_exists('\CommerceGuys\Enum\AbstractEnum')) {
    $requirements['addressing_library_enum'] = [
      'title' => t('Address module requirements)'),
      'value' => t('Not installed'),
      'description' => t('The Address module requires the commerceguys/enum library. <a href=":link" target="_blank">For more information check our readme</a>', array(':link' => 'https://github.com/goalgorilla/drupal_social/blob/master/readme.md#install-from-project-page-on-drupalorg')),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  if (!class_exists('\CommerceGuys\Intl\Country\CountryRepository')) {
    $requirements['addressing_library_country'] = [
      'title' => t('Address module requirements)'),
      'value' => t('Not installed'),
      'description' => t('The Address module requires the commerceguys/intl library. <a href=":link" target="_blank">For more information check our readme</a>', array(':link' => 'https://github.com/goalgorilla/drupal_social/blob/master/readme.md#install-from-project-page-on-drupalorg')),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  if (!class_exists('\CommerceGuys\Zone\Repository\ZoneRepository')) {
    $requirements['addressing_library_zone'] = [
      'title' => t('Address module requirements)'),
      'value' => t('Not installed'),
      'description' => t('The Address module requires the commerceguys/zone library. <a href=":link" target="_blank">For more information check our readme</a>', array(':link' => 'https://github.com/goalgorilla/drupal_social/blob/master/readme.md#install-from-project-page-on-drupalorg')),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  // Check to see if bcmath extension is actually available.
  $bc_math_enabled = (extension_loaded('bcmath'));
  if (!$bc_math_enabled) {
    $requirements['bcmatch'] = array(
      'title' => t('BC Math'),
      'value' => t('Not installed'),
      'severity' => REQUIREMENT_ERROR,
      'description' => t('the PHP BC Math library is not installed (correctly). <a href=":link" target="_blank">For more information check our readme</a>', array(':link' => 'https://github.com/goalgorilla/drupal_social/blob/master/readme.md#install-from-project-page-on-drupalorg')),
    );
  }

  return install_display_requirements($install_state, $requirements);
}

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function social_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {

  // Add 'Social' fieldset and options.
  $form['social'] = [
    '#type' => 'fieldgroup',
    '#title' => t('Open Social optional configuration'),
    '#description' => t('All the required modules and configuration will be automatically installed and imported. You can optionally select additional features or generated demo content.'),
    '#weight' => 50,
  ];

  $social_optional_modules = [
    'social_book' => t('Book functionality'),
    'social_sharing' => t('Share content on social media'),
  ];

  // Checkboxes to enable Optional modules.
  $form['social']['optional_modules'] = [
    '#type' => 'checkboxes',
    '#title' => t('Enable additional features'),
    '#options' => $social_optional_modules,
    '#default_value' => [],
  ];

  // Checkboxes to generate demo content.
  $form['social']['demo_content'] = [
    '#type' => 'checkbox',
    '#title' => t('Generate demo content and users'),
    '#description' => 'Will generate files, users, groups, events, topics, comments and posts.',
  ];

  // Submit handler to enable features.
  $form['#submit'][] = 'social_features_submit';
}

/**
 * Submit handler.
 */
function social_features_submit($form_id, &$form_state) {
  $optional_modules = array_filter($form_state->getValue('optional_modules'));
  \Drupal::state()->set('social_install_optional_modules', $optional_modules);
  \Drupal::state()->set('social_install_demo_content', $form_state->getValue('demo_content'));
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
    'social_post' => 'social_post',
    'social_page' => 'social_page',
    'social_search' => 'social_search',
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
    'title' => t('Install Open Social modules'),
    'error_message' => t('The installation has encountered an error.'),
  );
  return $batch;
}

/**
 * @param $install_state
 */
function social_final_site_setup(&$install_state) {
  // Clear all status messages generated by modules installed in previous step.
  drupal_get_messages('status', TRUE);

  node_access_needs_rebuild(FALSE); // There is no content at this point.

  $batch = array();

  $social_optional_modules = \Drupal::state()->get('social_install_optional_modules');
  foreach ($social_optional_modules as $module => $module_name) {
    $batch['operations'][] = ['_social_install_module_batch', array(array($module), $module_name)];
  }
  $demo_content = \Drupal::state()->get('social_install_demo_content');
  if ($demo_content === 1) {
    $batch['operations'][] = ['_social_install_module_batch', array(array('social_demo'), 'social_demo')];

    // Generate demo content.
    $demo_content_types = [
      'file' => t('files'),
      'user' => t('users'),
      'group' => t('groups'),
      'topic' => t('topics'),
      'event' => t('events'),
      'eventenrollment' => t('event enrollments'),
      'post' => t('posts'),
      'comment' => t('comments'),
    ];
    foreach ($demo_content_types as $demo_type => $demo_description) {
      $batch['operations'][] = ['_social_add_demo_batch', array($demo_type, $demo_description)];
    }

    // Uninstall social_demo.
    $batch['operations'][] = ['_social_uninstall_module_batch', array(array('social_demo'), 'social_demo')];
  }

  // Add some finalising steps.
  $final_batched = [
    'profile_weight' => t('Set weight of profile.'),
    'router_rebuild' => t('Rebuild router.'),
    'cron_run' => t('Run cron.'),
  ];
  foreach ($final_batched as $process => $description) {
    $batch['operations'][] = ['_social_finalise_batch', array($process, $description)];
  }

  return $batch;
}

/**
 * Performs final installation steps and displays a 'finished' page.
 *
 * @param $install_state
 *   An array of information about the current installation state.
 *
 * @return
 *   A message informing the user that the installation is complete.
 *
 * @see install_finished().
 */
function social_install_finished(&$install_state) {
  // Clear all status messages generated by modules installed in previous step.
  drupal_get_messages('status', TRUE);

  if ($install_state['interactive']) {
    // Load current user and perform final login tasks.
    // This has to be done after drupal_flush_all_caches()
    // to avoid session regeneration.
    $account = User::load(1);
    user_login_finalize($account);
  }

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
  $context['message'] = t('Install %module_name module.', array('%module_name' => $module_name));
}

/**
 * Implements callback_batch_operation().
 *
 * Performs batch uninstallation of modules.
 */
function _social_uninstall_module_batch($module, $module_name, &$context) {
  set_time_limit(0);
  \Drupal::service('module_installer')->uninstall($module, $dependencies = FALSE);
  $context['results'][] = $module;
  $context['message'] = t('Uninstalled %module_name module.', array('%module_name' => $module_name));
}

/**
 * Implements callback_batch_operation().
 *
 * Performs batch demo content generation.
 */
function _social_add_demo_batch($demo_type, $demo_description, &$context) {
  set_time_limit(0);

  $num_created = 0;

  // Create an instance of the necessary class.
  $className = "\Drupal\social_demo\Content\SocialDemo" . ucfirst($demo_type);

  if (class_exists($className)) {
    $container = \Drupal::getContainer();
    $class = $className::create($container);
    $num_created = $class->createContent();
  }

  $context['results'][] = $demo_type;
  $context['message'] = t('Generated %num %demo_description.', array('%num' => $num_created, '%demo_description' => $demo_description));
}

/**
 * Implements callback_batch_operation().
 *
 * Performs batch finalising.
 */
function _social_finalise_batch($process, $description, &$context) {

  switch ($process) {
    case 'profile_weight':
      $profile = drupal_get_profile();

      // Installation profiles are always loaded last.
      module_set_weight($profile, 1000);
      break;

    case 'router_rebuild':
      // Build the router once after installing all modules.
      // This would normally happen upon KernelEvents::TERMINATE, but since the
      // installer does not use an HttpKernel, that event is never triggered.
      \Drupal::service('router.builder')->rebuild();
      break;

    case 'cron_run':
      // Run cron to populate update status tables (if available) so that users
      // will be warned if they've installed an out of date Drupal version.
      // Will also trigger indexing of profile-supplied content or feeds.
      \Drupal::service('cron')->run();
      break;

  }

  $context['results'][] = $process;
  $context['message'] = $description;
}
