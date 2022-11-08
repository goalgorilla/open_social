<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around theming.
 *
 * Also sets our default sky theme back to the legacy theme for which tests were
 * designed.
 */
class ThemeContext extends RawMinkContext {

  /**
   * By default all tests run in the old theme.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The before scenario scope.
   *
   * @BeforeScenario
   */
  public function before(BeforeScenarioScope $scope) {
    // Since we enable Sky theme by default we should make sure we run our
    // tests on the old theme. In another case, it will break all our tests.
    // @see https://www.drupal.org/project/socialblue/issues/3251299
    $this->withTheme("old");
  }

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
