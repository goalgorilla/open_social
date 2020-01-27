<?php

namespace Drupal\social\Installer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the site configuration form.
 */
class ModuleConfigureForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_module_configure_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = $this->t('Install optional modules');

    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('All the required modules and configuration will be automatically installed and imported. You can optionally select additional features or generated demo content.'),
    ];

    $form['install_modules'] = [
      '#type' => 'container',
    ];

    // Checkboxes to enable Optional modules.
    $form['install_modules']['optional_modules'] = [
      '#type' => 'checkboxes',
      '#title' => t('Enable additional features'),
      '#options' => $this->getOptionalModules(),
      '#default_value' => [
        'dynamic_page_cache',
        'inline_form_errors',
        'page_cache',
        'social_file_private',
        'social_search_autocomplete',
        'social_lets_connect_contact',
        'social_lets_connect_usage',
        'social_group_flexible_group',
      ],
    ];

    $form['install_demo'] = [
      '#type' => 'container',
    ];

    $form['install_demo']['demo_content'] = [
      '#type' => 'checkbox',
      '#title' => t('Generate demo content and users'),
      '#description' => t('Will generate files, users, groups, events, topics, comments and posts.'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and continue'),
      '#button_type' => 'primary',
      '#submit' => ['::submitForm'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $optional_modules = array_filter($form_state->getValue('optional_modules'));

    // Set the modules to be installed by Drupal in the install_profile_modules
    // step.
    $install_modules = array_merge(
      \Drupal::state()->get('install_profile_modules'),
      $optional_modules
    );
    \Drupal::state()->set('install_profile_modules', $install_modules);

    // Store whether we need to set up demo content.
    \Drupal::state()->set('social_install_demo_content', $form_state->getValue('demo_content'));
  }

  /**
   * Contains the optional modules for Open Social.
   *
   * TODO: Refactor this into an OptionalModuleManagerService as used by
   *   Thunder.
   *
   * @return array
   *   The optional modules that users can install.
   */
  private function getOptionalModules() {
    return [
      'social_book' => t('Book functionality'),
      'social_sharing' => t('Share content on social media'),
      'social_event_type' => t('Categorize events in event types'),
      'social_sso' => t('Registration with social networks'),
      'social_search_autocomplete' => t('Suggested results in the search overlay'),
      'social_file_private' => t('Use the private file system for uploaded files (highly recommended)'),
      'inline_form_errors' => t('Inline Form Errors'),
      'page_cache' => t('Cache page for anonymous users (highly recommended)'),
      'dynamic_page_cache' => t('Cache pages for any user (highly recommended)'),
      'social_lets_connect_contact' => t('Adds Open Social Links to the main menu.'),
      'social_lets_connect_usage' => t('Shares usage data to the Open Social team.'),
      'social_group_flexible_group' => t('Flexible group functionality'),
      'social_group_secret' => t('Secret group functionality'),
    ];
  }

}
