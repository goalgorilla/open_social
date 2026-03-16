<?php

/**
 * @file
 * Post-update functions for Social Group Default Route module.
 */

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\social_group_default_route\GroupPathAliasFixService;

/**
 * Fixes broken path aliases for groups caused by unwanted suffixes.
 *
 * This function performs two operations in a single pass:
 * 1. Cleans up duplicate/stale aliases for /group/{id} (canonical paths).
 * 2. Fixes aliases with unwanted suffixes (-0, -1, -2) for /group/{id}/home.
 *
 * Uses batch processing to handle large numbers of groups efficiently.
 *
 * @param array $sandbox
 *   A reference to the sandbox array used to manage the operation's state:
 *   - groups: Array of group IDs to process.
 *   - total: Total number of groups to process.
 *   - progress: Number of groups processed so far.
 *   - fixed_suffixes: Number of aliases with suffixes fixed.
 *   - #finished: Completion ratio (0 to 1).
 *
 * @throws \Exception
 *   Throws an exception if there is a problem updating the database or other
 *   database-related issues occur.
 */
function social_group_default_route_post_update_001_fix_groups_path_aliases(array &$sandbox): MarkupInterface|string {
  // By default, skip this update unless explicitly opted in.
  if (!\Drupal::state()->get('social_group_default_route_fix_aliases_opt_in', FALSE)) {
    $sandbox['#finished'] = 1;
    \Drupal::logger('social_group_default_route')->info('Platform has opted out of alias fixes for the Group Default Route changes.');
    return t('Platform has opted out of alias fixes for the Group Default Route changes.');
  }

  /** @var \Drupal\social_group_default_route\GroupPathAliasFixService $service */
  $service = \Drupal::service(GroupPathAliasFixService::class);

  // Initialize sandbox on first run.
  if (!isset($sandbox['groups'])) {
    $sandbox['groups'] = \Drupal::entityQuery('group')
      ->accessCheck(FALSE)
      ->sort('id')
      ->execute();

    $sandbox['total'] = count($sandbox['groups']);
    $sandbox['progress'] = 0;
    $sandbox['fixed_suffixes'] = 0;

    // If no groups to process, mark as finished.
    if ($sandbox['total'] === 0) {
      $sandbox['#finished'] = 1;
      return t('No groups found to process.');
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 25);
  $groups_to_process = array_splice($sandbox['groups'], 0, $batch_size);

  foreach ($groups_to_process as $gid) {
    $service->fixGroupPathAliases($gid);
    $sandbox['progress']++;
  }

  // Calculate completion ratio.
  if (empty($sandbox['groups'])) {
    $sandbox['#finished'] = 1;

    // All groups processed, invalidate cache and mark as finished.
    \Drupal::service('path_alias.manager')->cacheClear();
    Cache::invalidateTags(['route_match']);

    return Markup::create(sprintf(
        'Fixed group path aliases: fixed %s aliases with unwanted suffixes from %s groups.' . PHP_EOL . 'A list of skipped:' . PHP_EOL . '%s',
        count($service->getReports('fixed_suffixes')),
        $sandbox['total'],
        implode(PHP_EOL, $service->getReports('skipped')),
      )
    );
  }
  else {
    // More groups to process.
    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
    return t('Processed @progress aliases of @total groups...', [
      '@progress' => $sandbox['progress'],
      '@total' => $sandbox['total'],
    ]);
  }
}

/**
 * Deletes /group/{id}/home aliases and shortens /group/{id}/stream aliases.
 *
 * This update performs two operations in a single pass per group:
 * 1. Deletes path aliases for /group/{id}/home.
 * 2. For /group/{id}/stream, shortens the alias by removing a trailing /stream
 *    when present.
 *
 * @param array $sandbox
 *   A reference to the sandbox array used to manage the operation's state:
 *   - groups: Array of group IDs to process.
 *   - total: Total number of groups to process.
 *   - progress: Number of groups processed so far.
 *   - #finished: Completion ratio (0 to 1).
 *
 * @return \Drupal\Component\Render\MarkupInterface
 *   A message describing the outcome.
 */
function social_group_default_route_post_update_002_remove_home_and_stream_aliases(array &$sandbox): MarkupInterface {
  if (!\Drupal::state()->get('social_group_default_route_fix_aliases_opt_in', FALSE)) {
    $sandbox['#finished'] = 1;
    \Drupal::logger('social_group_default_route')->info('Platform has opted out of alias fixes for the Group Default Route changes.');
    return t('Platform has opted out of alias fixes for the Group Default Route changes.');
  }

  $database = \Drupal::database();

  if (!isset($sandbox['groups'])) {
    $sandbox['groups'] = \Drupal::entityQuery('group')
      ->accessCheck(FALSE)
      ->sort('id')
      ->execute();

    $sandbox['total'] = count($sandbox['groups']);
    $sandbox['progress'] = 0;

    if ($sandbox['total'] === 0) {
      $sandbox['#finished'] = 1;
      return t('No groups found to process.');
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 25);
  $groups_to_process = array_splice($sandbox['groups'], 0, $batch_size);

  foreach ($groups_to_process as $gid) {
    foreach (GroupPathAliasFixService::ALIAS_TABLES as $table) {
      $has_home_alias = (bool) $database->select($table, 'pa')
        ->fields('pa', ['id'])
        ->condition('path', "/group/$gid/home")
        ->execute()
        ?->fetchField();

      if (!$has_home_alias) {
        continue;
      }

      $database->delete($table)
        ->condition('path', "/group/$gid/home")
        ->execute();

      $stream_alias = $database->select($table, 'pa')
        ->fields('pa', ['id', 'alias'])
        ->condition('path', "/group/$gid/stream")
        ->execute()
        ?->fetchAssoc();

      if (!isset($stream_alias['id'], $stream_alias['alias'])) {
        continue;
      }

      $alias = $stream_alias['alias'];
      if (!str_ends_with($alias, '/stream')) {
        continue;
      }

      $database->update($table)
        ->fields(['alias' => substr($alias, 0, -strlen('/stream'))])
        ->condition('id', $stream_alias['id'])
        ->execute();
    }
    $sandbox['progress']++;
  }

  if (empty($sandbox['groups'])) {
    $sandbox['#finished'] = 1;
    \Drupal::service('path_alias.manager')->cacheClear();
    Cache::invalidateTags(['route_match']);
    return t('Removed /group/{id}/home aliases and shortened /group/{id}/stream aliases for @total groups.', ['@total' => $sandbox['total']]);
  }

  $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
  return t('Processed @progress of @total groups...', ['@progress' => $sandbox['progress'], '@total' => $sandbox['total']]);
}

/**
 * Moves path aliases from /group/{id} to /group/{id}/stream.
 *
 * Also shortens aliases by removing trailing /stream.
 *
 * 1. Path: alias rows with path /group/{id} are updated to
 *    path /group/{id}/stream so that /group/{id} no longer
 *    exists. Where /group/{id}/stream already has an alias
 *    for the same langcode, the /group/{id} row is removed.
 * 2. Alias: rows with path /group/{id}/stream whose alias
 *    ends with /stream (e.g. /group/slug/stream) are updated
 *    to the shorter alias (e.g. /group/slug).
 *
 * @param array $sandbox
 *   Batch sandbox: path_rows, alias_rows, total, progress, counters.
 *
 * @throws \Exception
 *   Throws an exception if there is a problem updating the database.
 */
function social_group_default_route_post_update_003_canonical_path_to_stream(array &$sandbox): MarkupInterface {
  if (!\Drupal::state()->get('social_group_default_route_fix_aliases_opt_in', FALSE)) {
    $sandbox['#finished'] = 1;
    \Drupal::logger('social_group_default_route')->info('Platform has opted out of alias fixes for the Group Default Route changes.');
    return t('Platform has opted out of alias fixes for the Group Default Route changes.');
  }

  $database = \Drupal::database();
  $tables = ['path_alias', 'path_alias_revision'];
  $batch_size = Settings::get('entity_update_batch_size', 25);

  // Initialize sandbox on first run: load both Part 1 and
  // Part 2 rows so total is fixed from the start and
  // progress percentage never jumps backward.
  if (!isset($sandbox['path_rows'])) {
    $canonical_rows = $database->select('path_alias', 'pa')
      ->fields('pa', ['id', 'path', 'alias', 'langcode'])
      ->condition('path', '/group/%', 'LIKE')
      ->condition('path', '/group/%/%', 'NOT LIKE')
      ->execute()?->fetchAll() ?? [];
    $canonical_rows = array_filter($canonical_rows, function ($row): bool {
      return (bool) preg_match('#^/group/(\d+)$#', $row->path);
    });

    // Part 1: distinct (path, langcode) for path updates.
    $seen = [];
    $sandbox['path_rows'] = [];
    foreach ($canonical_rows as $row) {
      $key = $row->path . ':' . $row->langcode;
      if (!isset($seen[$key])) {
        $seen[$key] = TRUE;
        $sandbox['path_rows'][] = (object) ['path' => $row->path, 'langcode' => $row->langcode];
      }
    }

    // Part 2: rows with path /group/{id}/stream and alias ending in /stream.
    $alias_rows = $database->select('path_alias', 'pa')
      ->fields('pa', ['id', 'path', 'alias', 'langcode'])
      ->condition('path', '/group/%/stream', 'LIKE')
      ->execute()?->fetchAll() ?? [];
    $sandbox['alias_rows'] = array_values(array_filter($alias_rows, function ($row) {
      if (!preg_match('#^/group/\d+/stream$#', $row->path) || !str_ends_with($row->alias, '/stream')) {
        return FALSE;
      }
      $new_alias = substr($row->alias, 0, -strlen('/stream'));
      return $new_alias !== '' && $new_alias !== '/';
    }));

    // Also include canonical rows whose alias ends with
    // /stream; Part 1 will move their path to
    // /group/{id}/stream, and Part 2 should shorten them.
    $extra = array_values(array_filter($canonical_rows, function ($row) {
      if (!str_ends_with($row->alias, '/stream')) {
        return FALSE;
      }
      $new_alias = substr($row->alias, 0, -strlen('/stream'));
      return $new_alias !== '' && $new_alias !== '/';
    }));
    $sandbox['alias_rows'] = array_merge($sandbox['alias_rows'], $extra);

    $sandbox['total'] = count($sandbox['path_rows']) + count($sandbox['alias_rows']);
    $sandbox['progress'] = 0;
    $sandbox['path_updated'] = 0;
    $sandbox['path_deleted'] = 0;
    $sandbox['alias_shortened'] = 0;
    $sandbox['alias_conflict_deleted'] = 0;
    $sandbox['deleted_ids'] = [];
  }

  // Part 1: Process a batch of path rows.
  if (!empty($sandbox['path_rows'])) {
    $batch = array_splice($sandbox['path_rows'], 0, $batch_size);
    foreach ($batch as $row) {
      $path = $row->path;
      preg_match('#^/group/(\d+)$#', $path, $m);
      $gid = $m[1];
      $stream_path = "/group/$gid/stream";
      $langcode = $row->langcode;

      $stream_exists = (bool) $database->select('path_alias', 'pa')
        ->fields('pa', ['id'])
        ->condition('path', $stream_path)
        ->condition('langcode', $langcode)
        ->execute()?->fetchField();

      $transaction = $database->startTransaction();
      try {
        if ($stream_exists) {
          $ids_deleted = $database->select('path_alias', 'pa')
            ->fields('pa', ['id'])
            ->condition('path', $path)
            ->condition('langcode', $langcode)
            ->execute()?->fetchCol() ?? [];
          foreach ($tables as $table) {
            $database->delete($table)
              ->condition('path', $path)
              ->condition('langcode', $langcode)
              ->execute();
          }
          $sandbox['path_deleted']++;
          foreach ($ids_deleted as $id) {
            $sandbox['deleted_ids'][$id] = TRUE;
          }
        }
        else {
          foreach ($tables as $table) {
            $database->update($table)
              ->fields(['path' => $stream_path])
              ->condition('path', $path)
              ->condition('langcode', $langcode)
              ->execute();
          }
          $sandbox['path_updated']++;
        }
        unset($transaction);
      }
      catch (\Exception $e) {
        $transaction->rollBack();
        throw $e;
      }
    }
    $sandbox['progress'] += count($batch);
  }

  // Part 2: Process a batch of alias rows (skip ids already deleted by Part 1).
  if (!empty($sandbox['alias_rows'])) {
    $batch = array_splice($sandbox['alias_rows'], 0, $batch_size);
    $batch_count = count($batch);
    $batch = array_values(array_filter($batch, function ($row) use ($sandbox) {
      return !isset($sandbox['deleted_ids'][$row->id]);
    }));
    foreach ($batch as $row) {
      $new_alias = substr($row->alias, 0, -strlen('/stream'));

      $alias_taken = (bool) $database->select('path_alias', 'pa')
        ->fields('pa', ['id'])
        ->condition('alias', $new_alias)
        ->condition('langcode', $row->langcode)
        ->condition('id', $row->id, '<>')
        ->execute()?->fetchField();

      $transaction = $database->startTransaction();
      try {
        if ($alias_taken) {
          foreach ($tables as $table) {
            $database->delete($table)
              ->condition('id', $row->id)
              ->execute();
          }
          $sandbox['alias_conflict_deleted']++;
        }
        else {
          foreach ($tables as $table) {
            $database->update($table)
              ->fields(['alias' => $new_alias])
              ->condition('id', $row->id)
              ->execute();
          }
          $sandbox['alias_shortened']++;
        }
        unset($transaction);
      }
      catch (\Exception $e) {
        $transaction->rollBack();
        throw $e;
      }
    }
    $sandbox['progress'] += $batch_count;
  }

  if (empty($sandbox['path_rows']) && empty($sandbox['alias_rows'])) {
    $sandbox['#finished'] = 1;
    \Drupal::service('path_alias.manager')->cacheClear();
    Cache::invalidateTags(['route_match']);
    return t('Group path aliases: updated @path_updated path(s) to /group/{id}/stream, removed @path_deleted duplicate(s), shortened @alias_shortened alias(es) by removing trailing /stream, removed @alias_conflict_deleted alias(es) due to conflict.', [
      '@path_updated' => $sandbox['path_updated'],
      '@path_deleted' => $sandbox['path_deleted'],
      '@alias_shortened' => $sandbox['alias_shortened'],
      '@alias_conflict_deleted' => $sandbox['alias_conflict_deleted'],
    ]);
  }

  $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
  return t('Processed @progress of @total group path alias updates...', [
    '@progress' => $sandbox['progress'],
    '@total' => $sandbox['total'],
  ]);
}

/**
 * Generates URL aliases for groups that have no existing alias.
 *
 * @param array $sandbox
 *   Batch sandbox; passed through to Pathauto (current, count, total, results).
 *
 * @throws \Exception
 *   Re-throws any exception from Pathauto.
 */
function social_group_default_route_post_update_004_generate_group_aliases(array &$sandbox): MarkupInterface|string {
  if (!\Drupal::state()->get('social_group_default_route_fix_aliases_opt_in', FALSE)) {
    $sandbox['#finished'] = 1;
    \Drupal::logger('social_group_default_route')->info('Platform has opted out of alias fixes for the Group Default Route changes.');
    return t('Platform has opted out of alias fixes for the Group Default Route changes.');
  }

  $alias_type_manager = \Drupal::service('plugin.manager.alias_type');
  if (!$alias_type_manager->hasDefinition('canonical_entities:group')) {
    $sandbox['#finished'] = 1;
    return t('Pathauto group alias type is not available; nothing to generate.');
  }

  if (!isset($sandbox['results'])) {
    $sandbox['results'] = ['updates' => 0];
  }

  $context = [
    'sandbox' => &$sandbox,
    'results' => &$sandbox['results'],
    'finished' => 0,
    'message' => '',
  ];

  /** @var \Drupal\pathauto\AliasTypeBatchUpdateInterface $alias_type */
  $alias_type = $alias_type_manager->createInstance('canonical_entities:group');
  $alias_type->batchUpdate('create', $context);

  if (isset($context['sandbox']['total']) && $context['sandbox']['count'] == $context['sandbox']['total']) {
    $context['finished'] = 1;
  }
  $sandbox['#finished'] = $context['finished'];

  if ($sandbox['#finished'] == 1) {
    \Drupal::service('path_alias.manager')->cacheClear();
    Cache::invalidateTags(['route_match']);
    $count = $sandbox['results']['updates'];
    return $count
      ? t('Generated @count URL alias(es) for groups (un-aliased paths only).', ['@count' => $count])
      : t('No new URL aliases to generate for groups.');
  }

  return '';
}

/**
 * Fixes group tab aliases that don't match the group's canonical alias.
 *
 * This handles two scenarios:
 * 1. Aliases with /stream/ embedded (e.g., /group/slug/stream/about should
 *    be /group/slug/about) left over from when stream aliases were shortened.
 * 2. Aliases with stale prefixes (e.g., /group/slug-1/about when the
 *    canonical alias is /group/slug) for groups whose suffix was fixed.
 *
 * @param array $sandbox
 *   A reference to the sandbox array used to manage the operation's state.
 *
 * @throws \Exception
 *   Throws an exception if there is a problem updating the database.
 */
function social_group_default_route_post_update_005_fix_group_tab_aliases(array &$sandbox): MarkupInterface {
  if (!\Drupal::state()->get('social_group_default_route_fix_aliases_opt_in', FALSE)) {
    $sandbox['#finished'] = 1;
    \Drupal::logger('social_group_default_route')->info('Platform has opted out of alias fixes for the Group Default Route changes.');
    return t('Platform has opted out of alias fixes for the Group Default Route changes.');
  }

  /** @var \Drupal\social_group_default_route\GroupPathAliasFixService $service */
  $service = \Drupal::service(GroupPathAliasFixService::class);

  if (!isset($sandbox['groups'])) {
    $sandbox['groups'] = \Drupal::entityQuery('group')
      ->accessCheck(FALSE)
      ->sort('id')
      ->execute();

    $sandbox['total'] = count($sandbox['groups']);
    $sandbox['progress'] = 0;
    // Accumulate reports in $sandbox because the service instance (and its
    // in-memory $reports array) is recreated on each batch iteration.
    $sandbox['reports'] = [
      'fixed_tab' => [],
    ];

    if ($sandbox['total'] === 0) {
      $sandbox['#finished'] = 1;
      return t('No groups found to process.');
    }
  }

  $batch_size = Settings::get('entity_update_batch_size', 25);
  $groups_to_process = array_splice($sandbox['groups'], 0, $batch_size);

  foreach ($groups_to_process as $gid) {
    $service->fixGroupTabAliases($gid);
    $sandbox['progress']++;
  }

  // Merge this batch's reports into the sandbox-persisted totals.
  $sandbox['reports']['fixed_tab'] = array_merge(
    $sandbox['reports']['fixed_tab'],
    $service->getReports('fixed_tab'),
  );

  if (empty($sandbox['groups'])) {
    $sandbox['#finished'] = 1;

    \Drupal::service('path_alias.manager')->cacheClear();
    Cache::invalidateTags(['route_match']);

    return t('Fixed group tab aliases for @total groups: @count tab aliases fixed.', [
      '@total' => $sandbox['total'],
      '@count' => count($sandbox['reports']['fixed_tab']),
    ]);
  }

  $sandbox['#finished'] = $sandbox['progress'] / $sandbox['total'];
  return t('Processed @progress of @total groups...', [
    '@progress' => $sandbox['progress'],
    '@total' => $sandbox['total'],
  ]);
}
