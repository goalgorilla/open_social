<?php

/**
 * @file
 * Defines theme functions for the Search API module.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\Utility;

/**
 * Returns HTML for a fields form table.
 *
 * @param array $variables
 *   An associative array containing:
 *   - element: A render element representing the form.
 *
 * @return string
 *   The rendered HTML for a fields form table.
 *
 * @ingroup themeable
 */
function theme_search_api_admin_fields_table($variables) {
  $form = $variables['element'];
  $header = array(
    t('Field'),
    t('Machine name'),
    t('Type'),
    t('Boost'),
    t('Remove'),
  );

  $rows = array();
  if (!empty($form['fields'])) {
    foreach (Element::children($form['fields']) as $name) {
      $row = array();
      foreach (Element::children($form['fields'][$name]) as $field) {
        if ($cell = render($form['fields'][$name][$field])) {
          $row[] = $cell;
        }
      }
      if (empty($form['fields'][$name]['description']['#value'])) {
        $rows[] = Utility::deepCopy($row);
      }
      else {
        $rows[] = array(
          'data' => $row,
          'title' => strip_tags($form['fields'][$name]['description']['#value']),
        );
      }
    }
  }

  $note = isset($form['note']) ? $form['note'] : '';
  unset($form['note'], $form['submit']);
  $output = '';
  foreach (Element::children($form) as $key) {
    if (!empty($form[$key])) {
      $output .= render($form[$key]);
    }
  }

  $build = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  $output .= render($build);
  $output .= render($note);

  return $output;
}

/**
 * Returns HTML for the data type overview table.
 *
 * @param array $variables
 *   An associative array containing:
 *   - data_types: An associative array of data types, keyed by data type ID and
 *     containing associative arrays with information about the data type:
 *     - label: The (translated) human-readable label for the data type.
 *     - description: The (translated) description of the data type.
 *     - fallback: The type ID of the fallback type.
 *   - fallback_mapping: array of fallback data types for unsupported data
 *     types.
 *
 * @return string
 *   The rendered HTML for a fields form table.
 *
 * @ingroup themeable
 */
function theme_search_api_admin_data_type_table(array $variables) {
  $data_types = $variables['data_types'];
  $fallback_mapping = $variables['fallback_mapping'];
  $header = array(
    t('Data Type'),
    t('Description'),
    t('Supported'),
  );
  // Only show the column with fallback types if there is actually an
  // unsupported type listed.
  if ($fallback_mapping) {
    $header[] = t('Fallback data type');
  }

  $rows = array();
  $yes = t('Yes');
  $yes_img = 'core/misc/icons/73b355/check.svg';
  $no = t('No');
  $no_img = 'core/misc/icons/e32700/error.svg';
  foreach ($data_types as $data_type_id => $data_type) {
    $has_fallback = isset($fallback_mapping[$data_type_id]);
    $supported_label = $has_fallback ? $no : $yes;
    $supported_icon = array(
      '#theme' => 'image',
      '#uri' => $has_fallback ? $no_img : $yes_img,
      '#width' => 18,
      '#height' => 18,
      '#alt' => $supported_label,
      '#title' => $supported_label,
    );

    $row = array(
      $data_type['label'],
      $data_type['description'],
      array('data' => $supported_icon),
    );

    if ($fallback_mapping) {
      $row[] = $has_fallback ? $data_types[$data_type['fallback']]['label'] : '';
    }

    $rows[] = $row;
  }

  $build = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
  );

  return render($build);
}

/**
 * Returns HTML for a list of form items.
 *
 * Wrapper around the "item_list" theme which uses the child elements as the
 * list items instead of the "#items" key.
 *
 * @param array $variables
 *   An associative array containing a single value, "element", containing the
 *   element to be rendered.
 *
 * @return string
 *   The rendered HTML for the list.
 *
 * @ingroup themeable
 */
function theme_search_api_form_item_list(array $variables) {
  $element = $variables['element'];

  $build = array(
    '#theme' => 'item_list',
  );
  foreach (Element::children($element) as $key) {
    $build['#items'][$key] = $element[$key];
  }

  return render($build);
}

/**
 * Returns HTML for a search server.
 *
 * @param array $variables
 *   An associative array containing:
 *   - server: The server that should be displayed.
 *
 * @return string
 *   The rendered HTML for a search server.
 *
 * @ingroup themeable
 */
