<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Psr\Container\ContainerInterface;

class SearchBridge {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Delete all data from SOLR.
   *
   * The search_api and search_api_solr modules create specific delete queries
   * for SOLR data based on the indexes. However, the site hash that they use
   * may change in between databases which can cause old test data not to be
   * cleaned up. This can cause issues when the data matches and the modules
   * try to load it.
   *
   * See https://www.drupal.org/project/search_api_solr/issues/3218868.
   */
  #[Command('solr-clear')]
  public function clearSolr() : array {
    /** @var \Drupal\search_api\IndexInterface[] $indexes */
    $indexes = $this->entityTypeManager
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

    return ['status' => 'ok'];
  }

}
