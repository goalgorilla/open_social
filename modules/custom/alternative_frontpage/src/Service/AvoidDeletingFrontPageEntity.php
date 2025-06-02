<?php

namespace Drupal\alternative_frontpage\Service;

use Drupal\alternative_frontpage\Entity\AlternativeFrontpage;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Service to check if an entity is set as an alternative front page.
 */
readonly class AvoidDeletingFrontPageEntity {

  public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    private AliasManagerInterface $pathAliasManager,
  ) {

  }

  /**
   * Check if the current path of the entity is set as an alternative frontend.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Delete entity to check alternative frontend.
   * @param string $type
   *   The type of entity to match against (e.g., 'group' or 'node').
   *
   * @return bool
   *   TRUE if the delete entity is set as an alternative frontend.
   */
  public function isEntitySetAsAlternativeFrontPage(EntityInterface $entity, string $type): bool {
    try {
      $storage = $this->entityTypeManager->getStorage('alternative_frontpage');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException) {
      return FALSE;
    }

    $allAlternativeFrontPages = $storage->loadMultiple();

    foreach ($allAlternativeFrontPages as $alternativeFrontPage) {
      assert($alternativeFrontPage instanceof AlternativeFrontpage);

      $entityId = $this->getEntityIdFromUrl((string) $alternativeFrontPage->get('path'), $type);
      if ((int) $entity->id() === (int) $entityId) {
        return TRUE;
      }

    }
    return FALSE;
  }

  /**
   * Extracts the group ID from a given URL.
   *
   * @param string $url
   *   The URL to extract the group ID from.
   * @param string $type
   *   The type of entity to match against (e.g., 'group' or 'node').
   *
   * @return int|null
   *   The group ID if found, or NULL if not found.
   */
  private function getEntityIdFromUrl(string $url, string $type): ?int {
    $path = (string) parse_url($url, PHP_URL_PATH);

    try {
      $internalPath = $this->pathAliasManager->getPathByAlias($path);

      // Check if the path matches the group pattern.
      if (preg_match("/$type\/(\d+)/", $internalPath, $matches)) {
        return (int) $matches[1];
      }
    }
    catch (\Exception) {
      // Fall back in case of error.
      // If (preg_match('/group\/(\d+)/', $path, $matches)) {.
      if (preg_match("/$type\/(\d+)/", $path, $matches)) {
        return (int) $matches[1];
      }
    }

    return NULL;
  }

}
