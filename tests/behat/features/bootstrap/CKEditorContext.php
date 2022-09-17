<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Defines test steps around CKEditor manipulation.
 */
class CKEditorContext extends RawMinkContext {

  /**
   * The Drupal mink context is useful for validation of content.
   */
  private MinkContext $minkContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->minkContext = $environment->getContext(SocialMinkContext::class);
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

    $id = $field->getAttribute('id');
    $instance = $this->getWysiwygInstance($id);
    $this->getSession()->executeScript("$instance.setData(\"$text\");");
  }

  /**
   * Open the Embed Content screen in the CKEditor.
   *
   * @When /^I click on the embed icon in the WYSIWYG editor$/
   */
  public function clickEmbedIconInWysiwygEditor() {
    $cssSelector = 'a.cke_button__social_embed';

    $session = $this->getSession();
    $element = $session->getPage()->find('css', $cssSelector);
    if (NULL === $element) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), NULL, 'css', $cssSelector);
    }

    $element->click();

    $ajax_timeout = $this->getMinkParameter('ajax_timeout');
    $session->wait(1000 * $ajax_timeout, 'document.getElementById("editor-image-dialog-form") !== null');
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

    $ajax_timeout = $this->getMinkParameter('ajax_timeout');
    $this->getSession()->getDriver()->wait(1000 * $ajax_timeout, "document.querySelectorAll('.ui-dialog').length > 0");
  }

  /**
   * Opens the image dialog in the CKEditor and uploads the image.
   *
   * @When I add image :image to the CKEditor
   * @When I add image :image to the CKEditor with alt text :alt
   */
  public function iAddImageToTheCkEditor(string $image, ?string $alt = NULL) : void {
    $this->clickImageIconInWysiwygEditor();

    $dialog = $this->getSession()->getPage()->find('css', '.ui-dialog');
    if ($dialog === NULL) {
      throw new \Exception("Dialog wasn't opened.");
    }

    $uploaded = count($dialog->findAll('css', '.preview'));

    // We fill by id because it may otherwise fill the ID outside of the dialog.
    $id = $dialog->findField("Image")?->getAttribute('id');
    $this->minkContext->attachFileToField($id, $image);

    // Wait for the number of previews to increase.
    $ajax_timeout = $this->getMinkParameter('ajax_timeout');
    $this->getSession()->getDriver()->wait(1000 * $ajax_timeout, "document.querySelectorAll('#editor-image-dialog-form .preview').length > $uploaded");

    if ($alt !== NULL) {
      $dialog->fillField("Alternative text", $alt);
    }

    $dialog->pressButton("Save");
    $this->getSession()->getDriver()->wait(1000 * $ajax_timeout, "document.querySelectorAll('.ui-dialog').length === 0");
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
