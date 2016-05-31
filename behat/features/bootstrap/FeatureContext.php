<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\DrupalContext;
use Behat\MinkExtension\Context\RawMinkContext;
use PHPUnit_Framework_Assert as PHPUnit;
use Drupal\profile\Entity\Profile;
use Drupal\group\Entity\Group;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;

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
     * Get the wysiwyg instance variable to use in Javascript.
     *
     * @param string
     *   The instanceId used by the WYSIWYG module to identify the instance.
     *
     * @throws Exception
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

    /**
     * @When /^I fill in the "([^"]*)" WYSIWYG editor with "([^"]*)"$/
     */
    public function iFillInTheWysiwygEditor($instanceId, $text) {
      $instance = $this->getWysiwygInstance($instanceId);
      $this->getSession()->executeScript("$instance.setData(\"$text\");");
    }

    /**
     * @When I click admin link :text
     */
    public function clickAdminLink($text) {

      $page = $this->getSession()->getPage();
      $adminspan = $page->find('xpath', '//a//span[text()="'.$text.'"]');

      if ($adminspan === null) {
        throw new \InvalidArgumentException(sprintf('Cannot find the admin link with text: "%s"', $text));
      }

      $adminlink = $adminspan->getParent();
      $adminlink->click();
    }

    /**
     * @When I select post visibility :visibility
     */
    public function iSelectPostVisibility($visibility) {
      $allowed_visibility = array(
        '0' => 'Recipient', // Is displayed as Community in front-end.
        '1' => 'Public',
        '2' => 'Community',
      );

      if (!in_array($visibility, $allowed_visibility)) {
        throw new \InvalidArgumentException(sprintf('This visibility option is not allowed: "%s"', $visibility));
      }

      // First make post visibility setting visible.
      $this->iClickPostVisibilityDropdown();

      // Click the radio button.
      $key = array_search($visibility, $allowed_visibility);
      if (!empty($key)) {
        $id = 'edit-field-visibility-0-' . $key;
        $this->clickRadioButton('', $id);
      }
      else {
        throw new \InvalidArgumentException(sprintf('Could not find key for visibility option: "%s"', $visibility));
      }

      // Hide post visibility setting.
      $this->iClickPostVisibilityDropdown();

    }


  /**
   * @When I click the xth :position link with the text :locator
   */
  public function iClickTheLinkWithText($position, $locator)
  {
    $session = $this->getSession();
    $links = $session->getPage()->findAll('named', array('link', $locator));
    $count = 1;
    foreach($links as $link) {
      if ($count == $position) {
        // Now click the element.
        $link->click();
        return;
      }
      $count++;
    }
    throw new \InvalidArgumentException(sprintf('Element not found with the locator: "%s"', $locator));
  }

  /**
   * @When I click the xth :position element with the css :css
   */
  public function iClickTheElementWithTheCSS($position, $css)
  {
    $session = $this->getSession();
    $elements = $session->getPage()->findAll('css', $css);

    $count = 0;

    foreach($elements as $element) {
      if ($count == $position) {
        // Now click the element.
        $element->click();
        return;
      }
      $count++;
    }
    throw new \InvalidArgumentException(sprintf('Element not found with the css: "%s"', $css));
  }

    /**
     * @When /^I click the post visibility dropdown/
     */
    public function iClickPostVisibilityDropdown()
    {
      $locator = 'button#post-visibility';
      $session = $this->getSession();
      $element = $session->getPage()->find('css', $locator);

      if ($element === NULL) {
        throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
      }

      // Now click the element.
      $element->click();
    }

    /**
     * @When I click radio button :label with the id :id
     * @When I click radio button :label
     */
    public function clickRadioButton($label = '', $id = '') {
      $session = $this->getSession();

      $session->executeScript(
        "var inputs = document.getElementsByTagName('input');
        for(var i = 0; i < inputs.length; i++) {
        inputs[i].style.opacity = 1;
        inputs[i].style.left = 0;
        inputs[i].style.visibility = 'visible';
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
      if ($label !== '' && $label != $labelonpage) {
        throw new \Exception(sprintf("Button with id '%s' has label '%s' instead of '%s' on the page %s", $id, $labelonpage, $label, $this->getSession()->getCurrentUrl()));
      }
      $radiobutton->selectOption($value, FALSE);

    }

    /**
     * Shows hidden button.
     *
     * @When /^(?:|I )show hidden buttons$/
     */
    public function showHiddenButton()
    {
      $session = $this->getSession();

      $session->executeScript(
        "var inputs = document.getElementsByClassName('secondary-action');
        for(var i = 0; i < inputs.length; i++) {
        inputs[i].style.opacity = 1;
        inputs[i].style.left = 0;
        inputs[i].style.position = 'relative';
        inputs[i].style.display = 'block';
        }
        ");
    }

    /**
     * Opens specified page.
     *
     * @Given /^(?:|I )am on the profile of "(?P<username>[^"]+)"$/
     * @When /^(?:|I )go to the profile of "(?P<username>[^"]+)"$/
     */
    public function openProfileOf($username)
    {
      $account = user_load_by_name($username);
      if ($account->id() !== 0) {
        $account_uid = $account->id();
      }
      else {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $username));
      }
      $page = '/user/' . $account_uid;

      $this->visitPath($page);
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
      $checkBefore = $textBefore;
      $checkAfter = $textAfter;

      foreach($items as $item) {
        if (strpos($item, $textBefore) !== FALSE) {
          $checkBefore = $item;
        }
        elseif (strpos($item, $textAfter) !== FALSE) {
          $checkAfter = $item;
        }
      }

      PHPUnit::assertGreaterThan(
        array_search($checkBefore, $items),
        array_search($checkAfter, $items),
        "$textBefore does not proceed $textAfter"
      );
    }

    /**
     * @BeforeScenario
     */
    public function resizeWindow()
    {
      $this->getSession()->resizeWindow(1280, 1024, 'current');
    }

    /**
     * Hook into user creation to add profile fields `@afterUserCreate`
     *
     * @afterUserCreate
     */
    public function alterUserParameters(EntityScope $event) {
      $account = $event->getEntity();
      // Get profile of current user.
      if (!empty($account->uid)) {
        $user_account = \Drupal::entityTypeManager()->getStorage('user')->load($account->uid);
        $storage = \Drupal::entityTypeManager()->getStorage('profile');
        if (!empty($storage)) {
          $user_profile = $storage->loadByUser($user_account, 'profile', TRUE);
          if ($user_profile) {
            // Set given profile field values.
            foreach ($user_profile->toArray() as $field_name => $value) {
              if (isset($account->{$field_name})) {
                $user_profile->set($field_name, $account->{$field_name});
              }
            }
            $user_profile->save();
          }
        }
      }
    }

    /**
     * Creates group of a given type provided in the form:
     * | title    | description     | author   | type        | language
     * | My title | My description  | username | open_group  | en
     * | ...      | ...             | ...      | ...         | ...
     *
     * @Given groups:
     */
    public function createGroups(TableNode $groupsTable) {
      foreach ($groupsTable->getHash() as $groupHash) {
        $group = (object) $groupHash;
        $this->groupCreate($group);
      }
    }

    /**
     * Create a user.
     *
     * @return object
     *   The created user.
     */
    public function groupCreate($group) {

      $account = user_load_by_name($group->author);
      if ($account->id() !== 0) {
        $account_uid = $account->id();
      }
      else {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $username));
      }

      // Let's create some groups.
      $group_object = Group::create([
        'langcode' => $group->language,
        'uid' => $account_uid,
        'type' => $group->type,
        'label' => $group->title,
        'field_group_description' => $group->description,
      ]);

      $group_object->save();

      return $group;
    }

    /**
     * Opens group stream page.
     *
     * @Given /^(?:|I )am on the stream of group "(?P<group_title>[^"]+)"$/
     * @When /^(?:|I )go to the stream of group "(?P<group_title>[^"]+)"$/
     */
    public function openGroupStreamPage($group_title)
    {

      $query = \Drupal::entityQuery('group')
        ->condition('label', $group_title);

      $group_ids = $query->execute();
      $groups = entity_load_multiple('group', $group_ids);

      if (count($groups) > 1) {
        throw new \Exception(sprintf("Multiple groups with title '%s' exists.", $group_title));
      }
      else {
        $group = reset($groups);
        if ($group->id() !== 0) {
          $group_id = $group->id();
        }
        else {
          throw new \Exception(sprintf("Group with group_title '%s' does not exist.", $group_title));
        }
      }
      $page = '/group/' . $group_id . '/stream';

      $this->visitPath($page);
    }

}
