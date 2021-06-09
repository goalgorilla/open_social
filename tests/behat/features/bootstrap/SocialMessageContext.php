<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\MessageContext;

/**
 * Provides step-definitions for interacting with Drupal messages.
 */
class SocialMessageContext extends MessageContext {

  /**
   * Checks if the current page contains the given success message
   *
   * @param $message
   *   string The text to be checked
   * @param $region
   *   string The region
   *
   * @Then I should see the success message( containing) :message in the :region( region)
   */
  public function assertRegionSuccessMessage($message, $region) {
    $this->_assertRegion(
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
   * @param $message
   *   string The message to be checked
   * @param $selectorId
   *   string CSS selector name
   * @param $exceptionMsgNone
   *   string The message being thrown when no message is contained, string
   *   should contain one '%s' as a placeholder for the current URL
   * @param $exceptionMsgMissing
   *   string The message being thrown when the message is not contained, string
   *   should contain two '%s' as placeholders for the current URL and the message.
   * @param $region
   *   string The region
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when the expected message is not present in the page.
   */
  private function _assertRegion($message, $selectorId, $exceptionMsgNone, $exceptionMsgMissing, $region) {
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);

    $selector = $this->getDrupalSelector($selectorId);
    $selectorObjects = $regionObj->findAll('css', $selector);

    if (empty($selectorObjects)) {
      throw new ExpectationException(sprintf($exceptionMsgNone, $session->getCurrentUrl()), $session->getDriver());
    }

    foreach ($selectorObjects as $selectorObject) {
      if (strpos(trim($selectorObject->getText()), $message) !== FALSE) {
        return;
      }
    }

    throw new ExpectationException(sprintf($exceptionMsgMissing, $session->getCurrentUrl(), $message), $session->getDriver());
  }

}