function theme_search_api_server($variables) {
  // Get the search server.
  /** @var $server \Drupal\search_api\ServerInterface */
  $server = $variables['server'];

  $output = '';

  if (($description = $server->getDescription())) {
    // Sanitize the description and append to the output.
    $output .= '<p class="description">' . nl2br(Html::escape($description)) . '</p>';
  }

  // Initialize the $rows variable which will hold the different parts of server
  // information.
  $rows = array();
  // Create a row template with references so we don't have to deal with the
  // complicated structure for each individual row.
  $row = array(
    'data' => array(
      array('header' => TRUE),
      '',
    ),
    'class' => array(''),
  );
  // Get the individual parts of the row by reference.
  $label = &$row['data'][0]['data'];
  $info = &$row['data'][1];
  $class = &$row['class'][0];

  // Check if the server is enabled.
  if ($server->status()) {
    $class = 'ok';
    $info = t('enabled (<a href=":url">disable</a>)', array(':url' => $server->toUrl('disable')->toString()));
  }
  else {
    $class = 'warning';
    $info = t('disabled (<a href=":url">enable</a>)', array(':url' => $server->toUrl('enable')->toString()));
  }
  // Append the row and reset variables.
  $label = t('Status');
  $rows[] = Utility::deepCopy($row);
  $class = '';

  // Check if the backend used by the server is valid and get its label.
  if ($server->hasValidBackend()) {
    $backend = $server->getBackend();
    $info = Html::escape($backend->label());
  }
  else {
    $class = 'error';
    $info = t('Invalid or missing backend plugin: %backend_id', array('%backend_id' => $server->getBackendId()));
  }

  // Append the row and reset variables.
  $label = t('Backend class');
  $rows[] = Utility::deepCopy($row);
  $class = '';

  // Build the indexes links container.
  $indexes = array(
    '#theme' => 'links',
    '#attributes' => array('class' => array('inline')),
    '#links' => array(),
  );
  // Add links for all indexes attached to this server.
  foreach ($server->getIndexes() as $index) {
    $indexes['#links'][] = array(
      'title' => $index->label(),
      'url' => $index->toUrl('canonical')->toString(),
    );
  }
  // Check if the indexes variable contains links.
  if ($indexes['#links']) {
    $label = t('Search indexes');
    $info = render($indexes);
    $rows[] = Utility::deepCopy($row);
  }

  // Add backend-specific additional information.
  foreach ($server->viewSettings() as $information) {
    // Convert the extra information and append the information to the row.
    $label = $information['label'];
    $info = $information['info'];
    $class = (empty($information['status']) ? '' : $information['status']);
    $rows[] = Utility::deepCopy($row);
  }

  // Append the server info table to the output.
  $server_info_table = array(
    '#theme' => 'table',
    '#rows' => $rows,
    '#attributes' => array(
      'class' => array(
        'search-api-server-summary',
      ),
    ),
  );
  $output .= render($server_info_table);

  return $output;
}

/**
 * Returns HTML for a search index.
 *
 * @param array $variables
 *   An associative array containing:
 *     - index: The search index to display.
 *
 * @return string
 *   The rendered HTML for a search index.
 *
 * @ingroup themeable
 */
