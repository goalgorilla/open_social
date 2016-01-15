<?php

/**
 * @file
 * Contains
 * \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationArchive.
 */

namespace Drupal\features\Plugin\FeaturesGeneration;

use Drupal\Component\Serialization\Yaml;
use Drupal\features\FeaturesGenerationMethodBase;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Form\FormStateInterface;
use Drupal\features\FeaturesBundleInterface;

/**
 * Class for generating a compressed archive of packages.
 *
 * @Plugin(
 *   id = \Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationArchive::METHOD_ID,
 *   weight = -2,
 *   name = @Translation("Download Archive"),
 *   description = @Translation("Generate packages and optional profile as a compressed archive for download."),
 * )
 */
class FeaturesGenerationArchive extends FeaturesGenerationMethodBase {

  /**
   * The package generation method id.
   */
  const METHOD_ID = 'archive';

  /**
   * The filename being written.
   *
   * @var string
   */
  protected $archiveName;

  /**
   * Reads and merges in existing files for a given package or profile.
   */
  protected function preparePackage(array &$package, array $existing_packages, FeaturesBundleInterface $bundle = NULL) {
    if (isset($existing_packages[$package['machine_name']])) {
      $existing_directory = $existing_packages[$package['machine_name']];
      // Scan for all files.
      $files = file_scan_directory($existing_directory, '/.*/');
      foreach ($files as $file) {
        // Skip files in the any existing configuration directory, as these
        // will be replaced.
        foreach (array_keys($this->featuresManager->getExtensionStorages()->getExtensionStorages()) as $directory) {
          if (strpos($file->uri, $directory) !== FALSE) {
            continue 2;
          }
        }
        // Merge in the info file.
        if ($file->name == $package['machine_name'] . '.info') {
          $package['files']['info']['string'] = $this->mergeInfoFile($package['files']['info']['string'], $file->uri);
        }
        // Read in remaining files.
        else {
          // Determine if the file is within a subdirectory of the
          // extension's directory.
          $file_directory = dirname($file->uri);
          if ($file_directory !== $existing_directory) {
            $subdirectory = substr($file_directory, strlen($existing_directory) + 1);
          }
          else {
            $subdirectory = NULL;
          }
          $package['files'][] = [
            'filename' => $file->filename,
            'subdirectory' => $subdirectory,
            'string' => file_get_contents($file->uri)
          ];
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

    // Determine the best name for the tar archive.
    // Single package export, so name by package name.
    if (count($packages) == 1) {
      $filename = current($packages)['machine_name'];
    }
    // Profile export, so name by profile.
    elseif (isset($bundle) && $bundle->isProfile()) {
      $filename = $bundle->getProfileName();
    }
    // Non-default bundle, so name by bundle.
    elseif (isset($bundle) && !$bundle->isDefault()) {
      $filename = $bundle->getMachineName();
    }
    // Set a fallback name.
    else {
      $filename = 'generated_features';
    }

    $return = [];

    $this->archiveName = $filename . '.tar.gz';
    $archive_name = file_directory_temp() . '/' . $this->archiveName;
    if (file_exists($archive_name)) {
      file_unmanaged_delete($archive_name);
    }

    $archiver = new ArchiveTar($archive_name);

    // Add package files.
    foreach ($packages as $package) {
      if (count($packages) == 1) {
        // Single module export, so don't generate entire modules dir structure.
        $package['directory'] = $package['machine_name'];
      }
      $this->generatePackage($return, $package, $archiver);
    }

    return $return;
  }

  /**
   * Writes a package or profile's files to an archive.
   *
   * @param array &$return
   *   The return value, passed by reference.
   * @param array $package
   *   The package or profile.
   * @param ArchiveTar $archiver
   *   The archiver.
   */
  protected function generatePackage(array &$return, array $package, ArchiveTar $archiver) {
    $success = TRUE;
    foreach ($package['files'] as $file) {
      try {
        $this->generateFile($package['directory'], $file, $archiver);
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
   * Registers a successful package or profile archive operation.
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
      // Archive writing doesn't merit a message, and if done through the UI
      // would appear on the subsequent page load.
      'display' => FALSE,
      'message' => '@type @package written to archive.',
      'variables' => [
        '@type' => $type,
        '@package' => $package['name']
      ],
    ];
  }

  /**
   * Registers a failed package or profile archive operation.
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
      // Archive writing doesn't merit a message, and if done through the UI
      // would appear on the subsequent page load.
      'display' => FALSE,
      'message' => '@type @package not written to archive. Error: @error.',
      'variables' => [
        '@type' => $type,
        '@package' => $package['name'],
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
   * @param ArchiveTar $archiver
   *   The archiver.
   *
   * @throws Exception
   */
  protected function generateFile($directory, array $file, ArchiveTar $archiver) {
    $filename = $directory;
    if (!empty($file['subdirectory'])) {
      $filename .= '/' . $file['subdirectory'];
    }
    $filename .= '/' . $file['filename'];
    // Set the mode to 0644 rather than the default of 0600.
    if ($archiver->addString($filename, $file['string'], FALSE, ['mode' => 0644]) === FALSE) {
      throw new \Exception($this->t('Failed to archive file @filename.', ['@filename' => $file['filename']]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exportFormSubmit(array &$form, FormStateInterface $form_state) {
    // Redirect to the archive file download.
    $session = \Drupal::request()->getSession();
    if (isset($session)) {
      $session->set('features_download', $this->archiveName);
    }
    $form_state->setRedirect('features.export_download');
  }

}
