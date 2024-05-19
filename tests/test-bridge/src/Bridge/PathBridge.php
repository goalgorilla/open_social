<?php

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\path_alias\PathAliasInterface;
use OpenSocial\TestBridge\Attributes\Command;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class PathBridge {

  use EntityTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * Create multiple aliases.
   *
   * @param array $aliases
   *   The alias information that'll be passed to PathAlias::create().
   *
   * @return array{created: int[], errors: string[]}
   *   An array of IDs for the aliases successfully created and an array of
   *   errors for failures.
   */
  #[Command(name: "create-aliases")]
  public function createAliases(array $aliases) {
    $created = [];
    $errors = [];
    foreach ($aliases as $inputId => $alias) {
      try {
        $alias = $this->aliasCreate($alias);
        $created[$inputId] = $alias->id();
      }
      catch (\Exception $exception) {
        $errors[$inputId] = $exception->getMessage();
      }
    }

    return ['created' => $created, 'errors' => $errors];
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

    $path_alias['path'] = $this->entityTypeManager->getStorage($target_entity_type)
      ->load($entity_id)
      ->toUrl()
      ->toString();

    // @todo Possibly make this work for all commands?
    // Switch current user to admin to by-pass path access check for alias.
    $current_user = $this->currentUser->getAccount();
    $user_1 = $this->entityTypeManager->getStorage('user')->load(1);
    assert($user_1 !== NULL);
    $this->currentUser->setAccount($user_1);

    $this->validateEntityFields("path_alias", $path_alias);
    $path_alias_object = PathAlias::create($path_alias);
    $violations = $path_alias_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The alias you tried to create is invalid: $violations");
    }
    $path_alias_object->save();

    // Restore current user.
    $this->currentUser->setAccount($current_user);

    return $path_alias_object;
  }

}
