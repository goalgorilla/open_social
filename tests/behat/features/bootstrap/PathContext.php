<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\path_alias\PathAliasInterface;
use Drupal\user\Entity\User;

/**
 * Defines steps around paths and aliases.
 */
class PathContext extends RawMinkContext {

  use EntityTrait;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }

  /**
   * Create aliases for certain entities.
   *
   * ```
   * Given aliases:
   *   | target_type | target_label     | alias       |
   *   | node:page   | Our new homepage | /home       |
   *   | group       | My first group   | /best-group |
   * ```
   *
   * @Given aliases:
   */
  public function createComments(TableNode $aliasTable) : void {
    foreach ($aliasTable->getHash() as $aliasHash) {
      $this->aliasCreate($aliasHash);
    }
  }

  /**
   * Create an alias.
   *
   * @return \Drupal\path_alias\PathAliasInterface
   *   The created path alias.
   */
  private function aliasCreate(array $path_alias) : PathAliasInterface {
    if (!isset($path_alias['target_type'])) {
      throw new \Exception("You must specify a `target_type` when creating an alias. Provide either an entity_type_id such as `node` or an entity_type_id:bundle such as `node:topic`.");
    }
    if (!isset($path_alias['target_label'])) {
      throw new \Exception("You must specify a `target_label` when creating an alias, containing the title of the entity you're creating an alias for.");
    }

    // The NULL array ensures `target_entity_bundle` doesn't cause warnings if
    // the target type doesn't have a bundle specified.
    [$target_entity_type, $target_entity_bundle] = explode(":", $path_alias['target_type']) + [NULL, NULL];
    $entity_id = $this->getEntityIdFromLabel($target_entity_type, $target_entity_bundle, $path_alias['target_label']);
    assert($entity_id !== NULL, "Could not find $target_entity_type with label '{$path_alias['target_label']}'.");
    unset($path_alias['target_type'], $path_alias['target_label']);

    $path_alias['path'] = \Drupal::entityTypeManager()->getStorage($target_entity_type)
      ->load($entity_id)
      ->toUrl()
      ->toString();

    // Switch current user to admin to by-pass path access check for alias.
    $current_user = \Drupal::currentUser()->getAccount();
    \Drupal::currentUser()->setAccount(User::load(1));

    $this->validateEntityFields("path_alias", $path_alias);
    $path_alias_object = PathAlias::create($path_alias);
    $violations = $path_alias_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The alias you tried to create is invalid: $violations");
    }
    $path_alias_object->save();

    // Restore current user.
    \Drupal::currentUser()->setAccount($current_user);

    return $path_alias_object;
  }

  /**
   * Get an entity from its type and title.
   *
   * @param string $type
   *   The entity type to load.
   * @param string|null $bundle
   *   The bundle of the entity to limit results to or NULL to skip.
   * @param string $label
   *   The title of the entity.
   *
   * @return int|null
   *   The integer ID of the entity or NULL if no matching entity could be
   *   found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *    Thrown if the entity type doesn't exist.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *    Thrown if the storage handler couldn't be loaded.
   */
  private function getEntityIdFromLabel(string $type, ?string $bundle, string $label) : ?int {
    $storage = \Drupal::entityTypeManager()->getStorage($type);
    $entity_type = $storage->getEntityType();

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition($entity_type->getKey('label'), $label);

    if ($bundle !== NULL) {
      $query->condition($entity_type->getKey('bundle'), $bundle);
    }

    $entity_ids = $query->execute();

    if (count($entity_ids) !== 1) {
      return NULL;
    }

    return (int) reset($entity_ids);
  }

}
