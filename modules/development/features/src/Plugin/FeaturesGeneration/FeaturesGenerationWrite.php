<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationWrite.
 */

namespace Drupal\features\Plugin\FeaturesGeneration;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\features\FeaturesGenerationMethodBase;
use Drupal\features\FeaturesBundleInterface;
use Drupal\features\Package;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for writing packages to the local file system.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationWrite::METHOD_ID,
 *   weight = 2,
 *   name = @Translation("Write"),
 *   description = @Translation("Write packages and optional profile to the file system."),
 * )
 */
class FeaturesGenerationWrite extends FeaturesGenerationMethodBase implements ContainerFactoryPluginInterface {

  /**
   * The package generation method id.
   */
  const METHOD_ID = 'write';

  /**
   * The app root.
   *
   * @var string
   */
  protected $root;

  /**
   * Creates a new FeaturesGenerationWrite instance.
   *
   * @param string $root
   *   The app root.
   */
  public function __construct($root) {
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('app.root')
    );
  }

  /**
   * Reads and merges in existing files for a given package or profile.
   *
   * @param \Drupal\features\Package &$package
   *   The package.
   * @param array $existing_packages
   *   An array of existing packages.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   The bundle the package belongs to.
   */
  protected function preparePackage(Package $package, array $existing_packages, FeaturesBundleInterface $bundle = NULL) {
    // If this package is already present, prepare files.
    if (isset($existing_packages[$package->getMachineName()])) {
      $existing_directory = $existing_packages[$package->getMachineName()];

      $package->setDirectory($existing_directory);

      // Merge in the info file.
      $info_file_uri = $this->root . '/' . $existing_directory . '/' . $package->getMachineName() . '.info.yml';
      if (file_exists($info_file_uri)) {
        $files = $package->getFiles();
        $files['info']['string'] = $this->mergeInfoFile($package->getFiles()['info']['string'], $info_file_uri);
        $package->setFiles($files);
      }

      // Remove the config directories, as they will be replaced.
      foreach (array_keys($this->featuresManager->getExtensionStorages()->getExtensionStorages()) as $directory) {
        $config_directory = $this->root . '/' . $existing_directory . '/' . $directory;
        if (is_dir($config_directory)) {
          file_unmanaged_delete_recursive($config_directory);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generate(array $packages = array(), FeaturesBundleInterface $bundle = NULL) {
    // If no packages were specified, get all packages.
    if (empty($packages)) {
      $packages = $this->featuresManager->getPackages();
    }

    $return = [];

    // Add package files.
    // We need to update the system.module.files state because it's cached.
    // Cannot just call system_rebuild_module_data() because $listing->scan() has
    // it's own internal static cache that we cannot clear at this point.
    $files = \Drupal::state()->get('system.module.files');
    foreach ($packages as $package) {
      $this->generatePackage($return, $package);
      if (!isset($files[$package->getMachineName()]) && isset($package->getFiles()['info'])) {
        $files[$package->getMachineName()] = $package->getDirectory() . '/' . $package->getFiles()['info']['filename'];
      }
    }

    // Rebuild system module cache
    \Drupal::state()->set('system.module.files', $files);

    return $return;
  }

  /**
   * Writes a package or profile's files to the file system.
   *
   * @param array &$return
   *   The return value, passed by reference.
   * @param \Drupal\features\Package $package
   *   The package or profile.
   */
  protected function generatePackage(array &$return, Package $package) {
    if (!$package->getFiles()) {
      $this->failure($return, $package, NULL, t('No configuration was selected to be exported.'));
      return;
    }
    $success = TRUE;
    foreach ($package->getFiles() as $file) {
      try {
        $this->generateFile($package->getDirectory(), $file);
      }
      catch (\Exception $exception) {
        $this->failure($return, $package, $exception);
        $success = FALSE;
        break;
      }
    }
    if ($success) {
      $this->success($return, $package);
    }
  }

  /**
   * Registers a successful package or profile write operation.
   *
   * @param array &$return
   *   The return value, passed by reference.
   * @param \Drupal\features\Package $package
   *   The package or profile.
   */
  protected function success(array &$return, Package $package) {
    $type = $package->getType() == 'module' ? $this->t('Package') : $this->t('Profile');
    $return[] = [
      'success' => TRUE,
      'display' => TRUE,
      'message' => '@type @package written to @directory.',
      'variables' => [
        '@type' => $type,
        '@package' => $package->getName(),
        '@directory' => $package->getDirectory(),
      ],
    ];
  }

  /**
   * Registers a failed package or profile write operation.
   *
   * @param array &$return
   *   The return value, passed by reference.
   * @param \Drupal\features\Package $package
   *   The package or profile.
   * @param \Exception $exception
   *   The exception object.
   * @param string $message
   *   Error message when there isn't an Exception object.
   */
  protected function failure(array &$return, Package $package, \Exception $exception = NULL, $message = '') {
    $type = $package->getType() == 'module' ? $this->t('Package') : $this->t('Profile');
    $return[] = [
      'success' => FALSE,
      'display' => TRUE,
      'message' => '@type @package not written to @directory. Error: @error.',
      'variables' => [
        '@type' => $type,
        '@package' => $package->getName(),
        '@directory' => $package->getDirectory(),
        '@error' => isset($exception) ? $exception->getMessage() : $message,
      ],
    ];
  }

  /**
   * Writes a file to the file system, creating its directory as needed.
   *
   * @param string $directory
   *   The extension's directory.
   * @param array $file
   *   Array with the following keys:
   *   - 'filename': the name of the file.
   *   - 'subdirectory': any subdirectory of the file within the extension
   *      directory.
   *   - 'string': the contents of the file.
   *
   * @throws Exception
   */
  protected function generateFile($directory, array $file) {
    if (!empty($file['subdirectory'])) {
      $directory .= '/' . $file['subdirectory'];
    }
    $directory = $this->root . '/' . $directory;
    if (!is_dir($directory)) {
      if (drupal_mkdir($directory, NULL, TRUE) === FALSE) {
        throw new \Exception($this->t('Failed to create directory @directory.', ['@directory' => $directory]));
      }
    }
    if (file_put_contents($directory . '/' . $file['filename'], $file['string']) === FALSE) {
      throw new \Exception($this->t('Failed to write file @filename.', ['@filename' => $file['filename']]));
    }
  }

}
