<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\data_policy\Entity\DataPolicy;

/**
 * Defines test steps around the usage of the GPDR module.
 */
class GDPRContext extends RawMinkContext {

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
   * Set the GDPR consent text confguration.
   *
   * @Given /^(?:|I )set the GDPR Consent Text to "(?P<text>[^"]+)"$/
   */
  public function setGdprContsentText(string $text) {
    $response = $this->testBridge->command(
      'set-gdpr-consent-text',
      text: $text,
    );
    assert(!isset($response['error']), $response['error']);
  }

}
