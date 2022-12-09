<?php

declare(strict_types=1);

namespace Drupal\social\Behat\Chrome;

use Behat\Testwork\ServiceContainer\ExtensionManager;
use DMore\ChromeExtension\Behat\ServiceContainer\ChromeExtension as ChromeExtensionBase;

/**
 * Overwrites DMore/../ChromeExtension to load our adapted driver.
 */
class ChromeExtension extends ChromeExtensionBase {

  /**
   * {@inheritdoc}
   */
  public function initialize(ExtensionManager $extensionManager) {
    // Must be kept in sync with parent::iniitalize but use our own factory.
    if (null !== $minkExtension = $extensionManager->getExtension('mink')) {
      /** @var $minkExtension \Behat\MinkExtension\ServiceContainer\MinkExtension */
      $minkExtension->registerDriverFactory(new ChromeFactory());
    }
  }

}
