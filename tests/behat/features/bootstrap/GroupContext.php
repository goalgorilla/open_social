<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContentType;

/**
 * Defines test steps around the usage of groups.
 */
class GroupContext extends RawMinkContext {
  /**
   * Keep track of all groups that are created so they can easily be removed.
   */
  private array $groups = [];

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
      $groupFields = (object) $groupHash;
      try {
        $group = $this->groupCreate($groupFields);
        $this->groups[$groupFields->title] = $group;
      }
      catch (\Exception $e) {
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
   * @return object
   *   The created group.
   */
  private function groupCreate($group) {

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
   * Get the group from a group title.
   *
   * @param string $group_title
   *   The title of the group.
   *
   * @return int|null
   *   The integer ID of the group or NULL if no group could be found.
   */
  private function getGroupIdFromTitle($group_title) {
    $query = \Drupal::entityQuery('group')
      ->accessCheck(FALSE)
      ->condition('label', $group_title);

    $group_ids = $query->execute();
    $groups = \Drupal::entityTypeManager()->getStorage('group')->loadMultiple($group_ids);

    if (count($groups) !== 1) {
      return NULL;
    }

    $group_id = (int) reset($groups)->id();
    if ($group_id !== 0) {
      return $group_id;
    }

    return NULL;
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
