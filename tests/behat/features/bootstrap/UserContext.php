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
   * View the user registration page.
   *
   * @When /^(?:|I )am on the registration page$/
   */
  public function whenIViewTheUserRegistrationPage(): void {
    $this->visitPath(self::REGISTRATION_PAGE);
    $this->assertSession()->statusCodeEquals(200);
  }

}
