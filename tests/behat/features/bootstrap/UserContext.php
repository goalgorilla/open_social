<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around users and user management.
 */
class UserContext extends RawMinkContext {

  private const REGISTRATION_PAGE = "/user/register";

  /**
   * Delete/cancel user.
   *
   * $method options:
   *  user_cancel_block: disable user, leave content
   *  user_cancel_block_unpublish: disable user, unpublish content
   *  user_cancel_reassign: delete user, reassign content to uid=0
   *  user_cancel_delete: delete user, delete content
   *
   * @When I delete user :username
   * @When I delete user :username with method :method
   */
  public function cancelUser($username, $method = 'user_cancel_delete') {
    $uid = $this->userLoadByName($username);
    user_cancel([], $uid, $method);

    // user_cancel() initiates a batch process. Run it manually.
    $batch =& batch_get();
    $batch['progressive'] = FALSE;
    batch_process();
  }

  /**
   * View the user registration page.
   *
   * @When /^(?:|I )am on the registration page$/
   */
  public function whenIViewTheUserRegistrationPage(): void {
    $this->visitPath(self::REGISTRATION_PAGE);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Get user id from username.
   *
   * Throws an error if user id with given username does not exist.
   *
   * @param string $username
   *  Username string
   * @return mixed
   *  User account ID.
   * @throws \Exception
   */
  private function userLoadByName(string $username) {
    $account = user_load_by_name($username);
    if ($account->id() !== 0) {
      return $account->id();
    }
    else {
      throw new \Exception(sprintf("User with username '%s' does not exist.", $username));
    }
  }

}
