<?php

use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalContext;
use Behat\MinkExtension\Context\RawMinkContext;
use PHPUnit_Framework_Assert as PHPUnit;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;
use Behat\Mink\Driver\Selenium2Driver;
use Behat\Behat\Hook\Scope\AfterStepScope;

/**
 * Defines application features from the specific context.
 */
class SocialMinkContext extends MinkContext{


  /**
   * @override MinkContext::assertRegionHeading()
   *
   * Makes the step case insensitive.
   */
  public function assertRegionHeading($heading, $region) {
    $regionObj = $this->getRegion($region);

    foreach (array('h1', 'h2', 'h3', 'h4', 'h5', 'h6') as $tag) {
      $elements = $regionObj->findAll('css', $tag);
      if (!empty($elements)) {
        foreach ($elements as $element) {
          if (trim(strtolower($element->getText())) === strtolower($heading)) {
            return;
          }
        }
      }
    }

    throw new \Exception(sprintf('The heading "%s" was not found in the "%s" region on the page %s', $heading, $region, $this->getSession()->getCurrentUrl()));
  }


  /**
   * @Given /^I make a screenshot$/
   */
  public function iMakeAScreenshot() {
    $this->iMakeAScreenshotWithFileName('screenshot');
  }

  /**
   * @Given /^I make a screenshot with the name "([^"]*)"$/
   */
  public function iMakeAScreenshotWithFileName($filename) {
    $screenshot = $this->getSession()->getDriver()->getScreenshot();
    $file_and_path = '/var/www/travis_artifacts/' . $filename . '.jpg';
    file_put_contents($file_and_path, $screenshot);
  }


  /**
   * @AfterStep
   */
  public function takeScreenShotAfterFailedStep(afterStepScope $scope)
  {
    if (99 === $scope->getTestResult()->getResultCode()) {
      $driver = $this->getSession()->getDriver();
      if (!($driver instanceof Selenium2Driver)) {
        return;
      }
      $today = date("H_i_s");
      $this->iMakeAScreenshotWithFileName($today . '-error');
    }
  }
}
