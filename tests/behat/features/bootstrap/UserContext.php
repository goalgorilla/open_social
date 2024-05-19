<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around users and user management.
 */
class UserContext extends RawMinkContext {

  /**
   * The test bridge that allows running code in the Drupal installation.
   */
  private TestBridgeContext $testBridge;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->testBridge = $environment->getContext(TestBridgeContext::class);
  }

  private const REGISTRATION_PAGE = "/user/register";

  /**
   * Delete/cancel user.
   *
   * $method options:
   *  user_cancel_block: disable user, leave content
   *  user_cancel_block_unpublish: disable user, unpublish content
   *  user_cancel_reassign: delete user, reassign content to uid=0
   *  user_cancel_delete: delete user, delete content.
   *
   * @When I delete user :username
   * @When I delete user :username with method :method
   */
  public function cancelUser($username, $method = 'user_cancel_delete') {
    $response = $this->testBridge->command(
      'cancel-user',
      username: $username,
      method: $method,
    );
    assert(!isset($response['error']), $response['error']);
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

}
