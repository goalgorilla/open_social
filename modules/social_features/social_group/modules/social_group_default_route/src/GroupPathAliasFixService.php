<?php

namespace Drupal\social_group_default_route;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Service to fix broken path aliases for groups.
 *
 * This service performs two operations:
 * 1. Cleans up duplicate/stale aliases for /group/{id} (canonical paths).
 * 2. Cleans up redundant aliases for /group/{id}/home.
 * 3. Fixes aliases with unwanted suffixes (-0, -1, -2) for /group/{id}/home.
 *
 * This fixes the issue where groups get -0, -1 suffixes in their URL aliases
 * after being updated. The issue is caused by stale aliases remaining in the
 * database when a group's title was changed.
 */
class GroupPathAliasFixService {

  /**
   * An array of unwanted suffixes that should be excluded or filtered out.
   */
  const array UNWANTED_SUFFIXES = ['-0', '-1', '-2'];

  /**
   * List of alias table names used in the application.
   *
   * @var array<int, string>
   */
  const array ALIAS_TABLES = ['path_alias', 'path_alias_revision'];

  /**
   * Reports storage array indexed by type.
   *
   * @var array<string, array>
   */
  protected array $reports = [];

  /**
   * Constructs a GroupPathAliasFixService object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\path_alias\AliasManagerInterface $aliasManager
   *   The path alias manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    protected Connection $database,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AliasManagerInterface $aliasManager,
    protected LanguageManagerInterface $languageManager,
  ) {}

  /**
   * Registers a report of the specified type.
   *
   * @param string $type
   *   The report type, either "success" or "unsuccess".
   * @param int|string $value
   *   The value to register in the report.
   */
  public function registerReport(string $type, int|string $value): void {
    if (!isset($this->reports[$type])) {
      $this->reports[$type] = [];
    }

    $this->reports[$type][] = $value;
  }

  /**
   * Gets reports for the specified type.
   *
   * @param string $type
   *   The report type, either "success" or "unsuccess".
   *
   * @return array
   *   An array of reports for the specified type,
   *   or an empty array if none exists.
   */
  public function getReports(string $type): array {
    return $this->reports[$type] ?? [];
  }

  /**
   * Fixes path aliases for a single group.
   *
   * This method performs both operations:
   * 1. Cleans up duplicate aliases for /group/{id} (canonical path)
   * 2. Clean up redundant alias for the removed path /group/{id}/home.
   * 3. Fixes broken aliases with suffixes for /group/{id}/stream.
   *
   * @param int|string $gid
   *   The group ID to process.
   *
   * @throws \Exception
   *   Throws an exception if there is a problem updating the database.
   */
  public function fixGroupPathAliases(int|string $gid): void {
    $languages = $this->languageManager->getLanguages();

    foreach (array_keys($languages) as $langcode) {
      // We need to apply updates only in case if a group home alias exists.
      if (!$this->groupHomeAliasExists($gid, $langcode)) {
        continue;
      }

      $affected_alias = $this->fixGroupCanonicalPathAliases($gid, $langcode);
      if (!isset($affected_alias['new_alias'])) {
        // There is nothing to fix.
        continue;
      }

      $broken_alias = $affected_alias['broken_alias'];
      $new_alias = $affected_alias['new_alias'];
      // Fix aliases for group tabs (topics, events, members, etc.)
      $this->updateGroupPagesAliases($broken_alias, $new_alias, $gid, $langcode);
      // Fix aliases for group content nodes.
      $this->updateGroupContentAliases($broken_alias, $new_alias, $gid, $langcode);
    }
  }

  /**
   * Ensures the group home alias exists.
   *
   * @param int|string $gid
   *   The group ID.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if the alias exists or was created, FALSE otherwise.
   *
   * @throws \Exception
   */
  protected function groupHomeAliasExists(int|string $gid, string $langcode): bool {
    return (bool) $this->database->select('path_alias', 'pa')
      ->fields('pa', ['id'])
      ->condition('langcode', $langcode)
      ->condition('path', "/group/$gid/home")
      ->execute()
      ?->fetchField();
  }

