<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesGeneratorInterface.
 */

namespace Drupal\features;

use Drupal\Core\Form\FormStateInterface;

/**
 * Common interface for features generation services.
 *
 * The configuration packaging API is based on two major concepts:
 * - Packages: modules into which configuration is packaged.
 * - Package generation methods: responsible for `determining
 *   which package to assign a given piece of configuration to.
 * Generation methods are customizable.
 *
 * Features defines two package generation methods, which are simple plugin
 * classes that implement a particular logic to assign pieces of configuration
 * to a given package (module).
 *
 * Modules can define additional package generation methods by simply providing
 * the related plugins, and alter existing methods through
 * hook_features_generation_method_info_alter(). Here is an example
 * snippet:
 * @code
 * function mymodule_features_generation_method_info_alter(&$generation_info) {
 *   // Replace the original plugin with our own implementation.
 *   $method_id = \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationArchive::METHOD_ID;
 *   $generation_info[$method_id]['class'] = 'Drupal\my_module\Plugin\FeaturesGeneration\MyFeaturesGenerationArchive';
 * }
 *
 * class MyFeaturesGenerationArchive extends FeaturesGenerationArchive {
 *   public function generate(array $packages = array(), FeaturesBundleInterface $bundle = NULL) {
 *     // Insert customization here.
 *   }
 * }
 * ?>
 * @endcode
 *
 * For more information, see
 * @link http://drupal.org/node/2404473 Developing for Features 3.x @endlink
 */
interface FeaturesGeneratorInterface {

  /**
   * The package generation method id for the package generator itself.
   */
  const METHOD_ID = 'generator-default';

  /**
   * Resets the assigned packages and the method instances.
   */
  public function reset();

  /**
   * Apply a given package generation method.
   *
   * @param string $method_id
   *   The string identifier of the package generation method to use to package
   *   configuration.
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
  public function applyGenerationMethod($method_id, array $packages = array(), FeaturesBundleInterface $bundle = NULL);

  /**
   * Responds to the submission of
   * \Drupal\features_ui\Form\FeaturesExportForm.
   */
  public function applyExportFormSubmit($method_id, &$form, FormStateInterface $form_state);

  /**
   * Returns the enabled package generation methods.
   *
   * @return array
   *   An array of package generation method definitions keyed by method id.
   */
  public function getGenerationMethods();

  /**
   * Generates file representations of configuration packages.
   *
   * @param string $method_id
   *   The ID of the generation method to use.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   The bundle used for the generation.
   * @param array $package_names
   *   Array of names of packages to be generated. If none are specified, all
   *   available packages will be added.
   */
  public function generatePackages($method_id, FeaturesBundleInterface $bundle, array $package_names = array());

}
