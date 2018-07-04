<?php
// @codingStandardsIgnoreFile

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
    $dir = '/var/www/travis_artifacts';
    if (is_writeable($dir)) {
      $file_and_path = $dir . '/' . $filename . '.jpg';
      file_put_contents($file_and_path, $screenshot);
    }
  }


  /**
   * @AfterStep
   */
  public function takeScreenShotAfterFailedStep(AfterStepScope $scope)
  {
    if (99 === $scope->getTestResult()->getResultCode()) {
      $driver = $this->getSession()->getDriver();
      if (!($driver instanceof Selenium2Driver)) {
        return;
      }
      $feature = $scope->getFeature();
      $title = $feature->getTitle();

      $filename = date("Ymd-H_i_s");

      if (!empty($title)) {
        $filename .= '-' . str_replace(' ', '-', strtolower($title));
      }

      $filename .= '-error';

      $this->iMakeAScreenshotWithFileName($filename);
    }
  }


  /**
   * Attaches file to field with specified name.
   *
   * @When /^(?:|I )attach the file "(?P<path>[^"]*)" to hidden field "(?P<field>(?:[^"]|\\")*)"$/
   */
  public function attachFileToHiddenField($field, $path) {
    $field = $this->fixStepArgument($field);

    $javascript = "jQuery('#".$field."').parent().removeClass('hidden')";
    $this->getSession()->executeScript($javascript);

    $this->attachFileToField($field, $path);
  }
}
