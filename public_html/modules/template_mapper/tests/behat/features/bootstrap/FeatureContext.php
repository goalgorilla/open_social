<?php

/**
 * @file
 * Contains custom definitions for Template Mapper.
 */

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * @Given the theme :arg1 is enabled
   *
   * @todo, this method should be moved to the general Drupal Extension.
   */
  public function theThemeIsEnabled($arg1) {
    // @todo, error handling.
    \Drupal::service('theme_handler')->install(array($arg1));
  }

  /**
   * @Given the theme :arg1 is the active theme
   *
   * @todo, this method should be moved to the general Drupal Extension.
   */
  public function theThemeIsTheActiveTheme($arg1) {
    // @todo, error handling.
    \Drupal::theme()->setActiveTheme(\Drupal::service('theme.initialization')->initTheme($arg1));
  }
}
