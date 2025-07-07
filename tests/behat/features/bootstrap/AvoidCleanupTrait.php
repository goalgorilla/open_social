<?php

namespace Drupal\social\Behat;

/**
 * A trait that can be used to avoid cleanup from RawDrupalContext.
 *
 * The ideal way is to avoid extending contexts from the DrupalExtension but
 * sometimes that's unavoidable in which case this trait can be used to ensure
 * the clean methods on RawDrupalContext don't run after a scenario.
 */
trait AvoidCleanupTrait {

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanUsers() : void {
    // The database is cleared for us automatically, but we must empty the PHP
    // array that tracks the objects to ensure our Behat process doesn't leak
    // memory while running multiple scenarios.
    $this->userManager->clearUsers();
  }

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanNodes() : void {
    // The database is cleared for us automatically, but we must empty the PHP
    // array that tracks the objects to ensure our Behat process doesn't leak
    // memory while running multiple scenarios.
    $this->nodes = [];
  }

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanLanguages() : void {
    // The database is cleared for us automatically, but we must empty the PHP
    // array that tracks the objects to ensure our Behat process doesn't leak
    // memory while running multiple scenarios.
    $this->languages = [];
  }

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanRoles() : void {
    // The database is cleared for us automatically, but we must empty the PHP
    // array that tracks the objects to ensure our Behat process doesn't leak
    // memory while running multiple scenarios.
    $this->roles = [];
  }

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanTerms() : void {
    // The database is cleared for us automatically, but we must empty the PHP
    // array that tracks the objects to ensure our Behat process doesn't leak
    // memory while running multiple scenarios.
    $this->terms = [];
  }

}
