<?php

namespace Drupal\social\Behat;

use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\MessageContext;

/**
 * Provides step-definitions for interacting with Drupal messages.
 */
class SocialMessageContext extends MessageContext {

  use AvoidCleanupTrait;

  /**
   * Checks if the current page contains the given success message.
   *
   * @param string $message
   *   The text to be checked.
   * @param string $region
   *   The region.
   *
   * @Then I should see the success message( containing) :message in the :region( region)
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function assertRegionSuccessMessage(string $message, string $region): void {
    $this->assertRegion(
      $message,
      'success_message_selector',
      "The page '%s' does not contain any success messages",
      "The page '%s' does not contain the success message '%s'",
      $region
    );
  }

  /**
   * Internal callback to check for a specific message in a given context.
   *
   * @param string $message
   *   The message to be checked.
   * @param string $selectorId
   *   CSS selector name.
   * @param string $exceptionMsgNone
   *   The message being thrown when no message is contained, string
   *   should contain one '%s' as a placeholder for the current URL.
   * @param string $exceptionMsgMissing
   *   The message being thrown when the message is not contained, string should
   *   contain two '%s' as placeholders for the current URL and the message.
   * @param string $region
   *   The region.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Exception
   */
  private function assertRegion(string $message, string $selectorId, string $exceptionMsgNone, string $exceptionMsgMissing, string $region): void {
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);

    $selector = $this->getDrupalSelector($selectorId);
    $selectorObjects = $regionObj->findAll('css', $selector);

    if (empty($selectorObjects)) {
      throw new ExpectationException(sprintf($exceptionMsgNone, $session->getCurrentUrl()), $session->getDriver());
    }

    foreach ($selectorObjects as $selectorObject) {
      if (str_contains(trim($selectorObject->getText()), $message)) {
        return;
      }
    }

    throw new ExpectationException(sprintf($exceptionMsgMissing, $session->getCurrentUrl(), $message), $session->getDriver());
  }

}
