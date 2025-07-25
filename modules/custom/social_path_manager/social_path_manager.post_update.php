<?php

/**
 * @file
 * Contains post-update hooks for the Social Path Manager module.
 */

/**
 * Fix corrupted group aliases and all related content aliases.
 *
 * Fixes aliases with duplicate /stream suffixes.
 */
function social_path_manager_post_update_0001_fix_corrupted_stream_alias(): string {
  $database = \Drupal::database();
  $messenger = \Drupal::messenger();
  $logger = \Drupal::logger('social_path_manager');

  $fixed_count = 0;
  $total_count = 0;

  // Step 1: Find and fix all aliases with duplicate /stream patterns.
  // This includes both group aliases and content aliases.
  $query = $database->select('path_alias', 'pa')
    ->fields('pa', ['id', 'path', 'alias', 'langcode'])
    ->condition('pa.alias', '%/stream/stream%', 'LIKE')
    ->orderBy('pa.alias');

  $result = $query->execute();
  if (!$result) {
    $message = 'Failed to execute database query for corrupted aliases.';
    $messenger->addError($message);
    $logger->error($message);
    return $message;
  }

  $corrupted_aliases = $result->fetchAll();
  $total_count = count($corrupted_aliases);

  if ($total_count === 0) {
    $message = 'No corrupted aliases with duplicate /stream patterns found.';
    $messenger->addStatus($message);
    $logger->info($message);
    return $message;
  }

  $logger->info('Found @count corrupted aliases to fix', [
    '@count' => $total_count,
  ]);

  foreach ($corrupted_aliases as $alias_record) {
    $original_alias = $alias_record->alias;

    // Remove duplicate /stream patterns.
    // This regex handles multiple consecutive /stream occurrences.
    $fixed_alias = preg_replace('#(/stream)+(/stream)+#', '/stream', $original_alias);

    // Ensure we don't have trailing /stream/stream patterns.
    $fixed_alias = preg_replace('#/stream/stream$#', '/stream', $fixed_alias);

    // If the alias was actually changed, update it.
    if ($fixed_alias !== $original_alias) {
      try {
        // Check if the fixed alias already exists for a different path.
        $existing_query = $database->select('path_alias', 'pa')
          ->fields('pa', ['id', 'path'])
          ->condition('pa.alias', $fixed_alias)
          ->condition('pa.langcode', $alias_record->langcode)
          ->condition('pa.id', $alias_record->id, '!=');

        $existing_result = $existing_query->execute();
        $existing = $existing_result ? $existing_result->fetch() : NULL;

        if ($existing) {
          // Delete the corrupted alias as the correct one already exists.
          $database->delete('path_alias')
            ->condition('id', $alias_record->id)
            ->execute();

          $logger->info('Deleted duplicate corrupted alias: @original (correct alias already exists)', [
            '@original' => $original_alias,
          ]);
        }
        else {
          // Update the corrupted alias.
          $database->update('path_alias')
            ->fields(['alias' => $fixed_alias])
            ->condition('id', $alias_record->id)
            ->execute();

          $logger->info('Fixed corrupted alias: @original -> @fixed', [
            '@original' => $original_alias,
            '@fixed' => $fixed_alias,
          ]);
        }

        $fixed_count++;

      }
      catch (\Exception $e) {
        $logger->error('Failed to fix alias @original: @error', [
          '@original' => $original_alias,
          '@error' => $e->getMessage(),
        ]);
      }
    }
  }

  // Step 2: Clear caches to ensure the fixed aliases take effect.
  \Drupal::service('path_alias.manager')->cacheClear();
  \Drupal::service('cache.data')->deleteAll();

  $message = t('Fixed @fixed out of @total corrupted aliases with duplicate /stream patterns.', [
    '@fixed' => $fixed_count,
    '@total' => $total_count,
  ]);

  $messenger->addStatus($message);
  $logger->info($message->render());

  return $message->render();
}
