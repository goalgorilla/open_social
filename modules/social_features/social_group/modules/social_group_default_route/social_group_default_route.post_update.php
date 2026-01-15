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
    return t('Processed @progress aliases of @total groups...', ['@progress' => $sandbox['progress'], '@total' => $sandbox['total']]);
  }
}
