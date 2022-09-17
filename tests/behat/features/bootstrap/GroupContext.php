<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContentType;

/**
 * Defines test steps around the usage of groups.
 */
class GroupContext extends RawMinkContext {

  use GroupTrait;

  /**
   * Keep track of all groups that are created so they can easily be removed.
   */
  private array $groups = [];

  /**
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
  }
  /**
   * Create multiple groups at the start of a test.
   *
   * Creates group of a given type provided in the form:
   * | title    | description     | author   | type        | language
   * | My title | My description  | username | open_group  | en
   * | ...      | ...             | ...      | ...         | ...
   *
   * @Given groups:
   */
  public function createGroups(TableNode $groupsTable) {
    foreach ($groupsTable->getHash() as $groupHash) {
      $group = $this->groupCreate($groupHash);
      $this->groups[$group->label()] = $group;
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
      $option = $this->getGroupIdFromTitle($group);
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
   * Open the group on its default page.
   *
   * @When /^(?:|I )am viewing the group "(?P<group>[^"]+)"$/
   */
  public function viewinGroup(string $group) : void {
    $group_id = $this->getGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \Exception("Group '${group}' does not exist.");
    }
    $this->visitPath("/group/${group_id}");
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

    $group_id = $this->getGroupIdFromTitle($group);
    if ($group_id === NULL) {
      throw new \Exception("Group '${group}' does not exist.");
    }

    $selected = $field->getValue();
    if ($selected !== (string) $group_id) {
      throw new \Exception("Expected group select to be set to '$group_id' but instead found '$selected'.");
    }
  }

  /**
   * Opens group stream page.
   *
   * @Given /^(?:|I )am on the stream of group "(?P<group_title>[^"]+)"$/
   * @When /^(?:|I )go to the stream of group "(?P<group_title>[^"]+)"$/
   */
  public function openGroupStreamPage($group_title) {
    $group_id = $this->getGroupIdFromTitle($group_title);
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
        $group_content_types = GroupContentType::loadByEntityTypeId('node');
        $group_content_types = array_keys($group_content_types);

        // Get all the node's related to the current group.
        $query = \Drupal::database()->select('group_content_field_data', 'gcfd');
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
        throw new \Exception(sprintf("Group '%s' does not exist.", $groupname));
      }
      if (count($gid) > 1) {
        throw new \Exception(sprintf("Multiple groups with label '%s' found.", $groupname));
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
   * @param array $group
   *   The values to pass to Group::create. `author` can be set to a username
   *   which will be converted to a uid.
   *
   * @return \Drupal\group\Entity\Group
   *   The created group.
   */
  private function groupCreate(array $group) {
    if (!isset($group['author'])) {
      $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
      $group['uid'] = is_object($current_user) ? $current_user->uid ?? 0 : 0;
    }
    else {
      $account = user_load_by_name($group['author']);
      if ($account->id() !== 0) {
        $group['uid'] = $account->id();
      }
      else {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $group['author']));
      }
    }
    unset($group['author']);

    // Let's create some groups.
    $group_object = Group::create($group);
    $violations = $group_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The group you tried to create is invalid: $violations");
    }
    $group_object->save();

    return $group_object;
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
