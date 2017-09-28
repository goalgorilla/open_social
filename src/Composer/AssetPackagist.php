<?php
// @codingStandardsIgnoreFile

namespace Social\Composer;

use Composer\Json\JsonFile;
use Composer\Script\Event;

/**
 * Adds Asset Packagist support to a composer.json.
 *
 * Based on the approach of Acqua Lightning.
 */
final class AssetPackagist {

  /**
   * Reads the root package's composer.json.
   *
   * This will be the composer.json closest to the current working directory
   * that contains a dependency on Open Social.
   *
   * @return JsonFile
   *   File wrapper around the root package's composer.json.
   */
  protected static function getRootPackage() {
    $file = new JsonFile('composer.json');

    // Split the current working directory into an array, accounting for leading
    // and trailing directory separators.
    $dir = explode(DIRECTORY_SEPARATOR, trim(getcwd(), DIRECTORY_SEPARATOR));

    do {
      if ($file->exists()) {
        $info = $file->read();

        if (isset($info['require']['goalgorilla/open_social'])) {
          return $file;
        }
      }
      chdir('..');
      array_pop($dir);
    }
    while ($dir);

    throw new \RuntimeException('Could not locate the root package.');
  }

  /**
   * Executes the script.
   *
   * @param \Composer\Script\Event $event
   *   The script event.
   */
  public static function execute(Event $event) {
    $io = $event->getIO();

    // Search upwards for a composer.json which depends on Open Social.
    $io->write('Searching for root package...');

    $file = static::getRootPackage();

    $package = $file->read();

    // Add the Asset Packagist repository if it does not already exist.
    if (isset($package['repositories'])) {
      $repository_key = NULL;

      foreach ($package['repositories'] as $key => $repository) {
        if ($repository['type'] == 'composer' && strpos($repository['url'], 'https://asset-packagist.org') === 0) {
          $repository_key = $key;
          break;
        }
      }

      if (is_null($repository_key)) {
        $package['repositories'][] = [
          'type' => 'composer',
          'url' => 'https://asset-packagist.org',
        ];
      }
    }

    // oomphinc/composer-installers-extender is required by Open Social and
    // depends on composer/installers, so it does not need to be specifically
    // included.
    unset(
      $package['require']['oomphinc/composer-installers-extender']
    );

    // Check if we need to add the bower and npm assets to the installer types.
    if (!isset($package['extra']['installer-types']) || !in_array('bower-asset', $package['extra']['installer-types'])) {
      $package['extra']['installer-types'][] = 'bower-asset';
    }
    if (!in_array('npm-asset', $package['extra']['installer-types'])) {
      $package['extra']['installer-types'][] = 'npm-asset';
    }

    // Get root folder based on the location of Drupal core. If we do it in this
    // way we can also support projects that have
    //    $package['extra']['installer-paths']['']
    $root_path = 'html/';
    foreach ($package['extra']['installer-paths'] as $path => $install_type) {
      if (in_array('drupal/core', $install_type, TRUE)) {
        // If Drupal core is installed in the root path we also want the
        // libraries there.
        if ($path == 'core') {
          $root_path = '';
        }
        else {
          $parts = explode('/core', $path);
          if (!empty($parts)) {
            $root_path = $parts[0] . '/';
          }
        }

        break;
      }
    }

    // Add the different library types to the installer paths.
    $package['extra']['installer-paths'][$root_path . 'libraries/{$name}'][] = 'type:drupal-library';
    $package['extra']['installer-paths'][$root_path . 'libraries/{$name}'][] = 'type:bower-asset';
    $package['extra']['installer-paths'][$root_path . 'libraries/{$name}'][] = 'type:npm-asset';

    $file->write($package);
    $io->write('Successfully updated your root composer.json file.');
  }

}