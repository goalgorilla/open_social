<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;
use Drupal\group\Entity\Group;
use Drupal\locale\SourceString;
use Behat\Mink\Selector\Xpath\Escaper;
use PHPUnit\Framework\Assert as Assert;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext implements Context
{

    protected $minkContext;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct() {
    }

    /**
     * Keep track of all groups that are created so they can easily be removed.
     *
     * @var array
     */
    protected $groups = array();

    /**
     * @BeforeScenario
     *
     * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
     */
    public function before(BeforeScenarioScope $scope) {
      // Start a session if not already done.
      // Needed since https://github.com/minkphp/Mink/pull/705
      // Otherwise resizeWindow will throw an error.
      if (!$this->getSession()->isStarted()) {
        $this->getSession()->start();
      }

      // Let's disable the tour module for all tests by default.
      \Drupal::configFactory()->getEditable('social_tour.settings')->set('social_tour_enabled', 0)->save();

      /** @var \Behat\Testwork\Environment\Environment $environment */
      $environment = $scope->getEnvironment();
      $this->minkContext = $environment->getContext(SocialMinkContext::class);

      $this->getSession()->resizeWindow(1280, 2024, 'current');
    }

  /**
   * @AfterScenario
   *
   * @param $event
   */
    public function after($event) {
      // Let's disable the tour module for all tests by default.
      \Drupal::configFactory()->getEditable('social_tour.settings')->set('social_tour_enabled', 1)->save();
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
    public function iFillInTheWysiwygEditor($locator, $text) {
      $field = $this->getSession()->getPage()->findField($locator);

      if (null === $field) {
        throw new ElementNotFoundException($this->getDriver(), 'form field', 'id|name|label|value|placeholder', $locator);
      }

      $id = $field->getAttribute('id');
      $instance = $this->getWysiwygInstance($id);
      $this->getSession()->executeScript("$instance.setData(\"$text\");");
    }

    /**
     * @When /^I click on the embed icon in the WYSIWYG editor$/
     */
    public function clickEmbedIconInWysiwygEditor() {

      $cssSelector = 'a.cke_button__social_embed';

      $session = $this->getSession();
      $element = $session->getPage()->find(
        'xpath',
        $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
      );
      if (null === $element) {
        throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
      }

      $element->click();
    }

    /**
     * @Then /^The iframe in the body description should have the src "([^"]*)"$/
     */
    public function iFrameInBodyDescriptionShouldHaveTheSrc($src) {

      $cssSelector = 'article .card__body .body-text iframe';

      $session = $this->getSession();
      $element = $session->getPage()->find(
        'xpath',
        $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
      );
      if (null === $element) {
        throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
      }

      $iframe_source = $element->getAttribute('src');

      // the sources could contain certain metadata making it hard to test
      // if it matches the given source. So we don't strict check rather
      // check if part of the source matches.
      if (strpos($iframe_source, $src) === FALSE) {
        throw new \InvalidArgumentException(sprintf('The iframe source does not contain the src: "%s" it is however: "%s"', $src, $iframe_source));
      }
    }

    /**
     * @When /^I click on the image icon in the WYSIWYG editor$/
     */
    public function clickImageIconInWysiwygEditor() {

      $cssSelector = 'a.cke_button__drupalimage';

      $session = $this->getSession();
      $element = $session->getPage()->find(
        'xpath',
        $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
      );
      if (null === $element) {
        throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
      }

      $element->click();

    }

    /**
     * @Then /^The image path in the body description should be private$/
     */
    public function imagePathInBodyDescriptionShouldBePrivate() {

      $cssSelector = 'article .card__body .body-text img';

      $session = $this->getSession();
      $element = $session->getPage()->find(
        'xpath',
        $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
      );
      if (null === $element) {
        throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
      }

      $src = $element->getAttribute('src');

      if (strpos($src, '/system/files/inline-images') === FALSE) {
        throw new \InvalidArgumentException(sprintf('The image does not seem to be uploaded in the private file system: "%s"', $src));
      }
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
      // First make post visibility setting visible.
      $this->iClickPostVisibilityDropdown();

      // Click the label of the readio button with the visibility. The radio
      // button itself can't be clicked because it's invisible.
      $page = $this->getSession()->getPage();
      $field = $page->findField($visibility);

      if (null === $field) {
        throw new ElementNotFoundException($this->getDriver(), 'form field', 'id|name|label|value|placeholder', $visibility);
      }

      $field->getParent()->click();
    }

    /**
     * @When I select group :group
     */
    public function iSelectGroup($group) {

      if ($group === "- None -") {
        $option = '_none';
      }

      if ($group !== "- None -") {
        $option = $this->getGroupIdFromTitle($group);
      }

      if (!$option) {
        throw new \InvalidArgumentException(sprintf('Could not find group for "%s"', $group));
      }

      $this->getSession()->getPage()->selectFieldOption('edit-groups', $option);

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
   * @When I click the xth :position element with the css :css in the :region( region)
   */
  public function iClickTheRegionElementWithTheCSS($position, $css, $region)
  {
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);
    $elements = $regionObj->findAll('css', $css);

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
   * Click on the element with the provided CSS Selector
   *
   * @When /^I click the element with css selector "([^"]*)"$/
   */
  public function iClickTheElementWithCSSSelector($cssSelector)
  {
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector) // just changed xpath to css
    );
    if (null === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $element->click();
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
     * @When /^I click the group member dropdown/
     */
    public function iClickGroupMemberDropdown()
    {
      $locator = '.add-users-dropbutton .dropdown-toggle';
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

      $escaper = new Escaper();
      $radiobutton = $id ? $element->findById($id) : $element->find('named', array('radio', $escaper->escapeLiteral($label)));
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
     * Shows hidden checkboxes.
     *
     * @When /^(?:|I )show hidden checkboxes/
     */
    public function showHiddenCheckbox()
    {
      $session = $this->getSession();

      $session->executeScript(
        "var inputs = document.getElementsByClassName('form-checkbox');
          for(var i = 0; i < inputs.length; i++) {
          inputs[i].style.opacity = 1;
          inputs[i].style.left = 0;
          inputs[i].style.position = 'relative';
          inputs[i].style.display = 'block';
          }
          ");
    }

    /**
     * Shows hidden inputs.
     *
     * @When /^(?:|I )show hidden inputs/
     */
    public function showHiddenInputs()
    {
      $session = $this->getSession();

      $session->executeScript(
        "var inputs = document.getElementsByClassName('input');
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

      Assert::assertGreaterThan(
        array_search($checkBefore, $items),
        array_search($checkAfter, $items),
        "$textBefore does not proceed $textAfter"
      );
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
        $groupFields = (object) $groupHash;
        try {
          $group = $this->groupCreate($groupFields);
          $this->groups[$groupFields->title] = $group;
        } catch (Exception $e) {

        }
      }
    }

    /**
     * Remove any groups that were created.
     *
     * @AfterScenario
     */
    public function cleanupGroups(AfterScenarioScope $scope) {
      if (!empty($this->groups)) {
        foreach ($this->groups as $group) {
          $group->delete();
        }
      }
    }

    /**
     * Create a group.
     *
     * @return object
     *   The created group.
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

      return $group_object;
    }

    /**
     * Opens group stream page.
     *
     * @Given /^(?:|I )am on the stream of group "(?P<group_title>[^"]+)"$/
     * @When /^(?:|I )go to the stream of group "(?P<group_title>[^"]+)"$/
     */
    public function openGroupStreamPage($group_title)
    {
      $group_id = $this->getGroupIdFromTitle($group_title);
      $page = '/group/' . $group_id . '/stream';

      $this->visitPath($page);
    }

    /**
     * @param $group_title
     *
     * @return null
     */
    public function getGroupIdFromTitle($group_title) {

      $query = \Drupal::entityQuery('group')
        ->condition('label', $group_title);

      $group_ids = $query->execute();
      $groups = \Drupal::entityTypeManager()->getStorage('group')->loadMultiple($group_ids);

      if (count($groups) > 1) {
        return NULL;
      }
      else {
        $group = reset($groups);
        if ($group->id() !== 0) {
          $group_id = $group->id();
        }
      }
      return $group_id;
    }

    /**
     * Opens specified node page of type and with title.
     *
     * @Given /^(?:|I )open the "(?P<type>[^"]+)" node with title "(?P<title>[^"]+)"$/
     * @When /^(?:|I )go the  "(?P<type>[^"]+)" node with title "(?P<title>[^"]+)"$/
     */
    public function openNodeWithTitle($type, $title)
    {
      $query = \Drupal::entityQuery('node')
        ->condition('type', $type)
        ->condition('title', $title, '=')
        ->addTag('DANGEROUS_ACCESS_CHECK_OPT_OUT');
      $nids = $query->execute();

      if (!empty($nids) && count($nids) === 1) {
        $nid = reset($nids);
        $page = '/node/' . $nid;

        $this->visitPath($page);
      }
      else {
        if (count($nids) > 1) {
          throw new \Exception(sprintf("Multiple nodes of type '%s' with title '%s' found.", $type, $title));
        }
        else {
          throw new \Exception(sprintf("Node of type '%s' with title '%s' does not exist.", $type, $title));
        }
      }
    }

    /**
     * Checks if correct amount of uploaded files by user are private.
     *
     * @Then /User "(?P<username>[^"]+)" should have uploaded "(?P<private>[^"]+)" private files and "(?P<public>[^"]+)" public files$/
     */
    public function checkFilesPrivateForUser($username, $private, $public)
    {

      $query = \Drupal::entityQuery('user')
        ->condition('name', $username);
      $uid = $query->execute();

      if (!empty($uid) && count($uid) === 1) {
        $uid = reset($uid);

        if ($uid) {
          $private_query = \Drupal::database()->select('file_managed', 'fm');
          $private_query->addField('fm', 'fid');
          $private_query->condition('fm.uid', $uid, '=');
          $private_query->condition('fm.uri', 'private://%', 'LIKE');
          $private_count = count($private_query->execute()->fetchAllAssoc('fid'));

          $public_query = \Drupal::database()->select('file_managed', 'fm');
          $public_query->addField('fm', 'fid');
          $public_query->condition('fm.uid', $uid, '=');
          $public_query->condition('fm.uri', 'public://%', 'LIKE');
          $public_count = count($public_query->execute()->fetchAllAssoc('fid'));

          Assert::assertEquals($private, $private_count, sprintf("Private count was not '%s', instead '%s' private files found.", $private, $private_count));
          Assert::assertEquals($public, $public_count, sprintf("Public count was not '%s', instead '%s' public files found.", $public, $public_count));
        }

      }
      else {
        throw new \Exception(sprintf("User '%s' does not exist.", $username));
      }
    }

    /**
     * Opens the files uploaded by a given user.
     *
     * @Then /I open and check the access of the files uploaded by "(?P<username>[^"]+)" and I expect access "(?P<access>[^"]+)"$/
     */
    public function openAndCheckFilesPrivateForUser($username, $access)
    {
      $allowed_access = array(
        '0' => 'denied',
        '1' => 'allowed',
      );
      if (!in_array($access, $allowed_access)) {
        throw new \InvalidArgumentException(sprintf('This access option is not allowed: "%s"', $access));
      }
      $expected_access = 0;
      if ($access == 'allowed') {
        $expected_access = 1;
      }

      $query = \Drupal::entityQuery('user')
        ->condition('name', $username);
      $uid = $query->execute();

      if (!empty($uid) && count($uid) === 1) {
        $uid = reset($uid);

        if ($uid) {
          $private_query = \Drupal::database()->select('file_managed', 'fm');
          $private_query->addField('fm', 'fid');
          $private_query->condition('fm.uid', $uid, '=');
          $private_query->condition('fm.uri', 'private://%', 'LIKE');
          $private_files = $private_query->execute()->fetchAllAssoc('fid');

          foreach ($private_files as $fid => $file) {
            $this->openFileAndExpectAccess($fid, $expected_access);
          }
        }
      }
      else {
        throw new \Exception(sprintf("User '%s' does not exist.", $username));
      }
    }

    /**
     * This opens the files and check for the expected access.
     *
     * @param $fid
     * @param $expected_access
     *  0 = NO access
     *  1 = YES access
     */
    public function openFileAndExpectAccess($fid, $expected_access) {
      /** @var \Drupal\file\Entity\File $file */
      $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
      $url = $file->url();
      $page = file_url_transform_relative($url);
      $this->visitPath($page);

      if ($expected_access == 0) {
        $this->assertSession()->pageTextContains('Access denied. You must log in to view this page.');
      }
      else {
        $this->assertSession()->pageTextNotContains('Access denied. You must log in to view this page.');
      }
    }

    /**
     * Log out.
     *
     * @Given /^(?:|I )logout$/
     */
    public function iLogOut()
    {
      $page = '/user/logout';
      $this->visitPath($page);
    }

    /**
     * Opens the content from a group and check for access.
     *
     * @Then /I open and check the access of content in group "(?P<groupname>[^"]+)" and I expect access "(?P<access>[^"]+)"$/
     */
    public function openAndCheckGroupContentAccess($groupname, $access)
    {
      $allowed_access = array(
        '0' => 'denied',
        '1' => 'allowed',
      );
      if (!in_array($access, $allowed_access)) {
        throw new \InvalidArgumentException(sprintf('This access option is not allowed: "%s"', $access));
      }
      $expected_access = 0;
      if ($access == 'allowed') {
        $expected_access = 1;
      }

      $query = \Drupal::entityQuery('group')
        ->condition('label', $groupname)
        ->accessCheck(FALSE);
      $gid = $query->execute();

      if (!empty($gid) && count($gid) === 1) {
        $gid = reset($gid);

        if ($gid) {
          $group = Group::load($gid);
          $group_content_types = \Drupal\group\Entity\GroupContentType::loadByEntityTypeId('node');
          $group_content_types = array_keys($group_content_types);

          // Get all the node's related to the current group
          $query = \Drupal::database()->select('group_content_field_data', 'gcfd');
          $query->addField('gcfd', 'entity_id');
          $query->condition('gcfd.gid', $group->id());
          $query->condition('gcfd.type', $group_content_types, 'IN');
          $query->execute()->fetchAll();

          $nodes = $query->execute()->fetchAllAssoc('entity_id');
          foreach (array_keys($nodes) as $key => $entity_id) {
            $this->openEntityAndExpectAccess('node', $entity_id, $expected_access);
          }

          // Get all the posts from this group
          $query = \Drupal::database()->select('post__field_recipient_group', 'pfrg');
          $query->addField('pfrg', 'entity_id');
          $query->condition('pfrg.field_recipient_group_target_id', $group->id());
          $query->execute()->fetchAll();

          $post_ids = $query->execute()->fetchAllAssoc('entity_id');

          foreach (array_keys($post_ids) as $key => $entity_id) {
            $this->openEntityAndExpectAccess('post', $entity_id, $expected_access);
          }
        }
      }
      else {
        if (empty($gid)) {
          throw new \Exception(sprintf("Group '%s' does not exist.", $groupname));
        }
        if (count($gid) > 1) {
          throw new \Exception(sprintf("Multiple groups with label '%s' found.", $groupname));
        }
      }
    }

    /**
     * This opens the entity and check for the expected access.
     *
     * @param $entity_type
     * @param $entity_id
     * @param $expected_access
     *  0 = NO access
     *  1 = YES access
     */
    public function openEntityAndExpectAccess($entity_type, $entity_id, $expected_access) {
      $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity_id);
      /** @var \Drupal\Core\Url $url */
      $url = $entity->toUrl();
      $page = $url->toString();

      $this->visitPath($page);

      if ($expected_access == 0) {
        $this->assertSession()->pageTextContains('Access denied');
      }
      else {
        $this->assertSession()->pageTextNotContains('Access denied');
      }
    }

    /**
     * @When I close the open tip
     */
    public function iCloseTheOpenTip()
    {
      $locator = 'a.joyride-close-tip';
      $session = $this->getSession();
      $element = $session->getPage()->find('css', $locator);

      if ($element === NULL) {
        throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $locator));
      }

      // Now click the element.
      $element->click();
    }

    /**
     * Turn off translations import.
     *
     * @Given I turn off translations import
     */
    public function turnOffTranslationsImport()
    {
      // Let's disable translation.path for now.
      \Drupal::configFactory()->getEditable('locale.settings')->set('translation.import_enabled', FALSE)->save();
    }

    /**
     * Translate a string.
     *
     * @Given /I translate "(?P<source>[^"]+)" to "(?P<translation>[^"]+)" for "(?P<langcode>[^"]+)"$/
     */
    public function iTranslate($source, $translation, $langcode)
    {
      $this->addTranslation($source, $translation, $langcode);
    }

    /**
     * Helper function to add translation.
     *
     * @param $source_string
     * @param $translated_string
     * @param $langcode
     */
    public function addTranslation($source_string, $translated_string, $langcode) {
      // Find existing source string.
      $storage = \Drupal::service('locale.storage');
      $string = $storage->findString(array('source' => $source_string));
      if (is_null($string)) {
        $string = new SourceString();
        $string->setString($source_string);
        $string->setStorage($storage);
        $string->save();
      }


      // Create translation. If one already exists, it will be replaced.
      $translation = $storage->createTranslation(array(
        'lid' => $string->lid,
        'language' => $langcode,
        'translation' => $translated_string,
      ))->save();
      return $translation;
    }

    /**
     * Fill multiple autocomplete field.
     *
     * @param string $field
     *   The field identifier.
     * @param string $text
     *   The typed text in field.
     * @param string $item
     *   The item for drop-down list.
     * @param bool $next
     *   (optional) TRUE if it is not first value.
     */
    public function fillAutocompleteField($field, $text, $item, $next = FALSE) {
      $element = $this->getSession()->getPage()->findField($field);

      if (null === $element) {
        throw new \Exception(sprintf('Field %s not found', $field));
      }

      if ($next) {
        $text = $element->getValue() . ', ' . $text;
      }

      $element->setValue($text);
      $element->keyDown(' ');
      sleep(1); // Wait timeout before sending an AJAX request.
      $this->minkContext->iWaitForAjaxToFinish();
      $id = $element->getAttribute('id');
      $index = $this->getSession()->evaluateScript('return jQuery(".ui-autocomplete-input").index(jQuery("#' . $id . '"));');
      $autocomplete = $this->getSession()->getPage()->find('xpath', '//ul[contains(@class, "ui-autocomplete")][' . ($index + 1) . ']');

      if (null === $autocomplete) {
        throw new \Exception('Could not find the autocomplete popup box');
      }

      $popup_element = $autocomplete->find('xpath', "//li[text()='{$item}']");

      // If "li" was not found, try to find "a" inside a "li".
      if (null === $popup_element) {
        $popup_element = $autocomplete->find('xpath', "//li/a[text()='{$item}']");
      }

      // If "li" was not found, try to find "div" inside a "li".
      if (null === $popup_element) {
        $popup_element = $autocomplete->find('xpath', "//li/div[text()='{$item}']");
      }

      if (null === $popup_element) {
        throw new \Exception(sprintf('Could not find autocomplete item with text %s', $item));
      }

      if (!empty($popup_element_id = $popup_element->getAttribute('id'))) {
        $this->getSession()->evaluateScript('jQuery("#' . $popup_element_id . '").click();');
      }
      else {
        $popup_element->click();
      }

      if ($next) {
        $this->getSession()->evaluateScript('jQuery("#' . $id . '").val(jQuery("#' . $id . '").val().replace(/\s(\d+\)\,\s)/g, " ($1"));');
      }
    }

    /**
     * @Given I fill in :field with :text and select :item
     */
    public function iFillInWithAndSelect($field, $text, $item) {
      $this->fillAutocompleteField($field, $text, $item);
    }

    /**
     * @Given I fill next in :field with :text and select :item
     */
    public function iFillNextInWithAndSelect($field, $text, $item) {
      $this->fillAutocompleteField($field, $text, $item, TRUE);
    }
}
