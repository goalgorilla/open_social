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
  }

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanNodes() : void {
  }

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanLanguages() : void {
  }

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanRoles() : void {
  }

  /**
   * Disable cleaning up to allow failed test inspection.
   */
  public function cleanTerms() : void {
  }

}
