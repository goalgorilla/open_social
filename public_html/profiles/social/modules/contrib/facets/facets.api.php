<?php
/**
 * @file
 * Hooks provided by the Facets module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the Facets Query Type mapping.
 *
 * Modules may implement this hook to alter the mapping that defines how a
 * certain data type should be handled in Search API based Facets.
 *
 * @param array $query_types
 *   The Search API backend info array, keyed by backend ID.
 *
 * @see \Drupal\facets\Plugin\facets\facet_source\SearchApiBaseFacetSource
 */
function hook_facets_search_api_query_type_mapping_alter($backend_plugin_id, array &$query_types) {
  if ($backend_plugin_id == 'search_api_solr') {
    $query_types['string'] = 'search_api_solr_string';
  }
}
