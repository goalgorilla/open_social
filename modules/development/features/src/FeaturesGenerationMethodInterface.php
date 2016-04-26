<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesGenerationMethodInterface.
 */

namespace Drupal\features;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for package assignment classes.
 */
interface FeaturesGenerationMethodInterface {

  /**
   * Injects the features manager.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *   The features manager to be used to retrieve the configuration
   *   list and the assigned packages.
   */
  public function setFeaturesManager(FeaturesManagerInterface $features_manager);

  /**
   * Injects the features assigner.
   *
   * @param \Drupal\features\FeaturesAssignerInterface $assigner
   *   The features assigner to be used to retrieve the bundle configuration.
   */
  public function setAssigner(FeaturesAssignerInterface $assigner);

  /**
   * Prepares packages for generation.
   *
   * @param array $packages
   *   Array of package data.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   The optional bundle used for the generation.  Used to generate profiles.
   *
   * @return array
   *   An array of packages data.
   */
  public function prepare(array &$packages = array(), FeaturesBundleInterface $bundle = NULL);

  /**
   * Performs package generation.
   *
   * @param array $packages
   *   Array of package data.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   The optional bundle used for the generation.  Used to generate profiles.
   *
   * @return array
   *   Array of results for profile and/or packages, each result including the
   *   following keys:
   *   - 'success': boolean TRUE or FALSE for successful writing.
   *   - 'display': boolean TRUE if the message should be displayed to the
   *     user, otherwise FALSE.
   *   - 'message': a message about the result of the operation.
   *   - 'variables': an array of substitutions to be used in the message.
   */
  public function generate(array $packages = array(), FeaturesBundleInterface $bundle = NULL);

  /**
   * Responds to the submission of
   * \Drupal\features_ui\Form\FeaturesExportForm.
   */
  public function exportFormSubmit(array &$form, FormStateInterface $form_state);

}
