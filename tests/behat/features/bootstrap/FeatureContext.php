<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Session;
use Behat\MinkExtension\Context\MinkContext;
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

  /**
   * The Mink context.
   *
   * @var \Behat\MinkExtension\Context\MinkContext
   */
  protected MinkContext $minkContext;

  /**
   * Keep track of intended usernames so they can be cleaned up.
   *
   * @var array
   */
  protected array $intendedUserNames = [];

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
   * Before scenario.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The scenario scope.
   *
   * @BeforeScenario
   */
  public function before(BeforeScenarioScope $scope): void {
    // Restart the session in that case, this means
    // the browser cache is cleared and not shared between
    // scenario's. This is necessary due to enabling cache.page.max_age
    // which adds the Cache Control header and allows the browser
    // to cache things between scenario's.
    // See: https://github.com/goalgorilla/open_social/actions/runs/8188710546/job/22392267853.
    $this->getSession()->restart();

    // Start a session if not already done.
    // Needed since https://github.com/minkphp/Mink/pull/705
    // Otherwise resizeWindow will throw an error.
    if (!$this->getSession()->isStarted()) {
      $this->getSession()->start();
    }

    $environment = $scope->getEnvironment();
    $this->minkContext = $environment->getContext(SocialMinkContext::class);

    $this->getSession()->resizeWindow(1280, 2024, 'current');
  }

  /**
   * Check that a user sees access denied page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
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
   * Check that an anonymous user is asked to log in to view a page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @Then I should be asked to log in
   */
  public function iShouldBeAskedToLogin() : void {
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals("/user/login");

    $page = $this->getSession()->getPage();
    $page->hasContent("Access Denied. You must log in to view this page.");

  }

  /**
   * Should see a field with a specific label.
   *
   * @param string $label
   *   The field label.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *
   * @Then I should see a field labeled :label
   */
  public function shouldSeeFieldLabeled(string $label) : void {
    if (!$this->getSession()->getPage()->hasField($label)) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value|placeholder', $label);
    }
  }

  /**
   * Should not see a field with a specific label.
   *
   * @param string $label
   *   The field label.
   *
   * @Then I should not see a field labeled :label
   */
  public function shouldNotSeeFieldLabeled(string $label) : void {
    if ($this->getSession()->getPage()->hasField($label)) {
      throw new \RuntimeException("Found a form field with id|name|label|value|placeholder of $label but this should not be on the page.");
    }
  }

  /**
   * Should see a required field with a specific label.
   *
   * @param string $label
   *   The field label.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *
   * @Then I should see a required field labeled :label
   */
  public function shouldSeeRequiredFieldLabeled(string $label) : void {
    $field = $this->getSession()->getPage()->findField($label);
    if ($field === NULL) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'form field', 'id|name|label|value|placeholder', $label);
    }
    // File type fields rely on server side validation, so we can't check the
    // attribute. Similarly, text-area's replaced by a WYSIWYG don't have a
    // required at attribute.
    if (($field->getAttribute("type") !== "file" && $field->getTagName() !== "textarea") && !$field->hasAttribute("required")) {
      throw new \RuntimeException("Found field '$label' but it was not required when it should be.");
    }
  }

  /**
   * Should see text in heading block.
   *
   * @param string $text
   *   The text.
   * @param string $heading
   *   The heading.
   *
   * @throws \Behat\Mink\Exception\DriverException
   *
   * @Then I should see :text in the :heading block
   */
  public function shouldSeeTextInHeadingBlock(string $text, string $heading): void {
    // There seems to be no easy way to search all links so we must craft our
    // own xpath looking at examples from `NamedSelector`.
    $heading_literal = (new Escaper())->escapeLiteral($heading);
    $search_string = "contains(normalize-space(string(.)), $heading_literal)";
    $x_paths = [];
    for ($heading_level = 1; $heading_level <= 6; $heading_level++) {
      $x_paths[] = ".//h{$heading_level}//.//descendant-or-self::*[{$search_string}]";
    }
    $xpath = implode("|", $x_paths);

    $matching_headings = $this->getSession()->getPage()->findAll('xpath', $xpath);

    // We rely on the fact that for how our blocks are always rendered in a
    // `section` element that will have an ID containing `block`. We take into
    // account that a heading may be nested inside something inside the
    // section, but it may not be in multiple sections.
    $blocks = array_filter(
      array_map(
        static function (NodeElement $el) {
          do {
            $el = $el->getParent();
            if ($el->getTagName() === "section") {
              return str_contains($el->getAttribute("id") ?? "", "block")
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
   * Iframe in the body description should have the src.
   *
   * @param string $src
   *   The src.
   *
   * @Then /^The iframe in the body description should have the src "([^"]*)"$/
   */
  public function iFrameInBodyDescriptionShouldHaveTheSrc(string $src): void {
    $cssSelector = 'article .card__body .body-text iframe';

    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
    );
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $iframe_source = $element->getAttribute('src');

    // The sources could contain certain metadata making it hard to test
    // if it matches the given source. So we don't strict check rather
    // check if part of the source matches.
    if (!str_contains($iframe_source, $src)) {
      throw new \InvalidArgumentException(sprintf('The iframe source does not contain the src: "%s" it is however: "%s"', $src, $iframe_source));
    }
  }

  /**
   * Embed content in the body description should have the src.
   *
   * @param string $src
   *   The src.
   *
   * @Then /^The embedded content in the body description should have the src "([^"]*)"$/
   */
  public function embeddedContentInBodyDescriptionShouldHaveTheSrc(string $src): void {

    $cssSelector = 'article .card__body .body-text .social-embed-container iframe';
    $session = $this->getSession();
    // @todo getSelectorsHandler() is deprecated with version 2.
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
    );
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $iframe_source = $element->getAttribute('src');

    // The sources could contain certain metadata making it hard to test
    // if it matches the given source. So we don't strict check rather
    // check if part of the source matches.
    if (!str_contains($iframe_source, $src)) {
      throw new \InvalidArgumentException(sprintf('The iframe source does not contain the src: "%s" it is however: "%s"', $src, $iframe_source));
    }
  }

  /**
   * Image path in the body description should be private.
   *
   * @Then /^The image path in the body description should be private$/
   */
  public function imagePathInBodyDescriptionShouldBePrivate(): void {
    $cssSelector = 'article .card__body .body-text img';

    $session = $this->getSession();
    // @todo getSelectorsHandler() is deprecated with version 2.
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
    );
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $src = $element->getAttribute('src');

    if (!str_contains($src, '/system/files/inline-images')) {
      throw new \InvalidArgumentException(sprintf('The image does not seem to be uploaded in the private file system: "%s"', $src));
    }
  }

  /**
   * Image path in the body description should be secret.
   *
   * @Then /^The image path in the body description should be secret/
   */
  public function imagePathInBodyDescriptionShouldBeSecret(): void {
    $cssSelector = 'article .card__body .body-text img';

    // @todo getSelectorsHandler() is deprecated with version 2.
    $session = $this->getSession();
    $element = $session->getPage()->find(
      'xpath',
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
    );
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $src = $element->getAttribute('src');

    if (!str_contains($src, '/system/file/inline-images')) {
      throw new \InvalidArgumentException(sprintf('The image does not seem to be uploaded in the secret file system: "%s"', $src));
    }
  }

  /**
   * Click on the element with the provided CSS Selector.
   *
   * @param string $text
   *   The text.
   *
   * @When I click admin link :text
   *
   * @throws \Behat\Mink\Exception\DriverException
   */
  public function clickAdminLink(string $text): void {

    $page = $this->getSession()->getPage();
    $admin_span = $page->find('xpath', '//a//span[text()="' . $text . '"]');

    if ($admin_span === NULL) {
      throw new \InvalidArgumentException(sprintf('Cannot find the admin link with text: "%s"', $text));
    }

    $admin_link = $admin_span->getParent();
    $admin_link->click();
  }

  /**
   * Click on the element with the provided CSS Selector.
   *
   * @param int $position
   *   The position.
   * @param string $locator
   *   The locator.
   *
   * @When I click the xth :position link with the text :locator
   */
  public function iClickTheLinkWithText(int $position, string $locator): void {
    $session = $this->getSession();
    $links = $session->getPage()->findAll('named', ['link', $locator]);
    $count = 1;
    foreach ($links as $link) {
      if ($count === $position) {
        // Now click the element.
        $link->click();
        return;
      }
      $count++;
    }
    throw new \InvalidArgumentException(sprintf('Element not found with the locator: "%s"', $locator));
  }

  /**
   * Click on the element with the provided CSS Selector.
   *
   * @param int $position
   *   The position.
   * @param string $css
   *   The CSS Selector.
   *
   * @When I click the xth :position element with the css :css
   */
  public function iClickTheElementWithT(int $position, string $css): void {
    $session = $this->getSession();
    $elements = $session->getPage()->findAll('css', $css);

    $count = 0;

    foreach ($elements as $element) {
      if ($count === $position) {
        // Now click the element.
        $element->click();
        return;
      }
      $count++;
    }
    throw new \InvalidArgumentException(sprintf('Element not found with the css: "%s"', $css));
  }

  /**
   * Click on the element with the provided CSS Selector.
   *
   * @param int $position
   *   The position.
   * @param string $css
   *   The CSS Selector.
   * @param string $region
   *   The region.
   *
   * @When I click the xth :position element with the css :css in the :region( region)
   */
  public function iClickTheRegionElementWithTheCss(int $position, string $css, string $region): void {
    $session = $this->getSession();
    $regionObj = $session->getPage()->find('region', $region);
    $elements = $regionObj->findAll('css', $css);

    $count = 0;

    foreach ($elements as $element) {
      if ($count === $position) {
        // Now click the element.
        $element->click();
        return;
      }
      $count++;
    }
    throw new \InvalidArgumentException(sprintf('Element not found with the css: "%s"', $css));
  }

  /**
   * Click on the element with the provided CSS Selector.
   *
   * @param string $cssSelector
   *   The CSS Selector.
   *
   * @When /^I click the element with css selector "([^"]*)"$/
   */
  public function iClickTheElementWithCssSelector(string $cssSelector): void {
    $session = $this->getSession();
    // @todo getSelectorsHandler() is deprecated with version 2.
    $element = $session->getPage()->find(
      'xpath',
      // Just changed xpath to css.
      $session->getSelectorsHandler()->selectorToXpath('css', $cssSelector)
    );
    if (NULL === $element) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS Selector: "%s"', $cssSelector));
    }

    $element->click();
  }

  /**
   * Click on radio button.
   *
   * @param string $label
   *   The label.
   * @param string $id
   *   The id.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\DriverException
   *
   * @When I click radio button :label with the id :id
   * @When I click radio button :label
   */
  public function clickRadioButton(string $label = '', string $id = ''): void {
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
    $radiobutton = $id ? $element->findById($id) : $element->find('named', ['radio', $escaper->escapeLiteral($label)]);
    if ($radiobutton === NULL) {
      throw new \RuntimeException(sprintf('The radio button with "%s" was not found on the page %s', $id ?: $label, $this->getSession()->getCurrentUrl()));
    }
    $value = $radiobutton->getAttribute('value');
    // Only check the label if we were selecting by ID, otherwise we already
    // found the button by a magic label, and help text may cause the parent
    // of the radio button to have more text than the label, making the
    // following always fail.
    if ($id) {
      $label_on_page = $radiobutton->getParent()->getText();
      if ($label !== '' && $label !== $label_on_page) {
        throw new \RuntimeException(sprintf("Button with id '%s' has label '%s' instead of '%s' on the page %s", $id, $label_on_page, $label, $this->getSession()
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
  public function showHiddenButton(): void {
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
  public function showHiddenCheckbox(): void {
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
  public function showHiddenInputs(): void {
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
   * @param string $username
   *   The username.
   *
   * @Given /^(?:|I )am on the profile of "(?P<username>[^"]+)"$/
   * @When /^(?:|I )go to the profile of "(?P<username>[^"]+)"$/
   */
  public function openProfileOf(string $username): void {
    $account = user_load_by_name($username);
    if ($account->id() !== 0) {
      $account_uid = $account->id();
    }
    else {
      throw new \RuntimeException(sprintf("User with username '%s' does not exist.", $username));
    }
    $page = '/user/' . $account_uid;

    $this->visitPath($page);
  }

  /**
   * Should see a field with a specific label.
   *
   * @param string $textBefore
   *   The text before.
   * @param string $textAfter
   *   The text after.
   * @param string $cssQuery
   *   The css query.
   *
   * @Then :textBefore should precede :textAfter for the query :cssQuery
   */
  public function shouldPrecedeForTheQuery(string $textBefore, string $textAfter, string $cssQuery): void {
    $elements = $this->getSession()->getPage()->findAll('css', $cssQuery);

    $items = array_map(
      static function ($element) {
        return $element->getText();
      },
      $elements
    );
    $checkBefore = $textBefore;
    $checkAfter = $textAfter;

    foreach ($items as $item) {
      if (str_contains($item, $textBefore)) {
        $checkBefore = $item;
      }
      elseif (str_contains($item, $textAfter)) {
        $checkAfter = $item;
      }
    }

    Assert::assertGreaterThan(
      array_search($checkBefore, $items, FALSE),
      array_search($checkAfter, $items, FALSE),
      "$textBefore does not proceed $textAfter"
    );
  }

  /**
   * Hook into user creation to add profile fields `@afterUserCreate`.
   *
   * @throws \Exception
   *
   * @afterUserCreate
   */
  public function alterUserParameters(EntityScope $event): void {
    $account = $event->getEntity();
    // Get profile of current user.
    if (!empty($account->uid)) {
      $user_account = \Drupal::entityTypeManager()->getStorage('user')->load($account->uid);
      $storage = \Drupal::entityTypeManager()->getStorage('profile');
      if ($storage !== NULL) {
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
   * Get the newest group id from title.
   *
   * @param string $group_title
   *   The group title.
   * @param string $mail
   *   The mail.
   *
   * @return int|null
   *   The group id or null.
   */
  public function getGroupContentIdFromGroupTitle(string $group_title, string $mail): ?int {

    $properties = [
      'gid' => $this->getNewestGroupIdFromTitle($group_title),
      'invitation_status' => 0,
      'invitee_mail' => $mail,
    ];
    $loader = \Drupal::service('ginvite.invitation_loader');
    $invitations = $loader->loadByProperties($properties);

    if ($invitations > 0) {
      $invitation = reset($invitations);

      if ($invitation instanceof GroupInvitationWrapper) {
        return $invitation->getGroupRelationship()->id();
      }
    }

    return NULL;
  }

  /**
   * Opens specified node page of type and with title.
   *
   * @param string $type
   *   The node type.
   * @param string $title
   *   The node title.
   *
   * @Given /^(?:|I )open the "(?P<type>[^"]+)" node with title "(?P<title>[^"]+)"$/
   * @When /^(?:|I )go the  "(?P<type>[^"]+)" node with title "(?P<title>[^"]+)"$/
   */
  public function openNodeWithTitle(string $type, string $title): void {
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
        throw new \RuntimeException(sprintf("Multiple nodes of type '%s' with title '%s' found.", $type, $title));
      }

      throw new \RuntimeException(sprintf("Node of type '%s' with title '%s' does not exist.", $type, $title));
    }
  }

  /**
   * Opens register page with destination to invited group.
   *
   * @param string $mail
   *   The mail.
   * @param string $group_title
   *   The group title.
   *
   * @Given /^(?:|I )open register page with prefilled "(?P<mail>[^"]+)" and destination to invited group "(?P<group_title>[^"]+)"$/
   */
  public function openRegisterPageDestinationGroup(string $mail, string $group_title): void {
    $group_content_id = $this->getGroupContentIdFromGroupTitle($group_title, $mail);
    $mail_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($mail));
    $page = '/user/register?invitee_mail=' . $mail_encoded . '&destination=/social-group-invite/' . $group_content_id . '/accept';

    $this->visitPath($page);
  }

  /**
   * Opens register page with destination to invited node.
   *
   * @param string $mail
   *   The mail.
   * @param string $node_title
   *   The node title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @Given /^(?:|I )open register page with prefilled "(?P<mail>[^"]+)" and destination to invited node "(?P<node_title>[^"]+)"$/
   */
  public function openRegisterPageDestinationNode(string $mail, string $node_title): void {
    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['title' => $node_title]);
    $node = reset($nodes);

    $mail_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($mail));
    $page = '/user/register?invitee_mail=' . $mail_encoded . '&destination=/node/' . $node->id();

    $this->visitPath($page);
  }

  /**
   * Stores the user's name in $this->intended_user_names.
   *
   * This goes before a register form manipulation and submission.
   *
   * @param string $name
   *   The name of the user.
   *
   * @Given I intend to create a user named :name
   *
   * @see cleanUsers()
   */
  public function intendUserName(string $name): void {
    $this->intendedUserNames[] = $name;
  }

  /**
   * Checks if correct amount of uploaded files by user are private.
   *
   * @param string $username
   *   The username.
   * @param string $private
   *   The private files.
   * @param string $public
   *   The public files.
   *
   * @Then /User "(?P<username>[^"]+)" should have uploaded "(?P<private>[^"]+)" private files and "(?P<public>[^"]+)" public files$/
   *
   * @throws \Exception
   */
  public function checkFilesPrivateForUser(string $username, string $private, string $public): void {

    $query = \Drupal::entityQuery('user')
      ->condition('name', $username);
    $uid = $query
      ->accessCheck(TRUE)
      ->execute();

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
      throw new \RuntimeException(sprintf("User '%s' does not exist.", $username));
    }
  }

  /**
   * Opens the files uploaded by a given user.
   *
   * @param string $username
   *   The username.
   * @param string $access
   *   The access.
   *
   * @throws \Exception
   *
   * @Then /I open and check the access of the files uploaded by "(?P<username>[^"]+)" and I expect access "(?P<access>[^"]+)"$/
   */
  public function openAndCheckFilesPrivateForUser(string $username, string $access): void {
    $allowed_access = [
      '0' => 'denied',
      '1' => 'allowed',
    ];
    if (!in_array($access, $allowed_access)) {
      throw new \InvalidArgumentException(sprintf('This access option is not allowed: "%s"', $access));
    }
    $expected_access = 0;
    if ($access === 'allowed') {
      $expected_access = 1;
    }

    $query = \Drupal::entityQuery('user')
      ->condition('name', $username);
    $uid = $query
      ->accessCheck(TRUE)
      ->execute();

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
      throw new \RuntimeException(sprintf("User '%s' does not exist.", $username));
    }
  }

  /**
   * This opens the files and check for the expected access.
   *
   * @param int $fid
   *   The file id.
   * @param int $expected_access
   *   0 = NO access, 1 = YES access.
   *
   * @throws \Behat\Mink\Exception\ResponseTextException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function openFileAndExpectAccess(int $fid, int $expected_access): void {
    /** @var \Drupal\file\Entity\File $file */
    $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
    $url = $file->createFileUrl();
    $page = \Drupal::service('file_url_generator')->transformRelative($url);
    $this->visitPath($page);

    if ($expected_access === 0) {
      $this->assertSession()->pageTextContains('Access denied. You must log in to view this page.');
    }
    else {
      $this->assertSession()->pageTextNotContains('Access denied. You must log in to view this page.');
    }
  }

  /**
   * Log out.
   *
   * Until https://github.com/jhedstrom/drupalextension/issues/641
   *
   * @afterScenario
   *
   * @Given /^(?:|I )logout$/
   */
  public function iLogOut(): void {
    // Go to log out page.
    $page = '/user/logout';
    $this->visitPath($page);

    // Since Drupal 10.3 logout is redirect to confirm if is missing token.
    // I check if user is in confirmation page.
    $session = $this->getSession();
    // Remove query string to avoid check destination parameter.
    $url = strtok($session->getCurrentUrl(), '?');
    if (!str_contains($url, '/user/logout/confirm')) {
      return;
    }

    // I found the confirmation form and the submit button to confirm.
    $locator = 'form.user-logout-confirm button';
    $element = $session->getPage()->find('css', $locator);
    if ($element === NULL) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate ID selector: "%s"', $locator));
    }
    $element->click();
  }

  /**
   * Close the open tip.
   *
   * @When I close the open tip
   */
  public function iCloseTheOpenTip(): void {
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
  public function turnOffTranslationsImport(): void {
    // Let's disable translation.path for now.
    \Drupal::configFactory()->getEditable('locale.settings')->set('translation.import_enabled', FALSE)->save();
  }

  /**
   * Translate a string.
   *
   * @param string $source
   *   The source string.
   * @param string $translation
   *   The translated string.
   * @param string $lang_code
   *   The language code.
   *
   * @Given /I translate "(?P<source>[^"]+)" to "(?P<translation>[^"]+)" for "(?P<lang_code>[^"]+)"$/
   *
   * @throws \Drupal\locale\StringStorageException
   */
  public function iTranslate(string $source, string $translation, string $lang_code): void {
    $this->addTranslation($source, $translation, $lang_code);
  }

  /**
   * Helper function to add translation.
   *
   * @param string $source_string
   *   The source string.
   * @param string $translated_string
   *   The translated string.
   * @param string $lang_code
   *   The language code.
   *
   * @return mixed
   *   The translation object.
   *
   * @throws \Drupal\locale\StringStorageException
   */
  public function addTranslation(string $source_string, string $translated_string, string $lang_code): mixed {
    // Find existing source string.
    $storage = \Drupal::service('locale.storage');
    $string = $storage->findString(['source' => $source_string]);
    if (is_null($string)) {
      $string = new SourceString();
      $string->setString($source_string);
      $string->setStorage($storage);
      $string->save();
    }

    // Create translation. If one already exists, it will be replaced.
    return $storage->createTranslation([
      'lid' => $string->lid,
      'language' => $lang_code,
      'translation' => $translated_string,
    ])->save();
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
  public function fillAutocompleteField(string $field, string $text, string $item, bool $next = FALSE): void {
    $element = $this->getSession()->getPage()->findField($field);

    if (NULL === $element) {
      throw new \RuntimeException(sprintf('Field %s not found', $field));
    }

    if ($next) {
      $text = $element->getValue() . ', ' . $text;
    }

    $element->setValue($text);
    $element->keyDown(' ');
    // Wait timeout before sending an AJAX request.
    sleep(1);
    $this->minkContext->iWaitForAjaxToFinish();
    $id = $element->getAttribute('id');
    $index = $this->getSession()->evaluateScript('return jQuery(".ui-autocomplete-input").index(jQuery("#' . $id . '"));');
    $autocomplete = $this->getSession()->getPage()->find('xpath', '//ul[contains(@class, "ui-autocomplete")][' . ($index + 1) . ']');

    if (NULL === $autocomplete) {
      throw new \RuntimeException('Could not find the autocomplete popup box');
    }

    $popup_element = $autocomplete->find('xpath', "//li[text()='$item']");

    // If "li" was not found, try to find "a" inside a "li".
    if (NULL === $popup_element) {
      $popup_element = $autocomplete->find('xpath', "//li/a[text()='$item']");
    }

    // If "li" was not found, try to find "div" inside a "li".
    if (NULL === $popup_element) {
      $popup_element = $autocomplete->find('xpath', "//li/div[text()='$item']");
    }

    if (NULL === $popup_element) {
      throw new \RuntimeException(sprintf('Could not find autocomplete item with text %s', $item));
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
   * Fill in a select value.
   *
   * @param string $field
   *   The field identifier.
   * @param string $text
   *   The typed text in field.
   * @param string $item
   *   The item for drop-down list.
   *
   * @Given I fill in :field with :text and select :item
   *
   * @throws \Exception
   */
  public function iFillInWithAndSelect(string $field, string $text, string $item): void {
    $this->fillAutocompleteField($field, $text, $item);
  }

  /**
   * Fill autocomplete field and select next value.
   *
   * @param string $field
   *   The field identifier.
   * @param string $text
   *   The typed text in field.
   * @param string $item
   *   The item for drop-down list.
   *
   * @Given I fill next in :field with :text and select :item
   *
   * @throws \Exception
   */
  public function iFillNextInWithAndSelect(string $field, string $text, string $item): void {
    $this->fillAutocompleteField($field, $text, $item, TRUE);
  }

  /**
   * Checks if the element with the given CSS selector is visible.
   *
   * @param string $link_name
   *   The name of the link to click.
   * @param string $row_text
   *   The text to search for in the row.
   *
   * @When /^I click "([^"]*)" on the row containing "([^"]*)"$/
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function iClickOnOnTheRowContaining(string $link_name, string $row_text): void {
    /** @var \Behat\Mink\Element\NodeElement $row */
    $row = $this->getSession()->getPage()->find('css', sprintf('table tr:contains("%s")', $row_text));
    if (!$row) {
      throw new \RuntimeException(sprintf('Cannot find any row on the page containing the text "%s"', $row_text));
    }

    $row->clickLink($link_name);
  }

  /**
   * Expand a details area.
   *
   * @When I expand the :label section
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function iExpandDetailsSection(string $label) : void {
    $elements = array_filter(
      $this->getSession()->getPage()->findAll("css", "summary"),
      static fn (NodeElement $el) => str_contains($el->getText(), $label),
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

    // Expand the detail element.
    $summary->click();
  }

  /**
   * Checks that the URL of an image with a certain alt-text is loaded.
   *
   * @param string $alt
   *   The alt attribute of the image to find.
   *
   * @Then the image :title should be loaded
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function imageShouldBeLoaded(string $alt) : void {
    $session = $this->getSession();
    $locator = "img[alt=\"$alt\"]";
    $img = $session->getPage()->find('css', $locator);
    if ($img === NULL) {
      throw new ElementNotFoundException($session->getDriver(), "img", "css", $locator);
    }

    $src = $img->getAttribute("src");
    if ($src === NULL) {
      throw new \RuntimeException("Image with alt '$alt' has no 'src' attribute.");
    }

    // Load the image in a new session to not disrupt our current test.
    $img_session = new Session($session->getDriver());
    $img_session->visit($this->locatePath($src));
    $img_status_code = $img_session->getStatusCode();
    $img_session->stop();
    if ($img_status_code !== 200) {
      throw new \RuntimeException("Loaded image at '$src', expected status code 200 but got $img_status_code");
    }
  }

}
