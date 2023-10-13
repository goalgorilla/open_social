<?php

namespace Drupal\social\Behat;

use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around CKEditor manipulation.
 */
class CKEditorContext extends RawMinkContext {


  /**
   * Turn off ckeditor.
   *
   * @Given I turn off ckeditor
   */
  public function turnOffCkeditor()
  {
    // Disable ckeditor for basic and full html.
    \Drupal::configFactory()->getEditable('editor.editor.basic_html')->delete();
    \Drupal::configFactory()->getEditable('editor.editor.full_html')->delete();

  }

  /**
   * Allow typing in the CKEditor.
   *
   * @When /^I fill in the "([^"]*)" WYSIWYG editor with "([^"]*)"$/
   */
  public function iFillInTheWysiwygEditor($locator, $text) {
    $field = $this->getSession()->getPage()->findField($locator);

    if (NULL === $field) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value|placeholder', $locator);
    }

    $id = str_replace('edit-', '', $field->getAttribute('id'));
    $editor = "div.js-form-item-" . $id . " .ck-editor__editable";
    $this->getSession()
      ->executeScript(
        "
        const domEditableElement = document.querySelector(\"$editor\");
        if (domEditableElement.ckeditorInstance) {
          const editorInstance = domEditableElement.ckeditorInstance;
          if (editorInstance) {
            editorInstance.setData(\"$text\");
          } else {
            throw new Exception('Could not get the editor instance!');
          }
        } else {
          throw new Exception('Could not find the element!');
        }
        ");
  }

  /**
   * Open the Embed Content screen in the CKEditor.
   *
   * @When /^I click on the embed icon in the WYSIWYG editor$/
   */
  public function clickEmbedIconInWysiwygEditor() {
    $name = 'Url Embed';

    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      "//button[span[text()='$name']]"
    );
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate ckeditor button: "%s"', $name));
    }

    $element->click();
  }

  /**
   * Open the add image dialog in the CKEditor.
   *
   * @When /^I click on the image icon in the WYSIWYG editor$/
   */
  public function clickImageIconInWysiwygEditor() {
    $cssSelector = 'a.cke_button__drupalimage';

    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
    );
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $element->click();
  }

  /**
   * Get the wysiwyg instance variable to use in Javascript.
   *
   * @param string $instanceId
   *   The instanceId used by the WYSIWYG module to identify the instance.
   *
   * @throws \Exception
   *   Throws an exception if the editor does not exist.
   *
   * @return string
   *   A Javascript expression representing the WYSIWYG instance.
   */
  protected function getWysiwygInstance($instanceId) {
    $instance = "CKEDITOR.instances['$instanceId']";
    if (!$this->getSession()->evaluateScript("return !!$instance")) {
      throw new \Exception(sprintf('The editor "%s" was not found on the page %s', $instanceId, $this->getSession()->getCurrentUrl()));
    }
    return $instance;
  }

}
