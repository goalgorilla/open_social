<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalContext;
use Behat\MinkExtension\Context\RawMinkContext;
use PHPUnit_Framework_Assert as PHPUnit;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext implements Context, SnippetAcceptingContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @When I click radio button :label with the id :id
     * @When I click radio button :label
     */
    public function clickRadioButton($label, $id = '') {
      $session = $this->getSession();

      $session->executeScript(
        "var inputs = document.getElementsByTagName('input');
        for(var i = 0; i < inputs.length; i++) {
        inputs[i].style.opacity = 1;
        inputs[i].style.left = 0;
        inputs[i].style.position = 'relative';
        }
        ");

      $element = $session->getPage();

      $radiobutton = $id ? $element->findById($id) : $element->find('named', array('radio', $this->getSession()->getSelectorsHandler()->xpathLiteral($label)));
      if ($radiobutton === NULL) {
        throw new \Exception(sprintf('The radio button with "%s" was not found on the page %s', $id ? $id : $label, $this->getSession()->getCurrentUrl()));
      }
      $value = $radiobutton->getAttribute('value');
      $labelonpage = $radiobutton->getParent()->getText();
      if ($label != $labelonpage) {
        throw new \Exception(sprintf("Button with id '%s' has label '%s' instead of '%s' on the page %s", $id, $labelonpage, $label, $this->getSession()->getCurrentUrl()));
      }
      $radiobutton->selectOption($value, FALSE);

    }

    /**
     * @Then :textBefore should precede :textAfter for the query :cssQuery
     */
    public function shouldPrecedeForTheQuery($textBefore, $textAfter, $cssQuery) {
      $elements = $this->getSession()->getPage()->findAll('css', $cssQuery);

      $items = array_map(
        function ($element) {
          return $element->getText();
        },
        $elements
      );
      PHPUnit::assertGreaterThan(
        array_search($textBefore, $items),
        array_search($textAfter, $items),
        "$textBefore does not proceed $textAfter"
      );
    }

}
