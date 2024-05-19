<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around the interaction with search.
 */
class SearchContext extends RawMinkContext {

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
   * When the database is loaded, delete all data from SOLR.
   */
  public function onDatabaseLoaded() {
    $response = $this->testBridge->command('solr-clear');
    assert($response['status'] === 'ok');
  }

  /**
   * @Given Search indexes are up to date
   */
  public function updateSearchIndexes() {
    $this->testBridge->drush(["search-api:index"]);

    // With the move to SOLR indexing has become asynchronous, so we must wait
    // a small moment to ensure SOLR has actually settled.
    // See also search_api_solr's own code in the commits linked for
    // https://www.drupal.org/project/search_api_solr/issues/2940539.
    sleep(1);
  }

  /**
   * I search :index for :term
   *
   * @When /^(?:|I )search (all|users|groups|content) for "([^"]*)"/
   */
  public function iSearchIndexForTerm($index, $term) {
    $this->getSession()->visit($this->locatePath('/search/' . $index . '/' . urlencode($term)));
  }

  /**
   * Checks, that the search results contains specified text.
   *
   * Example: Then I should see "Who is the Batman?" in the search results.
   * Example: And should see "Who is the Batman?" in the search results.
   *
   * @Then I should see :text in the search results
   * @Then should see :text in the search results
   */
  public function assertSearchResultsContainsText($text) : void {
    $this->assertSession()->elementTextContains("css", "#block-socialblue-content", $text);
  }

  /**
   * Checks, that search results don't contain specified text.
   *
   * Example: Then I should not see "Who is the Batman?" in the search results.
   * Example: And should not see "Who is the Batman?" in the search results.
   *
   * @Then I should not see :text in the search results
   * @Then should not see :text in the search results
   */
  public function assertSearchResultsNotContainsText($text) {
    $this->assertSession()->elementTextNotContains("css", "#block-socialblue-content", $text);
  }

}
