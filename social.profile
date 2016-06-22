<?php
/**
 * @file
 * Enables modules and site configuration for a social site installation.
 */

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter() for install_configure_form().
 *
 * Allows the profile to alter the site configuration form.
 */
function social_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  // Add a placeholder as example that one can choose an arbitrary site name.
  $form['site_information']['site_name']['#attributes']['placeholder'] = t('Drupal Social');

  // Add 'Social' fieldset and options.
  $form['social'] = [
    '#type' => 'details',
    '#title' => t('Social Features'),
    '#weight' => -5,
    '#open' => TRUE,
  ];

  // Checkboxes to enable Social Features.
  $form['social']['features'] = [
    '#type' => 'checkboxes',
    '#title' => t('Enable Features'),
    '#description' => 'You can choose to disable some of Social\'s features above. However, it is not recommended.',
    '#options' => [
      'social_devel' => 'Social Devel',
      'social_demo' => 'Social Demo',
    ],
    '#default_value' => ['social_devel', 'social_demo'],
  ];

  // Submit handler to enable features.
  $form['#submit'][] = 'social_features_submit';
}

/**
 * Enable requested Social features.
 */
function social_features_submit($form_id, &$form_state) {
  $required = array(
    'core' => array(
      'social_core',
    ),
    'user' => array(
      'social_user',
    ),
    'content' => array(
      'social_group',
      'social_event',
      'social_topic',
      'social_profile',
    ),
    'editor' => array(
      'social_editor',
    ),
    'comment' => array(
      'social_comment',
    ),
    'search' => array(
      'social_search',
    ),
    'post' => array(
      'social_post',
    ),
    'activity' => array(
      'social_activity',
    ),
  );
  foreach ($required as $category => $modules) {
    \Drupal::service('module_installer')->install($modules, TRUE);
    drupal_set_message(t('Installed @category', array('@category' => $category)));
  }

  // Now install the selected features.
  $features = array_filter($form_state->getValue('features'));
  if (isset($features)) {
    \Drupal::service('module_installer')->install($features, TRUE);
  }
}
