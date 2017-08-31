<?php

/**
 * @file
 * Enables modules and site configuration for a social site installation.
 */

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormStateInterface;
use Drupal\features\FeaturesManagerInterface;
use Drupal\features\ConfigurationItem;
use Drupal\search_api\Entity\Index;

/**
 * Implements hook_install_tasks().
 */
function social_install_tasks(&$install_state) {
  $tasks = [
    'social_install_profile_modules' => [
      'display_name' => t('Install Open Social modules'),
      'type' => 'batch',
    ],
    'social_final_site_setup' => [
      'display_name' => t('Apply configuration'),
      'type' => 'batch',
      'display' => TRUE,
    ],
    'social_theme_setup' => [
      'display_name' => t('Apply theme'),
      'display' => TRUE,
    ],
  ];
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
 * Callback for install_verify_requirements, so that we meet custom requirement.
 *
 * @param array $install_state
 *   The current install state.
 *
 * @return array
 *   All the requirements we need to meet.
 */
function social_verify_custom_requirements(array &$install_state) {
  // Copy pasted from install_verify_requirements().
  // @todo when composer hits remove this.
  // Check the installation requirements for Drupal and this profile.
  $requirements = install_check_requirements($install_state);

  // Verify existence of all required modules.
  $requirements += drupal_verify_profile($install_state);

  // Added a custom check for users to see if the Address libraries are
  // downloaded.
  if (!class_exists('\CommerceGuys\Addressing\Address')) {
    $requirements['addressing_library'] = [
      'title' => t('Address module requirements)'),
      'value' => t('Not installed'),
      'description' => t('The Address module requires the commerceguys/addressing library. <a href=":link" target="_blank">For more information check our readme</a>', [':link' => 'https://github.com/goalgorilla/drupal_social/blob/master/readme.md#install-from-project-page-on-drupalorg']),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  if (!class_exists('\Facebook\Facebook')) {
    $requirements['social_auth_facebook'] = [
      'title' => t('Social Auth Facebook module requirements'),
      'value' => t('Not installed'),
      'description' => t('Social Auth Facebook requires Facebook PHP Library. Make sure the library is installed via Composer.'),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  if (!class_exists('\Google_Client')) {
    $requirements['social_auth_google'] = [
      'title' => t('Social Auth Google module requirements'),
      'value' => t('Not installed'),
      'description' => t('Social Auth Google requires Google_Client PHP Library. Make sure the library is installed via Composer.'),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  if (!class_exists('\Happyr\LinkedIn\LinkedIn')) {
    $requirements['social_auth_linkedin'] = [
      'title' => t('Social Auth LinkedIn module requirements'),
      'value' => t('Not installed'),
      'description' => t('Social Auth LinkedIn requires LinkedIn PHP Library. Make sure the library is installed via Composer.'),
      'severity' => REQUIREMENT_ERROR,
    ];
  }

  if (!class_exists('\Abraham\TwitterOAuth\TwitterOAuth')) {
    $requirements['social_auth_twitter'] = [
      'title' => t('Social Auth Twitter module requirements'),
      'value' => t('Not installed'),
      'description' => t('Social Auth Twitter requires TwitterOAuth PHP Library. Make sure the library is installed via Composer.'),
      'severity' => REQUIREMENT_ERROR,
    ];
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
    'social_event_type' => t('Categorize events in event types'),
    'social_sso' => t('Registration with social networks'),
    'social_file_private' => t('Use the private file system for uploaded files (highly recommended)'),
  ];

  // Checkboxes to enable Optional modules.
  $form['social']['optional_modules'] = [
    '#type' => 'checkboxes',
    '#title' => t('Enable additional features'),
    '#options' => $social_optional_modules,
    '#default_value' => [
      'social_file_private',
    ],
  ];

  // Checkboxes to generate demo content.
  $form['social']['demo_content'] = [
    '#type' => 'checkbox',
    '#title' => t('Generate demo content and users'),
    '#description' => t('Will generate files, users, groups, events, topics, comments and posts.'),
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
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @return array
 *   The batch definition.
 */
function social_install_profile_modules(array &$install_state) {

  $files = system_rebuild_module_data();

  $modules = [
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
    'social_follow_content' => 'social_follow_content',
    'social_mentions' => 'social_mentions',
    'social_font' => 'social_font',
    'social_like' => 'social_like',
    'social_post_photo' => 'social_post_photo',
    'social_swiftmail' => 'social_swiftmail',
  ];
  $social_modules = $modules;
  // Always install required modules first. Respect the dependencies between
  // the modules.
  $required = [];
  $non_required = [];

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

  $operations = [];
  foreach ($required + $non_required + $social_modules as $module => $weight) {
    $operations[] = [
      '_social_install_module_batch',
      [[$module], $module],
    ];
  }

  $batch = [
    'operations' => $operations,
    'title' => t('Install Open Social modules'),
    'error_message' => t('The installation has encountered an error.'),
  ];
  return $batch;
}

/**
 * Final setup of Social profile.
 *
 * @param array $install_state
 *   The install state.
 *
 * @return array
 *   Batch settings.
 */
function social_final_site_setup(array &$install_state) {
  // Clear all status messages generated by modules installed in previous step.
  drupal_get_messages('status', TRUE);

  // There is no content at this point.
  node_access_needs_rebuild(FALSE);

  $batch = [];

  $social_optional_modules = \Drupal::state()->get('social_install_optional_modules');
  foreach ($social_optional_modules as $module => $module_name) {
    $batch['operations'][] = [
      '_social_install_module_batch',
      [[$module], $module_name],
    ];
  }
  $demo_content = \Drupal::state()->get('social_install_demo_content');
  if ($demo_content === 1) {
    $batch['operations'][] = [
      '_social_install_module_batch',
      [['social_demo'], 'social_demo'],
    ];

    // Generate demo content.
    $demo_content_types = [
      'file' => t('files'),
      'user' => t('users'),
      'group' => t('groups'),
      'topic' => t('topics'),
      'event' => t('events'),
      'event_enrollment' => t('event enrollments'),
      'post' => t('posts'),
      'comment' => t('comments'),
      'like' => t('likes'),
      // @todo Add 'event_type' if module is enabled.
    ];
    foreach ($demo_content_types as $demo_type => $demo_description) {
      $batch['operations'][] = [
        '_social_add_demo_batch',
        [$demo_type, $demo_description],
      ];
    }

    // Uninstall social_demo.
    $batch['operations'][] = [
      '_social_uninstall_module_batch',
      [['social_demo'], 'social_demo'],
    ];
  }

  // Add some finalising steps.
  $final_batched = [
    'profile_weight' => t('Set weight of profile.'),
    'router_rebuild' => t('Rebuild router.'),
    'trigger_sapi_index' => t('Index search'),
    'cron_run' => t('Run cron.'),
    'import_optional_config' => t('Import optional configuration'),
  ];
  foreach ($final_batched as $process => $description) {
    $batch['operations'][] = [
      '_social_finalise_batch',
      [$process, $description],
    ];
  }

  return $batch;
}

/**
 * Install the theme.
 *
 * @param array $install_state
 *   The install state.
 */
function social_theme_setup(array &$install_state) {
  // Clear all status messages generated by modules installed in previous step.
  drupal_get_messages('status', TRUE);

  // Also install improved theme settings & color module, because it improves
  // the social blue theme settings page.
  $modules = ['color'];
  \Drupal::service('module_installer')->install($modules);

  $themes = ['socialblue'];
  \Drupal::service('theme_handler')->install($themes);

  \Drupal::configFactory()
    ->getEditable('system.theme')
    ->set('default', 'socialblue')
    ->save();

  // Ensure that the install profile's theme is used.
  // @see _drupal_maintenance_theme()
  \Drupal::service('theme.manager')->resetActiveTheme();

  $modules = ['improved_theme_settings'];
  \Drupal::service('module_installer')->install($modules);
}

/**
 * Performs final installation steps and displays a 'finished' page.
 *
 * @param array $install_state
 *   An array of information about the current installation state.
 *
 * @see install_finished()
 */
function social_install_finished(array &$install_state) {
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

  $content_types = array($demo_type);
  $manager = \Drupal::service('plugin.manager.demo_content');
  $plugins = $manager->createInstances($content_types);

  /** @var \Drupal\social_demo\DemoContentInterface $plugin */
  foreach ($plugins as $plugin) {
    $plugin->createContent();
    $num_created = $plugin->count();
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

    case 'trigger_sapi_index':
      $indexes = Index::loadMultiple();
      /** @var \Drupal\search_api\Entity\Index $index */
      foreach ($indexes as $index) {
        $index->reindex();
      }
      break;

    case 'cron_run':
      // Run cron to populate update status tables (if available) so that users
      // will be warned if they've installed an out of date Drupal version.
      // Will also trigger indexing of profile-supplied content or feeds.
      \Drupal::service('cron')->run();
      break;

    case 'import_optional_config':
      // We need to import all the optional configuration as well, since
      // this is not supported by Drupal Core installation profiles yet.
      /** @var \Drupal\features\FeaturesAssignerInterface $assigner */
      $assigner = \Drupal::service('features_assigner');

      $bundle = $assigner->applyBundle('social');
      if ($bundle->getMachineName() === 'social') {
        $current_bundle = $bundle;

        /** @var \Drupal\features\FeaturesManagerInterface $manager */
        $manager = \Drupal::service('features.manager');
        $packages = $manager->getPackages();

        $packages = $manager->filterPackages($packages, $current_bundle->getMachineName());

        $options = [];
        foreach ($packages as $package) {
          if ($package->getStatus() != FeaturesManagerInterface::STATUS_NO_EXPORT) {
            $missing = $manager->reorderMissing($manager->detectMissing($package));
            $overrides = $manager->detectOverrides($package, TRUE);
            if (!empty($overrides) || !empty($missing)) {
              $options += [
                $package->getMachineName() => [],
              ];
            }
          }
        }

        /** @var \Drupal\features\FeaturesManagerInterface $manager */
        $manager = \Drupal::service('features.manager');
        $packages = $manager->getPackages();
        $packages = $manager->filterPackages($packages, 'social');
        $overridden = [];

        foreach ($packages as $package) {
          $overrides = $manager->detectOverrides($package);
          $missing = $manager->detectMissing($package);
          if ((!empty($missing) || !empty($overrides)) && ($package->getStatus() == FeaturesManagerInterface::STATUS_INSTALLED)) {
            $overridden[] = $package->getMachineName();
          }
        }
        if (!empty($overridden)) {
          social_features_import($overridden);
        }

      }
      break;
  }

  $context['results'][] = $process;
  $context['message'] = $description;
}

/**
 * Imports module config into the active store.
 *
 * @see drush_features_import()
 */
function social_features_import($args) {

  /** @var \Drupal\features\FeaturesManagerInterface $manager */
  $manager = \Drupal::service('features.manager');
  /** @var \Drupal\config_update\ConfigRevertInterface $config_revert */
  $config_revert = \Drupal::service('features.config_update');

  // Parse list of arguments.
  $modules = [];
  foreach ($args as $arg) {
    $arg = explode(':', $arg);
    $module = array_shift($arg);
    $component = array_shift($arg);

    if (isset($module)) {
      if (empty($component)) {
        // If we received just a feature name, this means that we need all of
        // its components.
        $modules[$module] = TRUE;
      }
      elseif ($modules[$module] !== TRUE) {
        if (!isset($modules[$module])) {
          $modules[$module] = [];
        }
        $modules[$module][] = $component;
      }
    }
  }

  // Process modules.
  foreach ($modules as $module => $components_needed) {

    /** @var \Drupal\features\Package $feature */
    $feature = $manager->loadPackage($module, TRUE);
    if (empty($feature)) {
      return;
    }

    if ($feature->getStatus() != FeaturesManagerInterface::STATUS_INSTALLED) {
      return;
    }

    // Only revert components that are detected to be Overridden.
    $components = $manager->detectOverrides($feature);
    $missing = $manager->reorderMissing($manager->detectMissing($feature));
    // Be sure to import missing components first.
    $components = array_merge($missing, $components);

    if (!empty($components_needed) && is_array($components_needed)) {
      $components = array_intersect($components, $components_needed);
    }

    if (!empty($components)) {
      $config = $manager->getConfigCollection();
      foreach ($components as $component) {
        if (!isset($config[$component])) {
          // Import missing component.
          /** @var array $item */
          $item = $manager->getConfigType($component);
          $type = ConfigurationItem::fromConfigStringToConfigType($item['type']);
          $config_revert->import($type, $item['name_short']);
        }
        else {
          // Revert existing component.
          /** @var \Drupal\features\ConfigurationItem $item */
          $item = $config[$component];
          $type = ConfigurationItem::fromConfigStringToConfigType($item->getType());
          $config_revert->revert($type, $item->getShortName());
        }
      }
    }
  }
}
