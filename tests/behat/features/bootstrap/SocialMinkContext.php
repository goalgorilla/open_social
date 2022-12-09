<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Drupal\DrupalExtension\Context\MinkContext;

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

}
