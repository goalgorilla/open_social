<?php

/**
 * @file
 * Contains \Drupal\composer_manager\Composer\Command.
 */

namespace Drupal\composer_manager\Composer;

use Composer\Script\Event;
use Composer\Console\Application;
use Drupal\Component\FileCache\FileCacheFactory;

/**
 * Callbacks for Composer commands defined by Composer Manager.
 *
 * Commands:
 * - 'composer drupal-rebuild'
 * - 'composer drupal-update'
 */
class Command {

  /**
   * Rebuilds the root package.
   */
  public static function rebuild(Event $event) {
    $package_manager = self::getPackageManager();
    $package_manager->rebuildRootPackage();

    echo 'The composer.json has been successfuly rebuilt.' . PHP_EOL;
  }

  /**
   * Rebuilds the root package, then calls 'composer update'.
   */
  public static function update(Event $event) {
    $package_manager = self::getPackageManager();
    $package_manager->rebuildRootPackage();

    // Change the requested command to 'update', and rerun composer.
    $command_index = array_search('drupal-update', $_SERVER['argv']);
    $_SERVER['argv'][$command_index] = 'update';
    $application = new Application();
    $application->run();
  }

  /**
   * Returns a \Drupal\composer_manager\PackageManager instance.
   */
  public static function getPackageManager() {
    $root = getcwd();
    require $root . '/autoload.php';
    // The module classes aren't in the autoloader at this point.
    require __DIR__ . '/../ExtensionDiscovery.php';
    require __DIR__ . '/../JsonFile.php';
    require __DIR__ . '/../PackageManagerInterface.php';
    require __DIR__ . '/../PackageManager.php';
    // YAML discovery in core uses FileCache which is not available.
    FileCacheFactory::setConfiguration(['default' => ['class' => '\Drupal\Component\FileCache\NullFileCache']]);

    return new \Drupal\composer_manager\PackageManager($root);
  }

}
