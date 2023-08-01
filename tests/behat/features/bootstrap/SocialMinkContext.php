<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\MinkContext;
use Behat\Gherkin\Node\TableNode;

/**
 * Defines application features from the specific context.
 */
class SocialMinkContext extends MinkContext {

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
   * @override MinkContext::assertCheckBox()
   */
  public function assertCheckBox($checkbox) {
    $this->getSession()->executeScript("
      var inputs = document.getElementsByTagName('input');
      for (var i = 0; i < inputs.length; i++) {
        inputs[i].style.opacity = 1;
        inputs[i].style.left = 0;
        inputs[i].style.position = 'relative';
      }
    ");

    parent::assertCheckBox($checkbox);
  }

  /**
   * @Given /^I make a screenshot with the name "([^"]*)"$/
   */
  public function iMakeAScreenshotWithFileName($filename) {
    $dir = __DIR__ . '/../../logs';
    if (is_writeable($dir)) {
      file_put_contents(
        "$dir/$filename.jpg",
        $this->getSession()->getScreenshot()
      );
    }
  }

  /**
   * @When /^(?:|I )fill in select2 input "(?P<field>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)" and select "(?P<entry>(?:[^"]|\\")*)"$/
   */
  public function iFillInSelectInputWithAndSelect($field, $value, $entry) {
    $page = $this->getSession()->getPage();

    $inputField = $page->find('css', $field);
    if (!$inputField) {
      throw new \Exception('No field found');
    }

    $this->getSession()->wait(1000);

    $choice = $inputField->getParent()->find('css', '.select2-selection');
    if (!$choice) {
      throw new \Exception('No select2 choice found');
    }
    $choice->press();

    $select2Input = $page->find('css', '.select2-search__field');
    if (!$select2Input) {
      throw new \Exception('No input found');
    }
    $select2Input->setValue($value);

    $this->getSession()->wait(1000);

    $chosenResults = $page->findAll('css', '.select2-results li');
    foreach ($chosenResults as $result) {
      if ($result->getText() == $entry) {
        $result->click();
        break;
      }
    }
  }


  /**
   * @When /^I clear group field$/
   */
  public function iClearGroupSelect2Input() {
    $page = $this->getSession()->getPage();

    $inputField = $page->find('css', '.field--name-groups .select2');
    if (!$inputField) {
      throw new \Exception('No field found');
    }

    $this->getSession()->wait(1000);

    $clearButton = $inputField->find('css', '.select2-selection__clear');
    if (!$clearButton) {
      throw new \Exception('No clear button found');
    }

    $clearButton->click();
  }

  /**
   * Attaches file to field with specified name.
   *
   * @When /^(?:|I )attach the file "(?P<path>[^"]*)" to hidden field "(?P<field>(?:[^"]|\\")*)"$/
   */
  public function attachFileToHiddenField($field, $path) {
    $field = $this->fixStepArgument($field);
    $id = $this->getSession()->getPage()->findField($field)->getAttribute('id');

    $javascript = "jQuery('#$id').parent().removeClass('hidden')";
    $this->getSession()->executeScript($javascript);

    $this->attachFileToField($field, $path);
  }

  /**
   * @Then I should see checked the box :checkbox
   *
   * @todo This doesn't actually check that the radio button is visible for the
   *   user, e.g. it may be hidden in a closed details element.
   */
  public function iShouldSeeCheckedTheBox($checkbox) {
    $checkbox = $this->fixStepArgument($checkbox);

    if (!$this->getSession()->getPage()->hasCheckedField($checkbox)) {
      $field = $this->getSession()->getPage()->findField($checkbox);

      if (null === $field) {
        throw new \Exception(sprintf('The checkbox "%s" with id|name|label|value was not found', $checkbox));
      }
      else {
        throw new \Exception(sprintf('The checkbox "%s" is not checked', $checkbox));
      }
    }
  }

  /**
   * @Then I should see unchecked the box :checkbox
   */
  public function iShouldSeeUncheckedTheBox($checkbox) {
    $checkbox = $this->fixStepArgument($checkbox);

    if (!$this->getSession()->getPage()->hasUncheckedField($checkbox)) {
      $field = $this->getSession()->getPage()->findField($checkbox);

      if (null === $field) {
        throw new \Exception(sprintf('The checkbox "%s" with id|name|label|value was not found', $checkbox));
      }
      else {
        throw new \Exception(sprintf('The checkbox "%s" is checked', $checkbox));
      }
    }
  }

  /**
   * Set alias field as specified value
   * Example: When I set alias as: "bwayne"
   *
   * @When /^(?:|I )set alias as "(?P<value>(?:[^"]|\\")*)"$/
   */
  public function iSetAlias($value) {
    // Uncheck "Generate automatic URL alias" if social_path_manager is enabled.
    if (\Drupal::service('module_handler')->moduleExists('social_path_manager')) {
      $option = $this->fixStepArgument('Generate automatic URL alias');
      $this->getSession()->getPage()->uncheckField($option);
    }
    // Fill in "URL alias" field with given value
    $field = $this->fixStepArgument('path[0][alias]');
    $value = $this->fixStepArgument($value);
    $this->getSession()->getPage()->fillField($field, $value);
  }

  /**
   * Ensure a specific option is selected in a select field.
   *
   * @Then I should see :option selected in the :locator select field
   * @Then should see :option selected in the :locator select field
   */
  public function thenShouldSeeOptionSelected(string $option, string $locator) : void {
    $field = $this->getSession()->getPage()->findField($locator);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value', $locator);
    }

    $opt = $field->find('named', ['option', $option]);

    if (NULL === $opt) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'select option', 'value|text', $option);
    }

    if (!$opt->isSelected()) {
      throw new \Exception("Expected '$option' to be selected but it was not.");
    }
  }

  /**
   * Ensure a select field does not contain an option.
   *
   * @Then I should not see :option in the :locator select field
   * @Then should not see :option in the :locator select field
   */
  public function thenShouldNotSeeOptionInTheSelectField(string $option, string $locator) : void {
    $field = $this->getSession()->getPage()->findField($locator);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value', $locator);
    }

    $opt = $field->find('named', ['option', $option]);

    if ($opt !== NULL) {
      throw new \Exception("The field was not supposed to contain '$option' but it was an option in the select field.");
    }
  }

  /**
   * Checks, that (?P<num>\d+) text exist in a selector on the page
   * Example: Then I should see "text" 5 times in ".teaser__title"
   * Example: And I should see "text" 1 time in "h4"
   *
   * @Then /^(?:|I )should see "(?P<text>(?:[^"]|\\")*)" (?P<num>\d+) time(s?) in "(?P<selector>(?:[^"]|\\")*)"$/
   */
  public function assertNumTextCss($num, $text, $selector) {
    $session = $this->getSession();
    $elements = $session->getPage()->findAll('css', $selector);
    $regex = '/' . preg_quote($text, '/') . '/ui';

    $count = 0;
    foreach ($elements as $element) {
      $element_text = $element->getText();
      $actual = preg_replace('/\s+/u', ' ', $element_text);
      preg_match($regex, $actual, $matches);

      $count += count($matches);
    }

    if ($count !== (int) $num) {
      throw new \Exception(sprintf('The text %s was not found %d time(s) in the text of the current page.', $text, $num));
    }

    return TRUE;
  }

  /**
   * Ensure a select field does not contain the following options.
   *
   * @Given /^the "(?P<locator>[^"]+)" select field should not contain the following options:$/
   */
  public function theSelectFieldShouldNotContainTheFollowingOptions(string $locator, TableNode $options): void {
    $field = $this->getSession()->getPage()->findField($locator);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value', $locator);
    }

    foreach ($options->getHash() as $value) {
      $option = $field->find('named', ['option', $value['options']]);

      if ($option !== NULL) {
        throw new \Exception("The field was supposed to not contain '$option' but it was an option in the select field.");
      }
    }
  }

  /**
   * Ensure a select field does contain the following options.
   *
   * @Given /^the "(?P<locator>[^"]+)" select field should contain the following options:$/
   */
  public function theSelectFieldShouldContainTheFollowingOptions(string $locator, TableNode $options): void {
    $field = $this->getSession()->getPage()->findField($locator);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value', $locator);
    }

    foreach ($options->getHash() as $value) {
      $option = $field->find('named', ['option', $value['options']]);

      if ($option === NULL) {
        throw new \Exception("The field was supposed to contain '$option' but it was not an option in the select field.");
      }
    }
  }

  /**
   * @Then /^I should see "([^"]*)" exactly "([^"]*)" times$/
   */
  public function iShouldSeeTheTextCertainNumberTimes($text, $expectedNumber): void {
    $allText = $this->getSession()->getPage()->getText();
    $numberText = substr_count($allText, $text);
    if ($expectedNumber != $numberText) {
      throw new \Exception('Found '.$numberText.' times of "'.$text.'" when expecting '.$expectedNumber);
    }
  }
}
