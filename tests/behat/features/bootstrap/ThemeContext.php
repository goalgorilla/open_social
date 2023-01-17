<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around theming.
 */
class ThemeContext extends RawMinkContext {

  /**
   * Allows a test to use a specific theme for a scenario.
   *
   * @Given /^the theme is set to (old|sky)$/
   */
  public function withTheme($theme) {
    \Drupal::configFactory()
      ->getEditable('socialblue.settings')
      ->set('style', $theme === "old" ? "" : $theme)
      ->save();
  }

}
