<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Symfony\Component\Yaml\Yaml;

/**
 * Defines test steps around the interaction with search.
 */
class SearchContext extends RawMinkContext {

  /**
   * @Given Search indexes are up to date
   */
  public function updateSearchIndexes() {
    /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $index_storage */
    $index_storage = \Drupal::service("entity_type.manager")->getStorage('search_api_index');

    $indexes = $index_storage->loadMultiple();
    if (!$indexes) {
      return;
    }

    // Loop over all interfaces and let the Search API index any non-indexed
    // items.
    foreach ($indexes as $index) {
      /** @var \Drupal\search_api\IndexInterface $index */
      $index->indexItems();
    }
  }

  /**
   * I search :index for :term
   *
   * @When /^(?:|I )search (all|users|groups|content) for "([^"]*)"/
   */
  public function iSearchIndexForTerm($index, $term) {
    $this->getSession()->visit($this->locatePath('/search/' . $index . '/' . urlencode($term)));
  }

}
