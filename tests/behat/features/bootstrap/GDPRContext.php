<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\data_policy\Entity\DataPolicy;
use Drupal\data_policy\Entity\InformBlock;

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
   * Create inform blocks at the start of a test.
   *
   * Creates inform blocks provided in the form:
   * | label               | page           | summary              | body
   * | Inform block title  | /path/example  | Inform block summary | Inform block description
   * | ...                 | ...            | ...                  | ...
   *
   * @Given inform_blocks:
   */
  public function createInformBlock(TableNode $informBlocksTable) : void {
    foreach ($informBlocksTable->getHash() as $informBlock) {
      if (isset($informBlock['body'])) {
        $informBlock['body'] = [
          'value' => $informBlock['body'],
          'format' => 'basic_html',
        ];
      }

      if (isset($informBlock['summary'])) {
        $informBlock['summary'] = [
          'value' => $informBlock['summary'],
          'format' => 'basic_html',
        ];
      }

      $inform_block_content = InformBlock::create($informBlock);
      $inform_block_content->save();
    }
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