// @todo This executes a search in the theme layer, amongst other things.
//   Definitely not cool.
function theme_search_api_index($variables) {
  // Get the index.
  /** @var $index \Drupal\search_api\IndexInterface */
  $index = $variables['index'];
  $server = $index->hasValidServer() ? $index->getServerInstance() : NULL;
  $tracker = $index->hasValidTracker() ? $index->getTrackerInstance() : NULL;

  $output = '';

  if (($description = $index->getDescription())) {
    // Sanitize the description and append to the output.
    $output .= '<p class="description">' . nl2br(Html::escape($description)) . '</p>';
  }

  // Initialize the $rows variable which will hold the different parts of server
  // information.
  $rows = array();
  // Create a row template with references so we don't have to deal with the
  // complicated structure for each individual row.
  $row = array(
    'data' => array(
      array('header' => TRUE),
      '',
    ),
    'class' => array(''),
  );
  // Get the individual parts of the row by reference.
  $label = &$row['data'][0]['data'];
  $info = &$row['data'][1];
  $class = &$row['class'][0];

  // Check if the index is enabled.
  if ($index->status()) {
    $class = 'ok';
    $info = t('enabled (<a href=":url">disable</a>)', array(':url' => $index->toUrl('disable')->toString()));
  }
  // Check if a server is available and enabled.
  elseif ($server && $server->status()) {
    $class = 'warning';
    $info = t('disabled (<a href=":url">enable</a>)', array(':url' => $index->toUrl('enable')->toString()));
  }
  else {
    $class = 'warning';
    $info = t('disabled');
  }
  // Append the row and reset variables.
  $label = t('Status');
  $rows[] = Utility::deepCopy($row);
  $class = '';

  foreach ($index->getDatasourceIds() as $datasource_id) {
    // Check if the datasource is valid.
    if ($index->isValidDatasource($datasource_id)) {
      $info = $index->getDatasource($datasource_id)->label();
    }
    else {
      $class = 'error';
      $info = t('Invalid or missing datasource plugin: %datasource_id', array('%datasource_id' => $datasource_id));
    }
    // Append the row and reset variables.
    $label = t('Data source');
    $rows[] = Utility::deepCopy($row);
    $class = '';
  }

  // Check if the tracker is valid.
  if ($tracker) {
    $info = $tracker->label();
  }
  else {
    $class = 'error';
    $info = t('Invalid or missing tracker plugin: %tracker_id', array('%tracker_id' => $index->getTrackerId()));
  }
  // Append the row and reset variables.
  $label = t('Tracker');
  $rows[] = Utility::deepCopy($row);
  $class = '';

  // Check if a server is available.
  if ($server) {
    $label = t('Server');
    $info = $server->toLink(NULL, 'canonical')->toString();
    $rows[] = Utility::deepCopy($row);
  }
  elseif ($index->getServerId()) {
    $class = 'error';
    $label = t('Server');
    $info = t('Unknown server set for index: %server_id', array('%server_id' => $index->getServerId()));
    $rows[] = Utility::deepCopy($row);
    $class = '';
  }

  // Check if the index is enabled.
  if ($index->status()) {
    // Check if a valid server is available.
    if ($server) {
      // Here we want to get a raw count of the total number of items on the
      // server for this index. Therefore we circumvent the whole preprocessing
      // (which could add facets, access filters, etc.) and hand the query
      // directly to the server.
      // @todo Add a cleaner way for doing this (i.e., skipping preprocessing).
      $query = $index->query();
      $query->range(0, 0);
      // Tag this special query to allow backend plugins to treat this query
      // differently if required.
      $query->addTag('server_index_status');
      $label = t('Server index status');
      try {
        $response = $server->search($query);
        $count = $response->getResultCount();
        // Build the server index status translation variables.
        $vars = array(':url' => Url::fromUri('https://drupal.org/node/2009804#server-index-status')->toString());
        // Build the server index status info.
        $info = \Drupal::translation()->formatPlural($count, 'There is 1 item indexed on the server for this index. (<a href=":url">More information</a>)', 'There are @count items indexed on the server for this index. (<a href=":url">More information</a>)', $vars);
      }
      catch (SearchApiException $e) {
        $info = t('Error while checking server index status: @message', array('@message' => $e->getMessage()));
        $class = 'error';
      }
      $rows[] = Utility::deepCopy($row);
    }

    $cron_limit = $index->getOption('cron_limit', \Drupal::config('search_api.settings')->get('default_cron_limit'));
    // Check if the cron limit is higher than zero.
    if ($cron_limit > 0) {
      $class = 'ok';
      $info = \Drupal::translation()->formatPlural($cron_limit, 'During cron runs, 1 item will be indexed per batch.', 'During cron runs, @count items will be indexed per batch.');
    }
    else {
      $class = 'warning';
      $info = t('No items will be indexed during cron runs.');
    }
    // Append the row and reset variables.
    $label = t('Cron batch size');
    $rows[] = Utility::deepCopy($row);
    $class = '';

    // Add the indexing progress bar.
    if ($tracker) {
      $indexed_count = $tracker->getIndexedItemsCount();
      $total_count = $tracker->getTotalItemsCount();
      $percent = ($total_count !== 0) ? (100 * $indexed_count / $total_count) : 0;

      $index_progress = array(
        '#theme' => 'progress_bar',
        '#percent' => $percent,
        '#message' => t('@indexed/@total indexed', array('@indexed' => $indexed_count, '@total' => $total_count)),
      );
      $output .= '<h3>' . t('Index status') . '</h3>';
      $output .= '<div class="search-api-index-status">' . render($index_progress) . '</div>';
    }
  }

  // Append the index info table to the output.
  $index_info_table = array(
    '#theme' => 'table',
    '#rows' => $rows,
    '#attributes' => array(
      'class' => array(
        'search-api-index-summary',
      ),
    ),
  );
  $output .= render($index_info_table);

  return $output;
}
