<?php

namespace OpenSocial\TestBridge\Bridge;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use OpenSocial\TestBridge\Attributes\Command;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class UserBridge {

  use EntityTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Create multiple user.
   *
   * @param array $users
   *   The user information that'll be passed to User::create().
   *
   * @return array{created: int[], errors: string[]}
   *   An array of IDs for the users successfully created and an array of
   *   errors for failures.
   */
  #[Command(name: "create-users")]
  public function createUsers(array $users) {
    $created = [];
    $errors = [];
    foreach ($users as $inputId => $user) {
      try {
        $user = $this->userCreate($user);
        $created[$inputId] = $user->id();
      }
      catch (\Exception $exception) {
        $errors[$inputId] = $exception->getMessage();
      }
    }

    return ['created' => $created, 'errors' => $errors];
  }

  /**
   * Delete/cancel user.
   *
   * $method options:
   *  user_cancel_block: disable user, leave content
   *  user_cancel_block_unpublish: disable user, unpublish content
   *  user_cancel_reassign: delete user, reassign content to uid=0
   *  user_cancel_delete: delete user, delete content
   */
  #[Command(name: 'cancel-user')]
  public function cancelUser(string $username, ?string $method = 'user_cancel_delete') : array {
    $account = user_load_by_name($username);
    if ($account === FALSE) {
      return ['status' => 'error', 'error' => sprintf("User with username '%s' does not exist.", $username)];
    }
    if ($account->id() === 0) {
      return ['status' => 'error', 'error' => "Can not delete the anonymous user."];
    }
    user_cancel([], $account->id(), $method);

    // user_cancel() initiates a batch process. Run it manually.
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();

    return ['status' => 'ok'];
  }

  /**
   * Create a user.
   *
   * @return \Drupal\user\Entity\User
   *   The user values.
   */
  private function userCreate($user) : User {
    // Create unblocked users unless explicitly marked as blocked.
    $user['status'] ??= 1;

    $this->validateEntityFields("user", $user);
    $user_object = User::create($user);
    $violations = $user_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The user you tried to create is invalid: $violations");
    }
    $user_object->save();

    return $user_object;
  }

}