  /**
   * Checks if a new alias would conflict with existing aliases.
   *
   * @param string $new_alias
   *   The new alias to check.
   * @param int|string $gid
   *   The group ID.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if conflict exists, FALSE otherwise.
   *
   * @throws \Exception
   */
  protected function hasGroupCanonicalAliasConflict(string $new_alias, int|string $gid, string $langcode): bool {
    $exists = $this->database->select('path_alias', 'pa')
      ->fields('pa', ['id'])
      ->condition('alias', $new_alias)
      ->condition('langcode', $langcode)
      // Exclude canonical aliases for the current group.
      ->condition('path', "/group/$gid/stream", '<>')
      ->condition('path', "/group/$gid/home", '<>')
      ->condition('path', "/group/$gid", '<>')
      ->execute()
      ?->fetchField();

    return (bool) $exists;
  }

  /**
   * Checks if a new alias would conflict with existing aliases.
   *
   * This method checks if the provided alias already exists in the database,
   * excluding the current alias being checked (if an alias ID is provided).
   *
   * @param string $new_alias
   *   The new alias to check for conflicts.
   * @param string $langcode
   *   The language code.
   * @param int|string $alias_id
   *   The ID of the alias to exclude from conflict checking (optional).
   * @param string $path
   *   The original path to exclude (optional).
   *
   * @return bool
   *   TRUE if a conflict exists, FALSE otherwise.
   *
   * @throws \Exception
   */
  protected function hasAliasConflict(string $new_alias, string $langcode, int|string $alias_id = 0, string $path = ''): bool {
    $query = $this->database->select('path_alias', 'pa')
      ->fields('pa', ['id'])
      ->condition('langcode', $langcode)
      ->condition('alias', $new_alias);

    // Exclude current alias.
    if ($alias_id) {
      $query->condition('id', $alias_id, '<>');
    }

    // Exclude by path.
    if ($path) {
      $query->condition('path', $path, '<>');
    }

    return (bool) $query->execute()?->fetchField();
  }

  /**
   * Checks if the unsuffixed alias exists as a canonical alias for this group.
   *
   * This verifies that the suffix being stripped was actually a Pathauto
   * deduplication artifact (the group has both /slug and /slug-N) rather
   * than a legitimate part of the title-derived slug.
   *
   * @param string $alias
   *   The unsuffixed alias to check.
   * @param int|string $gid
   *   The group ID.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if the alias exists for this group's canonical paths.
   */
  protected function unsuffixedAliasExistsForGroup(string $alias, int|string $gid, string $langcode): bool {
    $canonical_paths = ["/group/$gid", "/group/$gid/home", "/group/$gid/stream"];

    $exists = $this->database->select('path_alias', 'pa')
      ->fields('pa', ['id'])
      ->condition('alias', $alias)
      ->condition('langcode', $langcode)
      ->condition('path', $canonical_paths, 'IN')
      ->execute()
      ?->fetchField();

    return (bool) $exists;
  }

