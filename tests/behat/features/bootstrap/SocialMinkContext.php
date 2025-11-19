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

    throw new \RuntimeException(sprintf('The heading "%s" was not found in the "%s" region on the page %s', $heading, $region, $this->getSession()->getCurrentUrl()));
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
      throw new \RuntimeException('No field found');
    }

    $this->getSession()->wait(1000);

    $choice = $inputField->getParent()->find('css', '.select2-selection');
    if (!$choice) {
      throw new \RuntimeException('No select2 choice found');
    }
    $choice->press();

    $select2Input = $inputField->getParent()->find('css', '.select2-search__field');
    if (!$select2Input) {
      $select2Input = $page->find('css', '.select2-search__field');
    }

    if (!$select2Input) {
      // Try to find an input globally on the page.
      throw new \RuntimeException('No input found');
    }

    $select2Input->setValue($value);

    $this->getSession()->wait(1000);

    $chosenResults = $page->findAll('css', '.select2-results__options li');
    foreach ($chosenResults as $result) {
      if ($result->getText() === $entry) {
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
      throw new \RuntimeException('No field found');
    }

    $this->getSession()->wait(1000);

    $clearButton = $inputField->find('css', '.select2-selection__clear');
    if (!$clearButton) {
      throw new \RuntimeException('No clear button found');
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
        throw new \RuntimeException(sprintf('The checkbox "%s" with id|name|label|value was not found', $checkbox));
      }

      throw new \RuntimeException(sprintf('The checkbox "%s" is not checked', $checkbox));
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
        throw new \RuntimeException(sprintf('The checkbox "%s" with id|name|label|value was not found', $checkbox));
      }

      throw new \RuntimeException(sprintf('The checkbox "%s" is checked', $checkbox));
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
      throw new \RuntimeException("Expected '$option' to be selected but it was not.");
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
      throw new \RuntimeException("The field was not supposed to contain '$option' but it was an option in the select field.");
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
      throw new \RuntimeException(sprintf('The text %s was not found %d time(s) in the text of the current page.', $text, $num));
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
        throw new \RuntimeException(sprintf(
          "The select field '%s' was not supposed to contain option '%s' but it did.",
          $locator,
          $value['options']
        ));
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
        throw new \RuntimeException("The field was supposed to contain '$option' but it was not an option in the select field.");
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
      throw new \RuntimeException('Found '.$numberText.' times of "'.$text.'" when expecting '.$expectedNumber);
    }
  }

  /**
   * Override iWaitForAjaxToFinish to handle redirects and VBO operations.
   * 
   * This override is more lenient than the parent implementation:
   * 1. Returns immediately if page content indicates navigation occurred.
   * 2. Handles VBO operations with button state checks.
   * 3. Uses a reduced timeout to fail faster on actual AJAX issues.
   */
  public function iWaitForAjaxToFinish($event = null) {
    $session = $this->getSession();
    $page = $session->getPage();
    
    // For clicks/submits that might redirect, check if navigation occurred.
    // Some redirects are so fast that by the time this hook runs,
    // we're already on the new page.
    if ($event) {
      $stepText = $event->getStep()->getText();
      // If this was a click/follow action, check if page navigated.
      if (preg_match('/\b(click|follow)\b/i', $stepText)) {
        // Give page a moment to stabilize after navigation.
        usleep(100000); // 100ms
        
        // Check if document indicates it just loaded.
        try {
          $justLoaded = $session->evaluateScript(<<<JS
            (function() {
              // If page just loaded, performance.timing will show recent load.
              if (typeof performance !== 'undefined' && performance.timing) {
                var loadTime = performance.timing.loadEventEnd;
                var now = Date.now();
                // If page loaded in last 2 seconds, likely a redirect.
                return loadTime > 0 && (now - loadTime) < 2000;
              }
              return false;
            })();
JS
          );
          
          if ($justLoaded) {
            // Page recently loaded - this was likely a redirect.
            // Wait for full readiness and return.
            $session->wait(2000, "document.readyState === 'complete'");
            return;
          }
        } catch (\Exception $e) {
          // Script evaluation failed, possibly navigating.
          usleep(500000);
          return;
        }
      }
    }
    
    // Check if we're dealing with VBO by looking for VBO forms.
    $hasVboForm = FALSE;
    try {
      $hasVboForm = $session->evaluateScript(<<<JS
        (function() {
          return document.querySelectorAll('.vbo-view-form').length > 0;
        })()
JS
      );
    } catch (\Exception $e) {
      // If we can't evaluate, assume no VBO form.
    }
    
    $condition = <<<JS
    (function() {
      try {
        function isAjaxing(instance) {
          return instance && instance.ajaxing === true;
        }
        var d7_not_ajaxing = true;
        if (typeof Drupal !== 'undefined' && typeof Drupal.ajax !== 'undefined' && typeof Drupal.ajax.instances === 'undefined') {
          for(var i in Drupal.ajax) { if (isAjaxing(Drupal.ajax[i])) { d7_not_ajaxing = false; } }
        }
        var d8_not_ajaxing = (typeof Drupal === 'undefined' || typeof Drupal.ajax === 'undefined' || typeof Drupal.ajax.instances === 'undefined' || !Drupal.ajax.instances.some(isAjaxing));
        
        // For VBO operations, check if the Actions button is enabled.
        // This indicates VBO AJAX completed.
        var vbo_complete = true;
        var vboForms = document.querySelectorAll('.vbo-view-form');
        if (vboForms.length > 0) {
          var actionsButton = document.querySelector('button[data-vbo="vbo-action"], input[data-vbo="vbo-action"]');
          if (actionsButton && actionsButton.disabled) {
            vbo_complete = false;
          }
        }
        
        return (
          // Assert no AJAX request is running and no animation is running.
          (typeof jQuery === 'undefined' || jQuery.hasOwnProperty('active') === false || (jQuery.active === 0 && jQuery(':animated').length === 0)) &&
          d7_not_ajaxing && d8_not_ajaxing && vbo_complete
        );
      } catch (e) {
        // If any error occurs, assume AJAX is not running.
        // This handles page navigation scenarios.
        return true;
      }
    }());
JS;
    $ajax_timeout = $this->getMinkParameter('ajax_timeout');
    
    // For VBO operations, use a shorter timeout with fallback check.
    $timeout = $hasVboForm ? min($ajax_timeout, 15) : $ajax_timeout;
    
    // Use standard wait with the condition.
    $result = $session->wait(1000 * $timeout, $condition);
    
    if (!$result) {
      // Wait timed out. Perform final checks before throwing exception.
      
      // Check if Actions button is enabled for VBO forms.
      if ($hasVboForm) {
        usleep(500000);
        $actionsButton = $page->findButton('Actions');
        if ($actionsButton && !$actionsButton->hasAttribute('disabled')) {
          // Button is enabled, VBO completed successfully - continue.
          return;
        }
      }
      
      if ($ajax_timeout === null) {
        throw new \Exception('No AJAX timeout has been defined. Please verify that "Drupal\MinkExtension" is configured in behat.yml (and not "Behat\MinkExtension").');
      }
      if ($event) {
        /** @var \Behat\Behat\Hook\Scope\BeforeStepScope $event */
        $event_data = ' ' . json_encode([
          'name' => $event->getName(),
          'feature' => $event->getFeature()->getTitle(),
          'step' => $event->getStep()->getText(),
          'suite' => $event->getSuite()->getName(),
        ]);
      } else {
        $event_data = '';
      }
      throw new \RuntimeException('Unable to complete AJAX request.' . $event_data);
    }
  }

}
