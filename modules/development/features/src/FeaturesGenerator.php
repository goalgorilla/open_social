<?php

/**
 * @file
 * Contains \Drupal\features\FeaturesGenerator.
 */

namespace Drupal\features;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class responsible for performing package generation.
 */
class FeaturesGenerator implements FeaturesGeneratorInterface {
  use StringTranslationTrait;

  /**
   * The package generation method plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $generatorManager;

  /**
   * The features manager.
   *
   * @var \Drupal\features\FeaturesManagerInterface
   */
  protected $featuresManager;

  /**
   * The features assigner.
   *
   * @var \Drupal\features\FeaturesAssignerInterface
   */
  protected $assigner;

  /**
   * Local cache for package generation method instances.
   *
   * @var array
   */
  protected $methods;

  /**
   * Constructs a new FeaturesGenerator object.
   *
   * @param \Drupal\features\FeaturesManagerInterface $features_manager
   *    The features manager.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $generator_manager
   *   The package generation methods plugin manager.
   */
  public function __construct(FeaturesManagerInterface $features_manager, PluginManagerInterface $generator_manager, FeaturesAssignerInterface $assigner) {
    $this->featuresManager = $features_manager;
    $this->generatorManager = $generator_manager;
    $this->assigner = $assigner;
  }

  /**
   * Initializes the injected features manager with the generator.
   *
   * This should be called right after instantiating the generator to make it
   * available to the features manager without introducing a circular
   * dependency.
   */
  public function initFeaturesManager() {
    $this->featuresManager->setGenerator($this);
  }

  /**
   * {@inheritdoc}
   */
  public function reset() {
    $this->methods = array();
  }

  /**
   * {@inheritdoc}
   */
  public function applyGenerationMethod($method_id, array $packages = array(), FeaturesBundleInterface $bundle = NULL) {
    $method = $this->getGenerationMethodInstance($method_id);
    $method->prepare($packages, $bundle);
    return $method->generate($packages, $bundle);
  }

  /**
   * {@inheritdoc}
   */
  public function applyExportFormSubmit($method_id, &$form, FormStateInterface $form_state) {
    $method = $this->getGenerationMethodInstance($method_id);
    $method->exportFormSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getGenerationMethods() {
    return $this->generatorManager->getDefinitions();
  }

  /**
   * Returns an instance of the specified package generation method.
   *
   * @param string $method_id
   *   The string identifier of the package generation method to use to package
   *   configuration.
   *
   * @return \Drupal\features\FeaturesGenerationMethodInterface
   */
  protected function getGenerationMethodInstance($method_id) {
    if (!isset($this->methods[$method_id])) {
      $instance = $this->generatorManager->createInstance($method_id, array());
      $instance->setFeaturesManager($this->featuresManager);
      $instance->setAssigner($this->assigner);
      $this->methods[$method_id] = $instance;
    }
    return $this->methods[$method_id];
  }

  /**
   * {@inheritdoc}
   */
  public function generatePackages($method_id, FeaturesBundleInterface $bundle, array $package_names = array()) {
    $this->featuresManager->setPackageBundleNames($bundle, $package_names);
    return $this->generate($method_id, $bundle, $package_names);
  }

  /**
   * Generates a file representation of configuration packages and, optionally,
   * an install profile.
   *
   * @param string $method_id
   *   The ID of the generation method to use.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   The bundle used for the generation.
   * @param string[] $package_names
   *   Names of packages to be generated. If none are specified, all
   *   available packages will be added.
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
  protected function generate($method_id, FeaturesBundleInterface $bundle, array $package_names = array()) {
    $packages = $this->featuresManager->getPackages();

    // Filter out the packages that weren't requested.
    if (!empty($package_names)) {
      $packages = array_intersect_key($packages, array_fill_keys($package_names, NULL));
    }

    $this->featuresManager->assignInterPackageDependencies($bundle, $packages);

    // Prepare the files.
    $this->featuresManager->prepareFiles($packages);

    $return = $this->applyGenerationMethod($method_id, $packages, $bundle);

    foreach ($return as $message) {
      if ($message['display']) {
        $type = $message['success'] ? 'status' : 'error';
        drupal_set_message($this->t($message['message'], $message['variables']), $type);
      }
      $type = $message['success'] ? 'notice' : 'error';
      \Drupal::logger('features')->{$type}($message['message'], $message['variables']);
    }
    return $return;
  }

}