  /**
   * Fixes canonical path aliases for a group by removing unwanted suffixes.
   *
   * This method identifies broken aliases for a group's home path that contain
   * unwanted suffixes (-0, -1, -2) and fixes them by removing the suffix. It
   * also updates related aliases (stream path) and removes duplicate canonical
   * path aliases to prevent conflicts.
   *
   * @param int|string $gid
   *   The group ID to process.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   An associative array with keys:
   *   - 'broken_alias': The original broken alias with suffix, or NULL if none
   *     found.
   *   - 'new_alias': The fixed alias without suffix, or NULL if none found.
   *
   * @throws \Exception
   *   Throws an exception if there is a problem updating the database.
   */
  protected function fixGroupCanonicalPathAliases(int|string $gid, string $langcode): array {
    $target_aliases = [
      'broken_alias' => NULL,
      'new_alias' => NULL,
    ];

    $broken_alias = $this->getBrokenCanonicalAlias($gid, $langcode);

    if (empty($broken_alias)) {
      // No broken aliases found.
      return $target_aliases;
    }

    $new_alias = $broken_alias;
    foreach (self::UNWANTED_SUFFIXES as $suffix) {
      $new_alias = $this->strTrimEnd($new_alias, $suffix);
    }

    // Only proceed if the unsuffixed alias already exists for this group,
    // which indicates a real Pathauto duplication (e.g. both /my-group and
    // /my-group-2 exist). If only /my-group-2 exists, the -2 is part of
    // the legitimate slug (e.g. group title "My Group 2") and should not
    // be stripped.
    if (!$this->unsuffixedAliasExistsForGroup($new_alias, $gid, $langcode)) {
      return $target_aliases;
    }

    // Make sure the new alias doesn't conflict with existing aliases.
    if ($this->hasGroupCanonicalAliasConflict($new_alias, $gid, $langcode)) {
      return $target_aliases;
    }

    // Use transaction for data safety.
    $transaction = $this->database->startTransaction();
    try {
      foreach (static::ALIAS_TABLES as $table) {
        // Update group stream (canonical) path alias.
        $this->database->update($table)
          ->fields(['alias' => $new_alias])
          ->condition('langcode', $langcode)
          ->condition('path', "/group/$gid/stream")
          ->execute();

        // Delete redundant group home path alias.
        $this->database->delete($table)
          ->condition('langcode', $langcode)
          ->condition('path', "/group/$gid/home")
          ->execute();

        // Delete canonical path alias as it creates conflicts
        // with a stream alias.
        // Also, for the moment when creating a new group, the alias for a
        // canonical path isn't generated anymore.
        $this->database->delete($table)
          ->condition('langcode', $langcode)
          ->condition('path', "/group/$gid")
          ->execute();
      }
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }

    $target_aliases['broken_alias'] = $broken_alias;
    $target_aliases['new_alias'] = $new_alias;

    return $target_aliases;
  }

