<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Behat\Mink\Element\NodeElement;
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
   * Fills in an autocomplete input and selects an option.
   *
   * This step works with both Select2 and Svelte autocomplete multiselect
   * components. It automatically detects the component type and handles it
   * accordingly.
   *
   * The selector parameter can be:
   * - A CSS selector (e.g., ".field-example" or "input[role='combobox']")
   * - A label text (e.g., "Select Role") - will find the input via label's
   * 'for' attribute
   *
   * Example:
   *   And I fill in input ".field-example" with "Exam" and select "Example"
   *   And I fill in input "Select Role" with "Organization" and select
   *   "Organization Manager"
   *
   * @When /^(?:|I )fill in input "(?P<selector>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)" and select "(?P<entry>(?:[^"]|\\")*)"$/
   */
  public function iFillInInputWithAndSelect(string $selector, string $value, string $entry): void {
    $selector = $this->fixStepArgument($selector);
    $value = $this->fixStepArgument($value);
    $entry = $this->fixStepArgument($entry);

    // Find the input field by selector or label.
    $inputField = $this->findInputField($selector);

    // Detect component type and handle accordingly.
    $componentType = $this->detectAutocompleteType($inputField);

    if ($componentType === 'select2') {
      $this->handleSelect2Autocomplete($inputField, $value, $entry);
    } elseif ($componentType === 'svelte') {
      $this->handleSvelteAutocomplete($inputField, $value, $entry);
    } else {
      throw new \RuntimeException(sprintf(
        'Could not determine autocomplete type for selector or label "%s". Expected either Select2 (.select2-selection) or Svelte autocomplete multiselect (.autocomplete-multiselect-container)',
        $selector
      ));
    }
  }

  /**
   * Fills an autocomplete field and confirms specified options are unavailable.
   *
   * This step works with Svelte autocomplete multiselect components.
   * It verifies that the specified options are not available in the dropdown
   * after filtering.
   *
   * The selector parameter can be:
   * - A CSS selector (e.g., ".field-example" or "input[role='combobox']")
   * - A label text (e.g., "Select Role") - will find the input via label's
   *   'for' attribute
   *
   * Example:
   *   And I fill in input "Select Role" with "Organization" and I can not select:
   *     | Anonymous |
   *     | Administrator |
   *
   * @When /^(?:|I )fill in input "(?P<selector>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)" and I can not select:$/
   */
  public function iFillInInputWithAndICannotSelect(string $selector, string $value, TableNode $options): void {
    $selector = $this->fixStepArgument($selector);
    $value = $this->fixStepArgument($value);

    // Find the input field by selector or label.
    $inputField = $this->findInputField($selector);

    // Detect component type.
    $componentType = $this->detectAutocompleteType($inputField);

    if ($componentType === 'select2') {
      throw new \RuntimeException('This step is not yet implemented for Select2 components. Use Svelte autocomplete multiselect or implement it for select2.');
    } elseif ($componentType === 'svelte') {
      $this->handleSvelteAutocompleteCannotSelect($inputField, $value, $options);
    } else {
      throw new \RuntimeException(sprintf(
        'Could not determine autocomplete type for selector or label "%s". Expected Svelte autocomplete multiselect (.autocomplete-multiselect-container)',
        $selector
      ));
    }
  }

  /**
   * Finds an input field by CSS selector or label text.
   *
   * @param string $selector
   *   CSS selector or label text.
   *
   * @return NodeElement
   *   The input element.
   *
   * @throws \RuntimeException
   *   If the input field cannot be found.
   */
  private function findInputField(string $selector): NodeElement {
    $page = $this->getSession()->getPage();

    // Try to find the input field by CSS selector first.
    $inputField = $page->find('css', $selector);

    // If not found by CSS selector, try to find by label text.
    if (!$inputField) {
      // Look for a label with the exact text (using normalize-space to handle whitespace).
      $label = $page->find('xpath', sprintf('//label[normalize-space(text())=%s]', $this->getSession()->getSelectorsHandler()->xpathLiteral($selector)));

      if ($label) {
        // Get the 'for' attribute to find the associated input.
        $forAttribute = $label->getAttribute('for');
        if ($forAttribute) {
          $inputField = $page->findById($forAttribute);
        }
      }

      // If still not found, try partial label text match (using normalize-space).
      if (!$inputField) {
        $label = $page->find('xpath', sprintf('//label[contains(normalize-space(text()), %s)]', $this->getSession()->getSelectorsHandler()->xpathLiteral($selector)));
        if ($label) {
          $forAttribute = $label->getAttribute('for');
          if ($forAttribute) {
            $inputField = $page->findById($forAttribute);
          }
        }
      }
    }

    if (!$inputField) {
      throw new \RuntimeException(sprintf('No field found with selector or label "%s"', $selector));
    }

    return $inputField;
  }

  /**
   * Detects the type of autocomplete component.
   *
   * @param NodeElement $inputField
   *   The input element.
   *
   * @return string|null
   *   The component type: 'select2', 'svelte', or null if unknown.
   */
  private function detectAutocompleteType(NodeElement $inputField): ?string {
    // Check for Select2 first.
    $select2Selection = $inputField->getParent()->find('css', '.select2-selection');
    if ($select2Selection) {
      return 'select2';
    }

    // Check for Svelte autocomplete multiselect component.
    $container = $inputField->getParent()->find('css', '.autocomplete-multiselect-container');
    if (!$container) {
      // Try to find container by traversing up the DOM tree.
      $parent = $inputField->getParent();
      while ($parent && $parent->getTagName() !== 'body') {
        $container = $parent->find('css', '.autocomplete-multiselect-container');
        if ($container) {
          break;
        }
        $parent = $parent->getParent();
      }
    }

    if ($container) {
      return 'svelte';
    }

    return null;
  }

  /**
   * Handles Select2 autocomplete selection.
   *
   * @todo When there are multiple select2 fields on the page (single vs
   *   multiple), this method should use '.select2-container--open
   *   .select2-search__field' to find the correct input, similar to the removed
   *   iFillInSelectInputWithAndSelect method. This
   *   ensures we find the correct open select2 dropdown when multiple select2
   *   fields exist.
   *
   * @param NodeElement $inputField
   *   The input element.
   * @param string $value
   *   The search value to type.
   * @param string $entry
   *   The option text to select.
   *
   * @throws \RuntimeException
   *   If the Select2 component cannot be interacted with or option not found.
   */
  private function handleSelect2Autocomplete(NodeElement $inputField, string $value, string $entry): void {
    $page = $this->getSession()->getPage();

    $this->getSession()->wait(1000);

    // Open the Select2 dropdown.
    $select2Selection = $inputField->getParent()->find('css', '.select2-selection');
    if (!$select2Selection) {
      throw new \RuntimeException('No select2 choice found');
    }
    $select2Selection->press();

    // Find the search input.
    $select2Input = $inputField->getParent()->find('css', '.select2-search__field');
    if (!$select2Input) {
      $select2Input = $page->find('css', '.select2-search__field');
    }

    if (!$select2Input) {
      // Try to find an input globally on the page.
      throw new \RuntimeException('No input found');
    }

    // Type the search value.
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
   * Handles Svelte autocomplete multiselect selection.
   *
   * @param NodeElement $inputField
   *   The input element.
   * @param string $value
   *   The search value to type.
   * @param string $entry
   *   The option text to select.
   *
   * @throws \RuntimeException
   *   If the Svelte component cannot be interacted with or option not found.
   */
  private function handleSvelteAutocomplete(NodeElement $inputField, string $value, string $entry): void {
    // Find the container.
    $container = $inputField->getParent()->find('css', '.autocomplete-multiselect-container');
    if (!$container) {
      // Try to find container by traversing up the DOM tree.
      $parent = $inputField->getParent();
      while ($parent && $parent->getTagName() !== 'body') {
        $container = $parent->find('css', '.autocomplete-multiselect-container');
        if ($container) {
          break;
        }
        $parent = $parent->getParent();
      }
    }

    if (!$container) {
      throw new \RuntimeException('Svelte autocomplete multiselect container not found');
    }

    // Find the text input (can be by role="combobox" or type="text").
    $input = $container->find('css', 'input[type="text"]');
    if (!$input) {
      // Fallback: try combobox role.
      $input = $container->find('css', 'input[role="combobox"]');
    }
    if (!$input) {
      // Fallback: use the original input if it's a text input.
      if ($inputField->getAttribute('type') === 'text' || $inputField->getAttribute('role') === 'combobox') {
        $input = $inputField;
      } else {
        throw new \RuntimeException('Could not find text input in Svelte autocomplete multiselect');
      }
    }

    // Get input ID for JavaScript operations.
    $inputId = $input->getAttribute('id');
    if (!$inputId) {
      throw new \RuntimeException('Input element does not have an ID attribute');
    }

    // Check if dropdown is already open.
    $dropdown = $container->find('css', '.dropdown-list');
    $isAlreadyOpen = $dropdown !== null;

    // Open dropdown by focusing the input (Svelte component uses onfocus handler).
    if (!$isAlreadyOpen) {
      // Use JavaScript to focus and trigger focus event.
      // Wrap in IIFE to avoid variable name conflicts.
      $this->getSession()->executeScript(sprintf(
        '(function() { const inputEl = document.getElementById("%s"); if (inputEl) { inputEl.focus(); inputEl.dispatchEvent(new Event("focus", { bubbles: true, cancelable: true })); } })();',
        $inputId
      ));
    }

    // Type the search term to filter options (if value is provided).
    if (!empty($value)) {
      // Set value and trigger input event via JavaScript to ensure Svelte reactivity.
      // Wrap in IIFE to avoid variable name conflicts.
      $this->getSession()->executeScript(sprintf(
        '(function() { const inputEl = document.getElementById("%s"); if (inputEl) { inputEl.value = %s; inputEl.dispatchEvent(new Event("input", { bubbles: true, cancelable: true })); } })();',
        $inputId,
        json_encode($value)
      ));
    }

    // Wait for dropdown to appear with options.
    // Options are preloaded and filtering is synchronous via Svelte's reactive
    // system. Use a minimal wait (50ms) just to allow DOM rendering, but it
    // will return immediately if condition is already true.
    $this->getSession()->wait(50, sprintf(
      '(() => {
        const container = document.querySelector(".autocomplete-multiselect-container");
        if (!container) return false;
        const dropdown = container.querySelector(".dropdown-list");
        if (!dropdown) return false;
        const options = dropdown.querySelectorAll("li[role=\"option\"]");
        return options.length > 0;
      })()'
    ));

    // Find the dropdown list.
    $dropdown = $container->find('css', '.dropdown-list');
    if (!$dropdown) {
      throw new \RuntimeException(sprintf('Dropdown list did not appear for value "%s" in Svelte autocomplete multiselect. The value may not be available.', $value));
    }

    // Find the option using XPath with normalize-space for exact text match.
    // This matches the working implementation pattern.
    $option = $dropdown->find('xpath', sprintf('.//li[normalize-space(text())=%s]', $this->getSession()->getSelectorsHandler()->xpathLiteral($entry)));

    if (!$option) {
      // If not found, try to find by partial match or check all available options for debugging.
      $allOptions = $dropdown->findAll('css', 'li[role="option"]');
      $availableOptions = array_map(fn($opt) => trim($opt->getText()), $allOptions);
      throw new \RuntimeException(sprintf(
        'Option "%s" was not found in the dropdown list. Available options: [%s]. If it\'s valid, verify that it has not already been selected.',
        $entry,
        implode(', ', $availableOptions)
      ));
    }

    // Get the option ID to trigger mousedown event (component uses onmousedown handler).
    $optionId = $option->getAttribute('id');

    if ($optionId) {
      // Use JavaScript to trigger mousedown event directly (component expects mousedown).
      $this->getSession()->executeScript(sprintf(
        '(function() { const optionEl = document.getElementById("%s"); if (optionEl) { optionEl.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true })); } })();',
        $optionId
      ));
    } else {
      // Fallback: try regular click if no ID.
      $option->click();
    }

    // Verify the option was selected by checking for the pill.
    $pills = $container->findAll('css', '.selected-pill span');
    $selectedValues = array_map(fn($pill) => trim($pill->getText()), $pills);

    if (!in_array($entry, $selectedValues, TRUE)) {
      throw new \RuntimeException(sprintf(
        'Option "%s" was clicked but was not added to selected pills. Selected values: [%s]',
        $entry,
        implode(', ', $selectedValues)
      ));
    }

    // Unfocus any active element to close dropdowns and trigger blur events.
    $this->getSession()->executeScript("if (document.activeElement) document.activeElement.blur();");
  }

  /**
   * Handles Svelte autocomplete multiselect verification that options cannot be selected.
   *
   * @param NodeElement $inputField
   *   The input element.
   * @param string $value
   *   The search value to type.
   * @param \Behat\Gherkin\Node\TableNode $options
   *   Table of options that should NOT be available.
   *
   * @throws \RuntimeException
   *   If any of the specified options are found in the dropdown.
   */
  private function handleSvelteAutocompleteCannotSelect(NodeElement $inputField, string $value, TableNode $options): void {
    // Find the container.
    $container = $inputField->getParent()->find('css', '.autocomplete-multiselect-container');
    if (!$container) {
      // Try to find container by traversing up the DOM tree.
      $parent = $inputField->getParent();
      while ($parent && $parent->getTagName() !== 'body') {
        $container = $parent->find('css', '.autocomplete-multiselect-container');
        if ($container) {
          break;
        }
        $parent = $parent->getParent();
      }
    }

    if (!$container) {
      throw new \RuntimeException('Svelte autocomplete multiselect container not found');
    }

    // Find the text input (can be by role="combobox" or type="text").
    $input = $container->find('css', 'input[type="text"]');
    if (!$input) {
      // Fallback: try combobox role.
      $input = $container->find('css', 'input[role="combobox"]');
    }
    if (!$input) {
      // Fallback: use the original input if it's a text input.
      if ($inputField->getAttribute('type') === 'text' || $inputField->getAttribute('role') === 'combobox') {
        $input = $inputField;
      } else {
        throw new \RuntimeException('Could not find text input in Svelte autocomplete multiselect');
      }
    }

    // Get input ID for JavaScript operations.
    $inputId = $input->getAttribute('id');
    if (!$inputId) {
      throw new \RuntimeException('Input element does not have an ID attribute');
    }

    // Check if dropdown is already open.
    $dropdown = $container->find('css', '.dropdown-list');
    $isAlreadyOpen = $dropdown !== null;

    // Open dropdown by focusing the input (Svelte component uses onfocus handler).
    if (!$isAlreadyOpen) {
      // Use JavaScript to focus and trigger focus event.
      // Wrap in IIFE to avoid variable name conflicts.
      $this->getSession()->executeScript(sprintf(
        '(function() { const inputEl = document.getElementById("%s"); if (inputEl) { inputEl.focus(); inputEl.dispatchEvent(new Event("focus", { bubbles: true, cancelable: true })); } })();',
        $inputId
      ));
    }

    // Type the search term to filter options.
    if (!empty($value)) {
      // Set value and trigger input event via JavaScript to ensure Svelte reactivity.
      // Wrap in IIFE to avoid variable name conflicts.
      $this->getSession()->executeScript(sprintf(
        '(function() { const inputEl = document.getElementById("%s"); if (inputEl) { inputEl.value = %s; inputEl.dispatchEvent(new Event("input", { bubbles: true, cancelable: true })); } })();',
        $inputId,
        json_encode($value)
      ));
    }

    // Wait for dropdown to appear with options.
    // Options are preloaded and filtering is synchronous via Svelte's reactive
    // system Use a minimal wait (50ms) just to allow DOM rendering, but it will
    // return immediately if condition is already true.
    $this->getSession()->wait(50, sprintf(
      '(() => {
        const container = document.querySelector(".autocomplete-multiselect-container");
        if (!container) return false;
        const dropdown = container.querySelector(".dropdown-list");
        if (!dropdown) return false;
        const options = dropdown.querySelectorAll("li[role=\\"option\\"]");
        return options.length > 0;
      })()'
    ));

    // Find the dropdown list.
    $dropdown = $container->find('css', '.dropdown-list');
    if (!$dropdown) {
      // If dropdown doesn't appear, that's fine - it means no options match, so all specified options are not available.
      return;
    }

    // Get all available options in the dropdown.
    $allOptions = $dropdown->findAll('css', 'li[role="option"]');
    $availableOptionTexts = array_map(fn($opt) => trim($opt->getText()), $allOptions);

    // Check each option from the table to ensure it's NOT available.
    foreach ($options->getHash() as $row) {
      // Get the option text from the first column.
      $optionText = trim(reset($row));
      if (empty($optionText)) {
        continue;
      }

      // Check if this option is available in the dropdown.
      foreach ($availableOptionTexts as $availableText) {
        // Use normalize-space for comparison (handles whitespace differences).
        if (trim($availableText) === $optionText) {
          throw new \RuntimeException(sprintf(
            'Option "%s" was found in the dropdown but should not be available. Available options: [%s]',
            $optionText,
            implode(', ', $availableOptionTexts)
          ));
        }
      }
    }

    // Unfocus any active element to close dropdowns and trigger blur events.
    $this->getSession()->executeScript("if (document.activeElement) document.activeElement.blur();");
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

  /**
   * @override MinkContext::pressButton()
   *
   * Overrides the default pressButton() to use JavaScript clicks for better
   * compatibility with JavaScript frameworks (like Svelte - but it can be
   * also extended to support other JavaScript frameworks).
   * This ensures that native browser click events are triggered, which allows
   * framework event handlers to fire properly.
   *
   * This override maintains backward compatibility - all existing tests using
   * "And I press the <button> button" will continue to work, but now also work
   * correctly with JavaScript framework components.
   *
   * @When /^(?:|I )press (?:the |)"([^"]*)" button$/
   * @param string $button
   *   Button id, value, or text.
   */
  public function pressButton($button) {
    $button = $this->fixStepArgument($button);
    $page = $this->getSession()->getPage();

    // Find the button element using Mink's findButton which searches by
    // id, value, or text content (same as parent implementation)
    $buttonElement = $page->findButton($button);

    if ($buttonElement === null) {
      // Fall back to parent implementation if button not found
      // This maintains the same error messages as before
      parent::pressButton($button);
      return;
    }

    // Check if button has JavaScript event handlers attached
    if ($this->hasJavaScriptHandlers($buttonElement)) {
      // Use JavaScript click to trigger proper browser events for Svelte/React/Vue
      try {
        $this->clickElementWithJavaScript($buttonElement);
        return;
      } catch (\Exception $e) {
        // If JavaScript click fails, fall back to parent implementation
        // This ensures backward compatibility
      }
    }

    // Use standard Mink click for buttons without JavaScript handlers
    parent::pressButton($button);
  }

  /**
   * Checks if an element has JavaScript event handlers attached.
   *
   * @param NodeElement $element
   *   The element to check.
   *
   * @return bool
   *   TRUE if the element has JavaScript handlers, FALSE otherwise.
   */
  private function hasJavaScriptHandlers($element): bool {
    // Check for onclick attribute
    if ($element->hasAttribute('onclick')) {
      return true;
    }

    // Check for Svelte classes (Svelte components have classes like "svelte-xxxxx")
    $class = $element->getAttribute('class');
    if ($class && preg_match('/\bsvelte-[\w-]+\b/', $class)) {
      return true;
    }

    // Check if element is inside a Svelte component container.
    try {
      $xpath = $element->getXpath();
      $hasSvelteParent = $this->getSession()->evaluateScript("
        (function() {
          var xpath = " . json_encode($xpath) . ";
          var element = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
          if (!element) return false;

          // Check if element or any parent has svelte-* class
          var current = element;
          while (current && current !== document.body) {
            if (current.className && typeof current.className === 'string' && /\bsvelte-[\w-]+\b/.test(current.className)) {
              return true;
            }
            current = current.parentElement;
          }

          // Check for React/Vue data attributes
          current = element;
          while (current && current !== document.body) {
            if (current.hasAttribute && (
              current.hasAttribute('data-reactroot') ||
              current.hasAttribute('data-v-') ||
              current.hasAttribute('__vue__')
            )) {
              return true;
            }
            current = current.parentElement;
          }

          // Check if element has event listeners (more expensive check)
          // This checks if there are any listeners on common event types
          var hasListeners = false;
          if (typeof getEventListeners === 'function') {
            ['click', 'mousedown', 'mouseup'].forEach(function(eventType) {
              try {
                if (getEventListeners(element)[eventType]) {
                  hasListeners = true;
                }
              } catch (e) {}
            });
          }

          return hasListeners;
        })()
      ");

      return (bool) $hasSvelteParent;
    } catch (\Exception $e) {
      // If we can't check, assume it might have handlers to be safe
      // This ensures we don't break existing functionality
      return false;
    }
  }

  /**
   * Helper method to click an element using JavaScript (for Svelte).
   *
   * @param NodeElement $element
   *   The element to click.
   */
  private function clickElementWithJavaScript($element): void {
    $xpath = $element->getXpath();
    $this->getSession()->evaluateScript("
      (function() {
        var xpath = " . json_encode($xpath) . ";
        var element = document.evaluate(xpath, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
        if (element) {
          element.click();
          return true;
        }
        return false;
      })()
    ");
  }

  /**
   * Checks that a radio button with the specified label is visible on the page.
   *
   * This step finds a radio button by its label text (handling visually-hidden
   * spans) and verifies it is visible. It works similarly to "I should see the button"
   * but specifically for radio buttons.
   *
   * Inspired by FeatureContext::clickRadioButton().
   *
   * @see \Drupal\social\Behat\FeatureContext::clickRadioButton()
   *
   * Example:
   *   And I should see the radio button "is not Role"
   *   And I should see the radio button "Membership Scope"
   *   And I should see the radio button "is not Role" with the id "relationship-role-exclude"
   *
   * @Then /^(?:|I )should see the radio button "(?P<label>(?:[^"]|\\")*)"(?: with the id "(?P<id>(?:[^"]|\\")*)")?$/
   */
  public function iShouldSeeTheRadioButton($label, $id = '') {
    $label = $this->fixStepArgument($label);
    $idArgument = $id ? $this->fixStepArgument($id) : '';
    $element = $this->getSession()->getPage();

    $radiobutton = $idArgument ? $element->findById($idArgument) : $element->find('named', ['radio', $this->getSession()->getSelectorsHandler()->xpathLiteral($label)]);

    if ($radiobutton === null) {
      $identifier = $idArgument ?: $label;
      throw new \Exception(sprintf('The radio button with "%s" was not found on the page %s', $identifier, $this->getSession()->getCurrentUrl()));
    }

    $radio_id = $radiobutton->getAttribute('id');
    $labelElement = $element->find('css', sprintf("label[for='%s']", $radio_id));
    if ($labelElement !== null) {
      $labelonpage = $labelElement->getText();
    }
    else {
      // Fallback: check if the radio button is wrapped in a label element.
      $parent = $radiobutton->getParent();
      if ($parent !== null && strtolower($parent->getTagName()) === 'label') {
        $labelonpage = $parent->getText();
      }
      else {
        // Final fallback: use the radio button's own text if available.
        $labelonpage = $radiobutton->getText();
      }
    }

    if ($label !== $labelonpage) {
      $buttonId = $idArgument ?: $radio_id;
      throw new \Exception(sprintf('Button with id "%s" has label "%s" instead of "%s" on the page %s', $buttonId, $labelonpage, $label, $this->getSession()->getCurrentUrl()));
    }
  }

}
