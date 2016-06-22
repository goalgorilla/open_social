<?php

namespace Drupal\search_api\Query;

use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Implements a simple request stack-aware cache for the search results.
 */
class ResultsCache implements ResultsCacheInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Storage for the results, keyed by request and search ID.
   *
   * @var \SplObjectStorage
   */
  protected $results;

  /**
   * NULL value to use as a key for the results storage.
   *
   * @var object
   */
  protected $null;

  /**
   * Constructs a ResultsCache object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    $this->results = new \SplObjectStorage();
    $this->null = (object) array();
  }

  /**
   * {@inheritdoc}
   */
  public function addResults(ResultSetInterface $results) {
    // @todo Create getter and setter methods for the search ID.
    $search_id = $results->getQuery()->getOption('search id', '');
    $request = $this->getCurrentRequest();
    if (!isset($this->results[$request])) {
      $this->results[$request] = array(
        $search_id => $results,
      );
    }
    else {
      // It's not possible to directly assign array values to an array inside of
      // a \SplObjectStorage object. So we have to first retrieve the array,
      // then add the results to it, then store it again.
      $cache = $this->results[$request];
      $cache[$search_id] = $results;
      $this->results[$request] = $cache;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getResults($search_id) {
    $request = $this->getCurrentRequest();
    if (isset($this->results[$request])) {
      $results = $this->results[$request];
      if (!empty($results[$search_id])) {
        return $this->results[$request][$search_id];
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function removeResults($search_id) {
    $request = $this->getCurrentRequest();
    if (isset($this->results[$request])) {
      $cache = $this->results[$request];
      unset($cache[$search_id]);
      $this->results[$request] = $cache;
    }
  }

  /**
   * Retrieves the current request.
   *
   * If there is no current request, instead of returning NULL this will instead
   * return a unique object to be used in lieu of a NULL key.
   *
   * @return \Symfony\Component\HttpFoundation\Request|object
   *   The current request, if present; or this object's representation of the
   *   NULL key.
   */
  protected function getCurrentRequest() {
    return $this->requestStack->getCurrentRequest();
  }

}
