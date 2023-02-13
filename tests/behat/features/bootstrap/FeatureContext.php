<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;
use Drupal\ginvite\GroupInvitation as GroupInvitationWrapper;
use Drupal\locale\SourceString;
use Behat\Mink\Selector\Xpath\Escaper;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext {

    use GroupTrait;

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

      /** @var \Behat\Testwork\Environment\Environment $environment */
      $environment = $scope->getEnvironment();
      $this->minkContext = $environment->getContext(SocialMinkContext::class);

      $this->getSession()->resizeWindow(1280, 2024, 'current');
    }

    /**
     * Check that a user sees an access denied page.
     *
     * @Then I should be denied access
     */
    public function iShouldBeDeniedAccess() : void {
      $this->assertSession()->statusCodeEquals(403);

      $page = $this->getSession()->getPage();
      $page->hasContent("Access Denied");
      $page->hasContent("You are not authorized to access this page.");
    }

    /**
     * Check that an anonymous user is asked to login to view a page.
     *
     * @Then I should be asked to login
     */
    public function iShouldBeAskedToLogin() : void {
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->addressEquals("/user/login");

      $page = $this->getSession()->getPage();
      $page->hasContent("Access Denied. You must log in to view this page.");

    }

    /**
     * @Then I should see :text in the :heading block
     */
    public function shouldSeeTextInHeadingBlock(string $text, string $heading) {
      // There seems to be no easy way to search all links so we musst craft our
      // own xpath looking at examples from `NamedSelector`.
      $heading_literal = (new Escaper())->escapeLiteral($heading);
      $search_string = "contains(normalize-space(string(.)), $heading_literal)";
      $xpaths = [];
      for ($heading_level=1;$heading_level<=6;$heading_level++) {
        $xpaths[] = ".//h{$heading_level}//.//descendant-or-self::*[{$search_string}]";
      }
      $xpath = join("|", $xpaths);

      $matching_headings = $this->getSession()->getPage()->findAll('xpath', $xpath);

      // We rely on the fact that for how our blocks are always rendered in a
      // `section` element that will have an ID containing `block`. We take into
      // account that a heading may be nested inside something inside the
      // section, but it may not be in multiple sections.
      $blocks = array_filter(
        array_map(
          function (NodeElement $el) {
            do {
              $el = $el->getParent();
              if ($el->getTagName() === "section") {
                return
                  str_contains($el->getAttribute("id") ?? "", "block")
                  ? $el
                  : NULL;
              }
            } while ($el->getTagName() !== "body");

            return NULL;
          },
          $matching_headings
        )
      );

      if (count($blocks) === 0) {
        throw new \RuntimeException("Could not find a block with a heading of any level containing '$heading'.");
      }
      if (count($blocks) > 1) {
        throw new \RuntimeException("Found multiple blocks with a heading of any level containing '$heading'.");
      }

      $block = current($blocks);
      if (!$block->has('named', ['content', $text])) {
        throw new \RuntimeException("Could not find '$text' in block with heading '$heading'.");
      }
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
     * @Then /^The embedded content in the body description should have the src "([^"]*)"$/
     */
    public function embeddedContentInBodyDescriptionShouldHaveTheSrc($src) {

      $cssSelector = 'article .card__body .body-text .social-embed-container iframe';

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
      // Only check the label if we were selecting by ID, otherwise we already
      // found the button by a magic label, and help text may cause the parent
      // of the radio button to have more text than the label, making the
      // following always fail.
      if ($id) {
        $labelonpage = $radiobutton->getParent()->getText();
        if ($label !== '' && $label != $labelonpage) {
          throw new \Exception(sprintf("Button with id '%s' has label '%s' instead of '%s' on the page %s", $id, $labelonpage, $label, $this->getSession()
            ->getCurrentUrl()));
        }
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
     * @param $group_title
     * @param $mail
     *
     * @return null
     */
    public function getGroupContentIdFromGroupTitle($group_title, $mail) {

      $properties = [
        'gid' => $this->getNewestGroupIdFromTitle($group_title),
        'invitation_status' => 0,
        'invitee_mail' => $mail
      ];
      $loader = \Drupal::service('ginvite.invitation_loader');
      $invitations = $loader->loadByProperties($properties);

      if ($invitations > 0) {
        $invitation = reset($invitations);

        if ($invitation instanceof GroupInvitationWrapper) {
          $group_content = $invitation->getGroupContent();
          return $group_content->id();
        }
      }
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
        ->accessCheck(FALSE);
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
     * Opens register page with destination to invited group.
     *
     * @Given /^(?:|I )open register page with prefilled "(?P<mail>[^"]+)" and destination to invited group "(?P<group_title>[^"]+)"$/
     */
    public function openRegisterPageDestinationGroup($mail, $group_title)
    {
      $group_content_id = $this->getGroupContentIdFromGroupTitle($group_title, $mail);
      $mail_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($mail));
      $page = '/user/register?invitee_mail=' . $mail_encoded . '&destination=/social-group-invite/' . $group_content_id . '/accept';

      $this->visitPath($page);
    }

    /**
     * Opens register page with destination to invited node.
     *
     * @Given /^(?:|I )open register page with prefilled "(?P<mail>[^"]+)" and destination to invited node "(?P<node_title>[^"]+)"$/
     */
    public function openRegisterPageDestinationNode($mail, $node_title)
    {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')
        ->loadByProperties(['title' => $node_title]);
      $node = reset($nodes);

      $mail_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($mail));
      $page = '/user/register?invitee_mail=' . $mail_encoded . '&destination=/node/' . $node->id();

      $this->visitPath($page);
    }

    /**
     * Keep track of intended user names so they can be cleaned up.
     *
     * @var array
     */
    protected $intended_user_names = [];

    /**
     * Stores the user's name in $this->intended_user_names.
     *
     * This goes before a register form manipulation and submission.
     *
     * @Given I intend to create a user named :name
     *
     * @see cleanUsers()
     */
    public function intendUserName($name) {
      $this->intended_user_names[] = $name;
    }

    /**
     * Remove any queue items that were created.
     *
     * @AfterScenario
     */
    public function cleanupQueue(AfterScenarioScope $scope)
    {
      $workerManager = \Drupal::service('plugin.manager.queue_worker');
      /** @var Drupal\Core\Queue\QueueFactory; $queue */
      $queue = \Drupal::service('queue');

      foreach ($workerManager->getDefinitions() as $name => $info) {
        /** @var Drupal\Core\Queue\QueueInterface $worker */
        $worker = $queue->get($name);

        if ($worker->numberOfItems() > 0) {
          while ($item = $worker->claimItem()) {
            // If we don't just delete them, process the item first.
            $worker->deleteItem($item);
          }
        }
      }
    }

  /**
   * Remove any users that were created.
   *
   * @AfterScenario
   */
  public function cleanupUser(AfterScenarioScope $scope)
  {
    if (!empty($this->intended_user_names)) {
      foreach ($this->intended_user_names as $name) {
        $user_obj = user_load_by_name($name);
        \Drupal::entityTypeManager()->getStorage('user')->load($user_obj->id())->delete();
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
      $url = $file->createFileUrl();
      $page = \Drupal::service('file_url_generator')->transformRelative($url);
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

    /**
     * @When /^I click "([^"]*)" on the row containing "([^"]*)"$/
     */
    public function iClickOnOnTheRowContaining($link_name, $row_text) {
      /** @var $row \Behat\Mink\Element\NodeElement */
      $row = $this->getSession()->getPage()->find('css', sprintf('table tr:contains("%s")', $row_text));
      if (!$row) {
        throw new \Exception(sprintf('Cannot find any row on the page containing the text "%s"', $row_text));
      }

      $row->clickLink($link_name);
    }

  /**
   * Expand a details area.
   *
   * @When I expand the :label section
   */
    public function iExpandDetailsSection(string $label) : void {
      $elements = array_filter(
        $this->getSession()->getPage()->findAll("css", "summary"),
        fn (NodeElement $el) => str_contains($el->getText(), $label),
      );

      if (count($elements) === 0) {
        throw new ElementNotFoundException($this->getSession(), "summary", "css", "summary");
      }

      if (count($elements) > 1) {
        throw new \RuntimeException("More than one summary element was found with label '$label', make the labels unique or improve your label specificity.");
      }

      // Store the summary so we can click it and find the parent details element.
      $element = $summary = current($elements);
      do {
        $element = $element->getParent();
        if ($element->getTagName() === "body") {
          throw new \RuntimeException("The summary field for '$label' was not in a parent 'details' element to expand.");
        }
      } while ($element->getTagName() !== "details");

      // If the default state for the details is open then the test should be
      // adjusted to encode that behaviour.
      if ($element->hasAttribute("open")) {
        throw new \RuntimeException("The details element for '$label' is already opened.");
      }

      // Expand the details element.
      $summary->click();
    }

    /**
     * Remove any user consents that were created.
     *
     * @AfterScenario @data-policy-create
     */
    public function deleteUserConsentEntities() {
      $consents = \Drupal::entityTypeManager()
        ->getStorage('user_consent')
        ->loadMultiple();

      foreach ($consents as $consent) {
        try {
          $consent->delete();
        }
        catch (\Throwable $e) {
          // This can be fine.
        }
      }
    }

    /**
     * Set "/stream" as a front page.
     *
     * @AfterScenario @alternative-frontpage
     */
    public function setFrontPage() {
      \Drupal::configFactory()->getEditable('system.site')->set('page.front', '/stream')->save();
    }
}
