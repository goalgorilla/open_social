<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\MinkContext;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupRelationshipType;

/**
 * Defines test steps around the usage of groups.
 *
 * It's important to note that this means Open Social (flexible) groups and does
 * not consider other types of objects built on the group module.
 */
class GroupContext extends RawMinkContext {

  use EntityTrait;
  use GroupTrait;

  private const CREATE_PAGE = "/group/add/flexible_group";

  /**
   * Keep track of all groups that are created, so they can easily be removed.
   */
  private array $groups = [];

  /**
   * Keep track of the last created group so that it can be validated.
   */
  private ?array $lastCreatedValues = NULL;

  /**
   * The Drupal mink context is useful for validation of content.
   */
  private MinkContext $minkContext;

  /**
   * Provide help filling in the WYSIWYG editor.
   */
  private CKEditorContext $cKEditorContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->cKEditorContext = $environment->getContext(CKEditorContext::class);
    $this->minkContext = $environment->getContext(SocialMinkContext::class);
  }

  /**
   * View the group creation page.
   *
   * @When /^(?:|I )view the group creation page$/
   */
  public function whenIViewTheGroupCreationPage() : void {
    $this->visitPath(self::CREATE_PAGE);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Fill out the group creation form and submit.
   *
   * The order of the fields may matter since some fields depend on other
   * fields, e.g. content visibility or join methods depend on group visibility
   * and address sub fields depend on country.
   *
   * Example: When I create a flexible group using its creation page:
   *              | Title       | My Book Page  |
   *              | Description | It's the best |
   *
   * @When I create a :group_type group using its creation page:
   * @When create a :group_type group using its creation page:
   */
  public function whenICreateAGroupUsingTheForm(string $group_type, TableNode $fields): void {
    $this->visitPath("/group/add/flexible_group");
    if ($this->getSession()->getStatusCode() !== 200) {
      throw new \RuntimeException("Could not go to `/group/add/flexible_group` page.");
    }

    $page = $this->getSession()->getPage();

    $group = ['type' => $group_type];
    foreach ($fields->getRowsHash() as $field => $value) {
      $key = strtolower($field);
      $group[$key] = $value;

      // We must be more specific for the title field since there could be more
      // than one on the page (e.g. the menu title).
      if ($key === "title") {
        $fieldset = $page
          ->find("named", ["fieldset", "Basic information"]);

        if ($fieldset === NULL) {
          throw new ElementNotFoundException($this->getSession()->getDriver(), "fieldset", "named", "Basic Information");
        }

        $fieldset->fillField($field, $value);
      }
      // For the description we're using CKEditor so we must fill in the editor
      // rather than the hidden form field.
      // @todo Not being able to click the label shows an a11y issue.
      elseif ($key === "description") {
        $this->cKEditorContext->iFillInTheWysiwygEditor($field, $value);
      }
      elseif ($key === "group visibility" && $group_type === "flexible") {
        $page->selectFieldOption("field_flexible_group_visibility", $value);
      }
      elseif ($key === "join method" && $group_type === "flexible") {
        $page->selectFieldOption("field_group_allowed_join_method", $value);
      }
      elseif ($key === "country") {
        $page->selectFieldOption($field, $value);
      }
      else {
        $page->fillField($field, $value);
      }

      // Wait until the address form has changed due to country selection before
      // continuing. We assume the "City" is present for any country.
      if ($key === "country") {
        $ajax_timeout = $this->getMinkParameter('ajax_timeout');
        if (!$this->getSession()->getDriver()->wait(1000 * $ajax_timeout, "document.querySelectorAll('[name=\"field_group_address[0][address][locality]\"').length > 0")) {
          throw new \RuntimeException("Address field did not update within $ajax_timeout seconds after country selection.");
        }
      }
    }

    // Submit the page.
    $page->pressButton("Save");

    // Keep track of the group we just created so that we can delete it after
    // the test but also so that we can validate what things are in there.
    $group_id = $this->getNewestGroupIdFromTitle($group['title']);
    if ($group_id === NULL) {
      throw new \RuntimeException("Could not find created group by title, perhaps creation failed or there are multiple groups with the same title.");
    }

    $this->lastCreatedValues = $group;

    $created_group = Group::load($group_id);
    assert($created_group instanceof Group);
    $this->groups[$group_id] = $created_group;
  }

  /**
   * Check that a book that was just created is properly shown.
   *
   * @Then I should see the group I just created
   * @Then should see the group I just created
   */
  public function thenIShouldSeeTheGroupIJustCreated() : void {
    $regions = [
      'title' => "Hero block",
      'description' => 'Main content',
    ];

    $this->minkContext->assertPageContainsText("Group {$this->lastCreatedValues['title']} has been created.");

    foreach ($this->lastCreatedValues as $field => $value) {
      if (isset($regions[$field])) {
        $this->minkContext->assertRegionText($value, $regions[$field]);
      }
      elseif ($field === "type") {
        $group_type_label = [
          'public' => 'Public group',
          'open' => 'Open group',
          'closed' => 'Closed group',
          'flexible' => 'Flexible group',
        ][$value];
        $this->minkContext->assertRegionText($group_type_label, "Hero block");
      }
      elseif ($field === "group visibility") {
        @trigger_error("The 'group visibility' field is not accessibly displayed and can not be tested.", E_USER_WARNING);
      }
      elseif ($field === "join method") {
        @trigger_error("The 'join method' field is not accessibly displayed and can not be tested.", E_USER_WARNING);
      }
      else {
        $this->minkContext->assertPageContainsText($value);
      }
    }
  }

  /**
   * Selects a group in a dropdown.
   *
   * @When I select group :group
   */
  public function iSelectGroup($group) {
    if ($group === "- None -") {
      $option = '_none';
    }

    if ($group !== "- None -") {
      $option = $this->getNewestGroupIdFromTitle($group);
    }

    if (!$option) {
      throw new \InvalidArgumentException(sprintf('Could not find group for "%s"', $group));
    }

    $this->getSession()->getPage()->selectFieldOption('edit-groups', $option);

  }

  /**
   * Clicks the group member dropdown.
   *
   * @When /^I click the group member dropdown/
   */
  public function iClickGroupMemberDropdown() {
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
   * Open the group overview.
   *
   * @When I am viewing the groups overview
   * @When am viewing the groups overview
   */
  public function viewGroupOverview() : void {
    $this->visitPath("/all-groups");
  }

  /**
   * Open the group on its default page.
   *
   * @When I am viewing the group :group
   * @When am viewing the group :group
   */
  public function viewingGroup(string $group) : void {
    $group_id = $this->getNewestGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \RuntimeException("Group '$group' does not exist.");
    }
    $this->visitPath("/group/$group_id");
  }

  /**
   * Open the quick join link directly.
   *
   * @When I visit the group quick join link for :group
   * @When am visiting the group quick join link for :group
   */
  public function visitGroupQuickJoin(string $group) : void {
    $group_id = $this->getNewestGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \RuntimeException("Group '$group' does not exist.");
    }

    $this->visitPath("/group/$group_id/quickjoin");
  }

  /**
   * Assert we're on the group page.
   *
   * Can be used to check that a redirect was implemented correctly.
   *
   * @Then I should be viewing the group :group
   * @Then should be viewing the group :group
   */
  public function shouldBeViewingGroup(string $group) : void {
    $group_id = $this->getNewestGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \RuntimeException("Group '$group' does not exist.");
    }
    $this->assertSession()->statusCodeEquals(200);
    // We may need to change the path here since the default group page is
    // configurable.
    $this->assertSession()->addressEquals("/group/$group_id/about");
  }

  /**
   * Edit a specific group.
   *
   * This mirrors editingTopic even though we already have viewPageInGroup which
   * could be used. A little duplication makes easier test writing.
   *
   * @When I am editing the group :group
   * @When am editing the group :group
   */
  public function editingGroup(string $group) : void {
    $this->viewPageInGroup("edit", $group);
  }

  /**
   * Open the group on a specific page.
   *
   * @When I am viewing the :group_page page of group :group
   * @When am viewing the :group_page page of group :group
   */
  public function viewPageInGroup(string $group_page, string $group) : void {
    $group_id = $this->getNewestGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \RuntimeException("Group '$group' does not exist.");
    }
    $this->visitPath("/group/$group_id/$group_page");
  }

  /**
   * Check that a form has a specific group preselected.
   *
   * @Then /^the group "(?P<group>[^"]+)" should be preselected$/
   */
  public function groupIsPreselected(string $group) : void {
    $field = $this->getSession()->getPage()->findField('Group');
    if ($field === NULL) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), "select", NULL, "Group");
    }

    $group_id = $this->getNewestGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \RuntimeException("Group '$group' does not exist.");
    }

    $selected = $field->getValue();
    if ($selected !== (string) $group_id) {
      throw new \RuntimeException("Expected group select to be set to '$group_id' but instead found '$selected'.");
    }
  }

  /**
   * Check that a specific group is selectable in the group selector.
   *
   * @Then I should be able to select the group :group
   * @Then should be able to select the group :group
   */
  public function shouldBeAbleToSelectGroup(string $group) : void {
    $field = $this->getSession()->getPage()->findField('Group');
    if ($field === NULL) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), "select", NULL, "Group");
    }

    $group_id = $this->getNewestGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \RuntimeException("Group '$group' does not exist.");
    }

    $option = $field->find('named', ['option', $group_id]);
    if ($option === NULL) {
      throw new \RuntimeException("Expected '$group' to be an option for the group selector but it was not.");
    }
  }

  /**
   * Check that a specific group is not selectable in the group selector.
   *
   * @Then I should not be able to select the group :group
   * @Then should not be able to select the group :group
   */
  public function shouldNotBeAbleToSelectGroup(string $group) : void {
    $field = $this->getSession()->getPage()->findField('Group');
    if ($field === NULL) {
      throw new ElementNotFoundException($this->getSession()->getDriver(), "select", NULL, "Group");
    }

    $group_id = $this->getNewestGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \RuntimeException("Group '$group' does not exist.");
    }

    $option = $field->find('named', ['option', $group_id]);
    if ($option !== NULL) {
      throw new \RuntimeException("Expected '$group' not to be an option for the group selector but it was.");
    }
  }

  /**
   * Opens group stream page.
   *
   * @Given /^(?:|I )am on the stream of group "(?P<group_title>[^"]+)"$/
   * @When /^(?:|I )go to the stream of group "(?P<group_title>[^"]+)"$/
   */
  public function openGroupStreamPage($group_title) {
    $group_id = $this->getNewestGroupIdFromTitle($group_title);
    if ($group_id === NULL) {
      throw new \InvalidArgumentException("Group '$group_title' does not exist.");
    }
    $page = '/group/' . $group_id . '/stream';

    $this->visitPath($page);
  }

  /**
   * Opens the content from a group and check for access.
   *
   * @Then /I open and check the access of content in group "(?P<groupname>[^"]+)" and I expect access "(?P<access>[^"]+)"$/
   */
  public function openAndCheckGroupContentAccess($groupname, $access) {
    $allowed_access = [
      '0' => 'denied',
      '1' => 'allowed',
    ];
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
        $group_content_types = GroupRelationshipType::loadByEntityTypeId('node');
        $group_content_types = array_keys($group_content_types);

        // Get all the node's related to the current group.
        $query = \Drupal::database()->select('group_relationship_field_data', 'gcfd');
        $query->addField('gcfd', 'entity_id');
        $query->condition('gcfd.gid', $group->id());
        $query->condition('gcfd.type', $group_content_types, 'IN');
        $query->execute()->fetchAll();

        $nodes = $query->execute()->fetchAllAssoc('entity_id');
        foreach (array_keys($nodes) as $key => $entity_id) {
          $this->openEntityAndExpectAccess('node', $entity_id, $expected_access);
        }

        // Get all the posts from this group.
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
        throw new \RuntimeException(sprintf("Group '%s' does not exist.", $groupname));
      }
      if (count($gid) > 1) {
        throw new \RuntimeException(sprintf("Multiple groups with label '%s' found.", $groupname));
      }
    }
  }

  /**
   * View the form for a specific group type.
   *
   * @param string $group_type
   *   The group type (i.e. open, closed, secret, or flexible).
   *
   * @When I visit the :group_type group create form
   */
  public function iVisitTheGroupCreateForm(string $group_type) : void {
    $this->visitPath("/group/add/{$group_type}_group");
  }

  /**
   * This opens the entity and check for the expected access.
   *
   * @param string $entity_type
   *   The entity type to check.
   * @param int $entity_id
   *   The id of the entity.
   * @param int $expected_access
   *   The expected access:
   *     0 = NO access
   *     1 = YES access.
   */
  private function openEntityAndExpectAccess($entity_type, $entity_id, $expected_access) {
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

}
