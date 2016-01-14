<?php

/**
 * @file
 * Contains \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationWrite.
 */

namespace Drupal\features\Plugin\FeaturesGeneration;

use Drupal\features\FeaturesGenerationMethodBase;
use Drupal\Core\Config\InstallStorage;
use Drupal\features\FeaturesBundleInterface;

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
class FeaturesGenerationWrite extends FeaturesGenerationMethodBase {

  /**
   * The package generation method id.
   */
  const METHOD_ID = 'write';

  /**
   * Reads and merges in existing files for a given package or profile.
   *
   * @param array &$package
   *   The package.
   * @param array $existing_packages
   *   An array of existing packages.
   * @param \Drupal\features\FeaturesBundleInterface $bundle
   *   The bundle the package belongs to.
   */
  protected function preparePackage(array &$package, array $existing_packages, FeaturesBundleInterface $bundle = NULL) {
    // If this package is already present, prepare files.
    if (isset($existing_packages[$package['machine_name']])) {
      $existing_directory = $existing_packages[$package['machine_name']];

      $package['directory'] = $existing_directory;

      // Merge in the info file.
      $info_file_uri = $existing_directory . '/' . $package['machine_name'] . '.info.yml';
      if (file_exists($info_file_uri)) {
        $package['files']['info']['string'] = $this->mergeInfoFile($package['files']['info']['string'], $info_file_uri);
      }

      // Remove the config directories, as they will be replaced.
      foreach (array_keys($this->featuresManager->getExtensionStorages()->getExtensionStorages()) as $directory) {
        $config_directory = $existing_directory . '/' . $directory;
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
      if (!isset($files[$package['machine_name']]) && isset($package['files']['info'])) {
        $files[$package['machine_name']] = $package['directory'] . '/' . $package['files']['info']['filename'];
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
   * @param array $package
   *   The package or profile.
   */
  protected function generatePackage(array &$return, array $package) {
    if (empty($package['files'])) {
      $this->failure($return, $package, NULL, t('No configuration was selected to be exported.'));
      return;
    }
    $success = TRUE;
    foreach ($package['files'] as $file) {
      try {
        $this->generateFile($package['directory'], $file);
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
   * @param array $package
   *   The package or profile.
   */
  protected function success(array &$return, array $package) {
    $type = $package['type'] == 'module' ? $this->t('Package') : $this->t('Profile');
    $return[] = [
      'success' => TRUE,
      'display' => TRUE,
      'message' => '@type @package written to @directory.',
      'variables' => [
        '@type' => $type,
        '@package' => $package['name'],
        '@directory' => $package['directory']
      ],
    ];
  }

  /**
   * Registers a failed package or profile write operation.
   *
   * @param array &$return
   *   The return value, passed by reference.
   * @param array $package
   *   The package or profile.
   * @param \Exception $exception
   *   The exception object.
   * @param string $message
   *   Error message when there isn't an Exception object.
   */
  protected function failure(array &$return, array $package, \Exception $exception, $message = '') {
    $type = $package['type'] == 'module' ? $this->t('Package') : $this->t('Profile');
    $return[] = [
      'success' => FALSE,
      'display' => TRUE,
      'message' => '@type @package not written to @directory. Error: @error.',
      'variables' => [
        '@type' => $type,
        '@package' => $package['name'],
        '@directory' => $package['directory'],
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
