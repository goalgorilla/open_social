<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around the usage of user.
 */
class TaggingContext extends RawMinkContext {

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
   * Fill placement data to show tag for entities.
   *
   * @Given I enable content tag :term_name for all entities
   */
  public function enableContentTagForAllEntities(string $term_name): void {
    $response = $this->testBridge->command('enable-content-tagging-for-all-entities');

    assert(!isset($response['error']), $response['error']);
  }

}
