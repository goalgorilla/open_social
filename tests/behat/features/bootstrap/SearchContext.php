<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Driver\DrushDriver;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;

/**
 * Defines test steps around the interaction with search.
 */
class SearchContext extends RawMinkContext {

  /**
   * The driver that allows us to execute drush commands.
   */
  private DrushDriver $drushDriver;

  /**
   * Ensures the drush driver is available in other hooks and steps.
   *
   * Should be the first BeforeScenario hook in this class.
   *
   * @BeforeScenario
   */
  public function getDrushDriver(BeforeScenarioScope $scope) : void {
    $this->environment= $scope->getEnvironment();
    $drupal_context = $this->environment->getContext(SocialDrupalContext::class);
    if (!$drupal_context instanceof SocialDrupalContext) {
      throw new \RuntimeException("Expected " . SocialDrupalContext::class . " to be configured for Behat.");
    }
    // Call getDriver without arguments to boostrap the default driver.
    $drupal_context->getDriver();
    $driver = $drupal_context->getDriver("drush");
    if (!$driver instanceof DrushDriver) {
      throw new \RuntimeException("Could not load the Drush driver. Make sure the DrupalExtension is configured to enable it.");
    }
    $this->drushDriver = $driver;
  }

  /**
   * When the database is loaded, delete all data from SOLR.
   *
   * The search_api and search_api_solr modules create specific delete queries
   * for SOLR data based on the indexes. However, the site hash that they use
   * may change in between databases which can cause old test data not to be
   * cleaned up. This can cause issues when the data matches and the modules
   * try to load it.
   *
   * See https://www.drupal.org/project/search_api_solr/issues/3218868.
   */
  public function onDatabaseLoaded() {
    /** @var \Drupal\search_api\IndexInterface[] $indexess */
    $indexes = \Drupal::service("entity_type.manager")
      ->getStorage('search_api_index')
      ->loadMultiple();

    foreach ($indexes as $index_id => $index) {
      /** @var \Drupal\search_api\ServerInterface $server */
      $server = $index->getServerInstance();
      $backend = $server->getBackend();
      if (!$backend instanceof SearchApiSolrBackend) {
        continue;
      }
      $connector = $backend->getSolrConnector();
      $update_query = $connector->getUpdateQuery();
      $update_query->addDeleteQuery("*:*");
      $connector->update($update_query, $backend->getCollectionEndpoint($index));
    }
  }

  /**
   * @Given Search indexes are up to date
   */
  public function updateSearchIndexes() {
    // We use Drush here because it ensures that any settings that are in our
    // test memory that might be outdated, don't affect what we're indexing.
    $this->drushDriver->drush("search-api:index");

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
