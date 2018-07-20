<?php

/**
 * @file
 * Add support for Bigpipe in Behat tests.
 *
 * Original PR here:
 * https://github.com/jhedstrom/drupalextension/pull/325
 */

use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\DrupalExtension\Context\RawDrupalContext;

/**
 * Big Pipe context.
 */
class BigPipeContext extends RawDrupalContext {

  /**
   * Prepares Big Pipe NOJS cookie if needed.
   *
   * @BeforeScenario
   */
  public function prepareBigPipeNoJsCookie() {
    try {
      // Check if JavaScript can be executed by Driver.
      $this->getSession()->getDriver()->executeScript('true');
    }
    catch (UnsupportedDriverActionException $e) {
      // Set NOJS cookie.
      $this
        ->getSession()
        ->setCookie(BigPipeStrategy::NOJS_COOKIE, TRUE);
    }
    catch (\Exception $e) {
      // Mute exceptions.
    }
  }

}