  /**
   * Updates related path aliases that depend on the broken alias.
   *
   * This method updates all related aliases that are dependent on the broken
   * alias, such as group topics, events, members, etc.
   *
   * @param string $broken_alias
   *   The broken alias with a suffix.
   * @param string $new_alias
   *   The new alias without a suffix.
   * @param int|string $gid
   *   The group ID.
   * @param string $langcode
   *   The language code.
   *
   * @throws \Exception
   */
  protected function updateGroupPagesAliases(string $broken_alias, string $new_alias, int|string $gid, string $langcode): void {
    $escaped_broken_alias = $this->database->escapeLike($broken_alias);
    $related_aliases = $this->database->select('path_alias', 'pa')
      ->fields('pa')
      ->condition('alias', $escaped_broken_alias . '/%', 'LIKE')
      ->condition('path', "/group/$gid/%", 'LIKE')
      ->condition('langcode', $langcode)
      ->execute()
      ?->fetchAll(\PDO::FETCH_ASSOC);

    if (!$related_aliases) {
      return;
    }

    // Use transaction for data safety.
    $transaction = $this->database->startTransaction();
    try {
      foreach ($related_aliases as $related_alias) {
        $fixed_alias = str_replace($broken_alias, $new_alias, $related_alias['alias']);

        if ($this->hasAliasConflict($fixed_alias, $langcode, alias_id: $related_alias['id'])) {
          // Seems like we have another same alias. Skip it to avoid conflicts.
          $this->registerReport('skipped', $related_alias['alias']);
          continue;
        }

        foreach (static::ALIAS_TABLES as $table) {
          $this->database->update($table)
            ->fields([
              'alias' => $fixed_alias,
            ])
            ->condition('id', $related_alias['id'])
            ->condition('revision_id', $related_alias['revision_id'])
            ->condition('langcode', $langcode)
            ->execute();
        }

        $this->registerReport('fixed_suffixes', $related_alias['alias']);
      }
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * Updates path aliases for group content nodes.
   *
   * This method finds all nodes that belong to the specified group and updates
   * their path aliases that contain the broken alias suffix.
   * It identifies nodes through group relationships and fixes their aliases
   * by replacing the broken alias with the corrected alias.
   *
   * @param string $broken_alias
   *   The broken alias with a suffix that needs to be replaced.
   * @param string $new_alias
   *   The new alias without a suffix to replace the broken alias.
   * @param int|string $gid
   *   The group ID.
   * @param string $langcode
   *   The language code.
   *
   * @throws \Exception
   *   Throws an exception if there is a problem updating the database.
   */
  private function updateGroupContentAliases(string $broken_alias, string $new_alias, int|string $gid, string $langcode): void {
    $node_ids = $this->database->select('group_relationship_field_data', 'grfd')
      ->fields('grfd', ['entity_id'])
      ->condition('gid', $gid)
      ->condition('plugin_id', 'group_node%', 'LIKE')
      ->execute()
      ?->fetchCol();

    if (!$node_ids) {
      return;
    }

    $node_ids = array_unique($node_ids);

    $escaped_broken_alias = $this->database->escapeLike($broken_alias);
    foreach ($node_ids as $nid) {
      $affected_aliases = $this->database->select('path_alias', 'pa')
        ->fields('pa')
        ->condition('alias', $escaped_broken_alias . '/%', 'LIKE')
        ->condition('path', "/node/$nid")
        ->condition('langcode', $langcode)
        ->execute()
        ?->fetchAll(\PDO::FETCH_ASSOC);

      if (!$affected_aliases) {
        continue;
      }

      // Use transaction for data safety.
      $transaction = $this->database->startTransaction();
      try {
        foreach ($affected_aliases as $alias) {
          $fixed_alias = str_replace($broken_alias, $new_alias, $alias['alias']);

          if ($this->hasAliasConflict($fixed_alias, $langcode, path: $alias['path'])) {
            // Seems like we have another same alias. Skip it to avoid conflict.
            $this->registerReport('skipped', $alias['path']);
            continue;
          }

          foreach (static::ALIAS_TABLES as $table) {
            $this->database->update($table)
              ->fields([
                'alias' => $fixed_alias,
              ])
              ->condition('id', $alias['id'])
              ->condition('revision_id', $alias['revision_id'])
              ->condition('langcode', $langcode)
              ->execute();
          }

          $this->registerReport('fixed_suffixes', $alias['path']);
        }
      }
      catch (\Exception $e) {
        $transaction->rollBack();
        throw $e;
      }
    }
  }

  /**
   * Fixes group tab aliases that don't match the current canonical alias.
   *
   * This handles two scenarios:
   * 1. Aliases with /stream/ embedded (e.g., /group/slug/stream/about should
   *    be /group/slug/about).
   * 2. Aliases with stale prefixes (e.g., /group/slug-1/about when the
   *    canonical alias is /group/slug).
   *
   * For each group tab alias, the correct alias is computed from the current
   * canonical alias (the alias for /group/{id}/stream) plus the tab suffix
   * from the system path.
   *
   * @param int|string $gid
   *   The group ID to process.
   *
   * @throws \Exception
   *   Throws an exception if there is a problem updating the database.
   */
  public function fixGroupTabAliases(int|string $gid): void {
    $languages = $this->languageManager->getLanguages();

    foreach (array_keys($languages) as $langcode) {
      $this->fixGroupTabAliasesForLanguage($gid, $langcode);
    }
  }

  /**
   * Fixes group tab aliases for a specific group and language.
   *
   * @param int|string $gid
   *   The group ID.
   * @param string $langcode
   *   The language code.
   *
   * @throws \Exception
   */
  protected function fixGroupTabAliasesForLanguage(int|string $gid, string $langcode): void {
    // Get the current canonical alias for this group. Order by ID descending
    // to get the latest alias when multiple exist for the same path (e.g. after
    // a group title change that created a new alias).
    $canonical_query = $this->database->select('path_alias', 'pa')
      ->fields('pa', ['alias'])
      ->condition('path', "/group/$gid/stream")
      ->condition('langcode', $langcode)
      ->orderBy('id', 'DESC')
      ->range(0, 1);
    $canonical_alias = $canonical_query->execute()
      ?->fetchField();

    if (!$canonical_alias) {
      return;
    }

    // Find group tab aliases for this group. Only match paths with exactly
    // one segment after /group/{id}/ (e.g. /group/123/about) to avoid
    // touching admin or nested paths. Also exclude the stream path itself
    // since that's the canonical alias.
    $tab_aliases = $this->database->select('path_alias', 'pa')
      ->fields('pa')
      ->condition('path', "/group/$gid/%", 'LIKE')
      ->condition('path', "/group/$gid/%/%", 'NOT LIKE')
      ->condition('path', "/group/$gid/stream", '<>')
      ->condition('langcode', $langcode)
      ->execute()
      ?->fetchAll(\PDO::FETCH_ASSOC);

    if (!$tab_aliases) {
      return;
    }

    $transaction = $this->database->startTransaction();
    try {
      foreach ($tab_aliases as $alias_record) {
        // Extract the tab suffix from the system path.
        // E.g., /group/123/about → about.
        $system_path = $alias_record['path'];
        if (!preg_match("#^/group/$gid/(.+)$#", $system_path, $m)) {
          continue;
        }
        $tab_suffix = $m[1];

        // Compute the expected alias.
        $expected_alias = $canonical_alias . '/' . $tab_suffix;

        // Skip if the alias already matches.
        if ($alias_record['alias'] === $expected_alias) {
          continue;
        }

        // Check for conflicts.
        if ($this->hasAliasConflict($expected_alias, $langcode, alias_id: $alias_record['id'])) {
          $this->registerReport('skipped', $alias_record['alias']);
          continue;
        }

        foreach (static::ALIAS_TABLES as $table) {
          $this->database->update($table)
            ->fields(['alias' => $expected_alias])
            ->condition('id', $alias_record['id'])
            ->condition('revision_id', $alias_record['revision_id'])
            ->condition('langcode', $langcode)
            ->execute();
        }

        $this->registerReport('fixed_tab', $alias_record['alias'] . ' → ' . $expected_alias);
      }
    }
    catch (\Exception $e) {
      $transaction->rollBack();
      throw $e;
    }
  }

  /**
   * Retrieves a broken canonical alias for a group.
   *
   * This method checks both the group home and stream paths to find any
   * aliases
   * that end with unwanted suffixes (-0, -1, -2). It searches for broken
   * aliases in order of priority: first checking the 'home' path, then the
   * 'stream' path. Returns the first broken alias found, or FALSE if no broken
   * aliases exist.
   *
   * @param int|string $gid
   *   The group ID to check for broken aliases.
   * @param string $langcode
   *   The language code.
   *
   * @return false|string
   *   The broken alias string if one is found, FALSE otherwise.
   *
   * @throws \Exception
   */
  private function getBrokenCanonicalAlias(int|string $gid, string $langcode): false|string {
    $callback = function ($gid, $path, $langcode) {
      $query = $this->database->select('path_alias', 'pa')
        ->fields('pa')
        ->condition('langcode', $langcode)
        // Broken should be a group home alias.
        ->condition('path', "/group/$gid/$path");

      $record = $query->execute()?->fetchAssoc();

      // We are expecting only one broken alias.
      // We can't fix a few broken aliases for one path.
      $broken_alias = $record['alias'] ?? '';

      foreach (self::UNWANTED_SUFFIXES as $suffix) {
        if (str_ends_with($broken_alias, $suffix)) {
          return $broken_alias;
        }
      }

      return FALSE;
    };

    foreach (['home', 'stream'] as $path_suffix) {
      if ($broken_alias = $callback($gid, $path_suffix, $langcode)) {
        return $broken_alias;
      }
    }

    return FALSE;
  }

  /**
   * Removes a specified trailing substring from the end of a string.
   *
   * This method checks if the string ends with the specified substring
   * and removes it. If the string does not end with the given substring,
   * the original string is returned unchanged.
   *
   * @param string $string
   *   The input string to process.
   * @param string $replacement
   *   The trailing substring to remove from the input string.
   *
   * @return string
   *   The modified string with the trailing substring removed, or the
   *   original string if the trailing substring is not present.
   */
  private function strTrimEnd(string $string, string $replacement): string {
    // Strip the end characters only in case if the string ends
    // with exactly the same characters.
    if (!str_ends_with($string, $replacement)) {
      return $string;
    }

    return substr($string, 0, -strlen($replacement));
  }

}
