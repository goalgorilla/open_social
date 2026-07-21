<?php

/**
 * @file
 * Post-update functions for Social Group Default Route module.
 */

use Drupal\Core\Cache\Cache;
use Drupal\Core\Site\Settings;
use Drupal\group\Entity\Group;

/**
 * Rebuild group path aliases for the stream-canonical change.
 *
 * Replaces deleted post-updates 001–004 and social_path_manager 0001: clears
 * stale /home, bare /group/{id}, and /stream aliases, then regenerates via
 * Pathauto and the tab builder.
 */
function social_group_default_route_post_update_005_fix_group_tab_aliases(array &$sandbox): string {
  // Only rebuild needed when the social_path_manager is enabled.
  if (!\Drupal::moduleHandler()->moduleExists('social_path_manager')) {
    $sandbox['#finished'] = 1;
    return t('social_path_manager is not enabled; skipping group alias rebuild.');
  }

  if (!isset($sandbox['ids'])) {
    $sandbox['ids'] = \Drupal::entityQuery('group')
      ->accessCheck(FALSE)
      ->sort('id')
      ->execute();
    $sandbox['total'] = count($sandbox['ids']);
    $sandbox['processed'] = 0;
  }

  if ((int) $sandbox['total'] === 0) {
    $sandbox['#finished'] = 1;
    return t('No groups found; nothing to rebuild.');
  }

  $alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
  $alias_manager = \Drupal::service('path_alias.manager');
  $generator = \Drupal::service('pathauto.generator');
  $batch_size = (int) Settings::get('entity_update_batch_size', 25);
  $group_ids = array_splice($sandbox['ids'], 0, $batch_size);

  foreach (Group::loadMultiple($group_ids) as $group) {
    $gid = $group->id();

    // Drop stale canonical aliases.
    $alias_ids = $alias_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('path', ["/group/$gid", "/group/$gid/home", "/group/$gid/stream"], 'IN')
      ->execute();
    if ($alias_ids) {
      $alias_storage->delete($alias_storage->loadMultiple($alias_ids));
    }

    foreach ($group->getTranslationLanguages() as $langcode => $language) {
      $translation = $group->getTranslation($langcode);

      $generator->updateEntityAlias($translation, 'update');
      // Ensure the tab builder sees the new canonical alias.
      $alias_manager->cacheClear("/group/$gid/stream");
      social_path_manager_update_alias($translation, 'all', TRUE);
    }

    $sandbox['processed']++;
  }

  $sandbox['#finished'] = empty($sandbox['ids'])
    ? 1
    : $sandbox['processed'] / $sandbox['total'];

  if ($sandbox['#finished'] == 1) {
    \Drupal::service('path_alias.manager')->cacheClear();
    Cache::invalidateTags(['route_match']);
    return t('Rebuilt path aliases for @count group(s).', ['@count' => $sandbox['total']]);
  }

  return t('Processed @p of @t groups...', [
    '@p' => $sandbox['processed'],
    '@t' => $sandbox['total'],
  ]);
}
