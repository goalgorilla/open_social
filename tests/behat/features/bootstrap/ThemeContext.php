<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around theming.
 */
class ThemeContext extends RawMinkContext {

  /**
   * The test bridge that allows running code in the Drupal installation.
   */
  private TestBridgeContext $testBridge;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->testBridge = $environment->getContext(TestBridgeContext::class);
  }

  /**
   * Allows a test to use a specific theme for a scenario.
   *
   * @Given /^the theme is set to (old|sky)$/
   */
  public function withTheme($theme) {
    $response = $this->testBridge->command(
      'theme-set-style',
      style: $theme === "old" ? "" : $theme
    );
    assert($response['status'] === 'ok');
  }

}
