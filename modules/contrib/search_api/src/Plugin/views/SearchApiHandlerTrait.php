<?php

namespace Drupal\search_api\Plugin\views;

use Drupal\search_api\Plugin\views\query\SearchApiQuery;

/**
 * Provides a trait to use for Search API Views handlers.
 */
trait SearchApiHandlerTrait {

  /**
   * Overrides the Views handlers' ensureMyTable() method.
   *
   * This is done since adding a table to a Search API query is neither
   * necessary nor possible, but we still want to stay as compatible as possible
   * to the default SQL query plugin.
   */
  public function ensureMyTable() {
  }

  /**
   * Determines the entity type used by this handler.
   *
   * If this handler uses a relationship, the base class of the relationship is
   * taken into account.
   *
   * @return string
   *   The machine name of the entity type.
   *
   * @see \Drupal\views\Plugin\views\HandlerBase::getEntityType()
   */
  public function getEntityType() {
    if (isset($this->definition['entity_type'])) {
      return $this->definition['entity_type'];
    }
    return parent::getEntityType();
  }

  /**
   * Returns the active search index.
   *
   * @return \Drupal\search_api\IndexInterface|null
   *   The search index to use with this filter, or NULL if none could be
   *   loaded.
   */
  protected function getIndex() {
    if ($this->getQuery()) {
      return $this->getQuery()->getIndex();
    }
    $base_table = $this->view->storage->get('base_table');
    return SearchApiQuery::getIndexFromTable($base_table);
  }

  /**
   * Retrieves the query plugin.
   *
   * @return \Drupal\search_api\Plugin\views\query\SearchApiQuery|null
   *   The query plugin, or NULL if there is no query or it is no Search API
   *   query.
   */
  public function getQuery() {
    if (empty($this->query) || !($this->query instanceof SearchApiQuery)) {
      return NULL;
    }
    return $this->query;
  }

}
