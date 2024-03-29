<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Behat\Mink\Element\NodeElement;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\user\Entity\User;
use Drupal\big_pipe\Render\Placeholder\BigPipeStrategy;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Hook\Scope\EntityScope;

/**
 * Provides pre-built step definitions for interacting with Open Social.
 */
class SocialDrupalContext extends DrupalContext {

  use AvoidCleanupTrait;
  use NodeTrait;

  /**
   * Prepares Big Pipe NOJS cookie if needed.
   *
   * Add support for Bigpipe in Behat tests.
   *
   * Original PR here:
   * https://github.com/jhedstrom/drupalextension/pull/325
   *
   * @BeforeScenario @api
   */
  public function prepareBigPipeNoJsCookie(BeforeScenarioScope $scope) {
    // Start a session if not already done.
    // Needed since https://github.com/minkphp/Mink/pull/705
    // Otherwise executeScript or setCookie will throw an error.
    if (!$this->getSession()->isStarted()) {
      $this->getSession()->start();
    }

    try {
      // Check if JavaScript can be executed by Driver.
      $this->getSession()->getDriver()->executeScript('true');
    }
    catch (UnsupportedDriverActionException $e) {
      // Set NOJS cookie.
      if ($this
        ->getSession()) {
        $this
          ->getSession()
          ->setCookie(BigPipeStrategy::NOJS_COOKIE, TRUE);
      }
    }
    catch (\Exception $e) {
      // Mute exceptions.
    }
  }

  /**
   * Call this function before users are created.
   *
   * @beforeUserCreate
   */
  public function beforeUserCreateObject(EntityScope $scope) {
    $user = $scope->getEntity();
    // If we add a user, using the Given users:
    // we can allow it not to have en email. However we use some
    // contrib modules that need an email for hook_user_insert().
    if (!isset($user->mail)) {
      $user->mail = strtolower(trim($user->name)) . '@example.com';
    }
  }

  /**
   * Creates content of the given type for the current user,
   * provided in the form:
   * | title     | My node        |
   * | Field One | My field value |
   * | status    | 1              |
   * | ...       | ...            |
   *
   * @Given I am viewing my :type( content):
   */
  public function assertViewingMyNode($type, TableNode $fields) {

    $user_manager = $this->getUserManager();
    $user = $user_manager->getCurrentUser();
    if (!$user) {
      throw new \Exception(sprintf('There is no current logged in user to create a node for.'));
    }

    $node = (object) array(
      'type' => $type,
    );
    foreach ($fields->getRowsHash() as $field => $value) {
      if (strpos($field, 'date') !== FALSE) {
        $value =  date('Y-m-d H:i:s', strtotime($value));
      }
      $node->{$field} = $value;
    }

    $node->uid = $user->uid;
    $saved = $this->nodeCreate($node);

    // Set internal browser on the node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * @override DrupalContext:assertViewingNode().
   *
   * To support relative dates.
   */
  public function assertViewingNode($type, TableNode $fields) {
    $node = (object) array(
      'type' => $type,
    );
    foreach ($fields->getRowsHash() as $field => $value) {
      if (strpos($field, 'date') !== FALSE) {
        $value = date('Y-m-d H:i:s', strtotime($value));
      }
      $node->{$field} = $value;
    }

    $saved = $this->nodeCreate($node);

    // Set internal browser on the node.
    $this->getSession()->visit($this->locatePath('/node/' . $saved->nid));
  }

  /**
   * @override DrupalContext:createNodes().
   *
   * To support relative dates.
   */
  public function createNodes($type, TableNode $nodesTable) {
    foreach ($nodesTable->getHash() as $nodeHash) {
      $node = (object) $nodeHash;
      $node->type = $type;
      if (isset($node->field_event_date)) {
        $node->field_event_date = date('Y-m-d H:i:s', strtotime($node->field_event_date));
      }
      $entity = $this->nodeCreate($node);
      if (isset($node->alias)) {
        $path_alias = \Drupal::entityTypeManager()->getStorage('path_alias')->create([
          'path' => "/node/" . $entity->nid,
          'alias' => $node->alias,
        ]);
        $path_alias->save();
      }
    }
  }

  /**
   * Creates topics.
   *
   * @Given :count topics with title :title by :username
   */
  public function createTopics($count, $title, $username) {
    /** @var \Drupal\user\UserInterface[] $accounts */
    $accounts = \Drupal::entityTypeManager()->getStorage('user')
      ->loadByProperties(['name' => $username]);

    if (!$accounts) {
      return;
    }

    $account = reset($accounts);

    for ($index = 1; $index <= $count; $index++) {
      $node = (object) [
        'type' => 'topic',
        'title' => str_replace('[id]', $index, $title),
        'uid' => $account->id(),
        'created' => time() + $index,
        'changed' => time() + $index,
      ];

      $this->nodeCreate($node);
    }
  }

  /**
   * Creates comments.
   *
   * @Given :count comments with text :text for :topic
   */
  public function createComments($count, $text, $topic) {
    /** @var \Drupal\node\NodeInterface[] $nodes */
    $nodes = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties(['title' => $topic]);

    if (!$nodes) {
      return;
    }

    $node = reset($nodes);

    if ($node->bundle() !== 'topic') {
      return;
    }

    /** @var \Drupal\comment\CommentStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('comment');

    for ($index = 1; $index <= $count; $index++) {
      $storage->create([
        'status' => 1,
        'entity_id' => $node->id(),
        'entity_type' => $node->getEntityTypeId(),
        'field_name' => 'field_topic_comments',
        'field_comment_body' => str_replace('[id]', $index, $text),
        'uid' => $node->getOwnerId(),
      ])->save();
    }
  }

  /**
   * @When I empty the queue
   */
  public function iEmptyTheQueue() {
    $this->processQueue(TRUE);
  }

  /**
   * @When I wait for the queue to be empty
   */
  public function iWaitForTheQueueToBeEmpty() {
    $this->processQueue();
  }

  /**
   * @When I check if queue items processed :item_name
   *
   * @param $item_name
   */
  public function iCheckIFQueueItemsProcessed($item_name = "") {
    $query = \Drupal::database()->select('queue', 'q');
    $query->addField('q', 'item_id');
    $query->condition('q.name', $item_name);
    $item = $query->execute()->fetchField();

    if (!empty($item)) {
      throw new \Exception('There are exist stuck items in queue.');
    }
  }

  /**
   * Process queue items.
   *
   * @param bool $just_delete
   *   If set to TRUE, it doesn't process the items, but simply deletes them.
   */
  protected function processQueue($just_delete = FALSE) {
    // This step is sometimes called after a cache clear which rebuilds the
    // container and unloads all modules. Normally an HTTP request will ensure
    // all modules are loaded again, but if the cache clear is directly
    // preceding queue processing then that's not the case.
    // Normally this wouldn't even be a problem, but in some tests we have those
    // two steps AND we have something in the queue that calls `renderPlain`
    // (e.g. a message token) which will cause the theme system to balk at
    // unloaded modules. Thus, to fix this we must now make sure all modules
    // are loaded.
    \Drupal::moduleHandler()->loadAll();

    $workerManager = \Drupal::service('plugin.manager.queue_worker');
    /** @var Drupal\Core\Queue\QueueFactory; $queue */
    $queue = \Drupal::service('queue');

    for ($i = 0; $i < 20; $i++) {
      foreach ($workerManager->getDefinitions() as $name => $info) {
        /** @var Drupal\Core\Queue\QueueInterface $worker */
        $worker = $queue->get($name);

        /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
        $queue_worker = $workerManager->createInstance($name);

        if ($worker->numberOfItems() > 0) {
          while ($item = $worker->claimItem()) {
            // If we don't just delete them, process the item first.
            if ($just_delete === FALSE) {
              $queue_worker->processItem($item->data);
            }
            $worker->deleteItem($item);
          }
        }
      }
    }
    if (\Drupal::moduleHandler()->moduleExists('advancedqueue')) {
      $queue_storage = \Drupal::service("entity_type.manager")->getStorage('advancedqueue_queue');
      /** @var \Drupal\advancedqueue\Entity\QueueInterface $queue */
      $queue = $queue_storage->load('default');
      /** @var \Drupal\advancedqueue\Processor $processor */
      $processor = \Drupal::service('advancedqueue.processor');
      $processor->processQueue($queue);
    }
  }

  /**
   * I wait for (seconds) seconds.
   *
   * @When /^(?:|I )wait for "([^"]*)" seconds$/
   */
  public function iWaitForSeconds($seconds, $condition = 'false') {
    $milliseconds = (int) ($seconds * 1000);
    $this->getSession()->wait($milliseconds, $condition);
  }

  /**
   * I enable the nickname field on profiles
   *
   * @When /^(?:|I )enable the nickname field on profiles/
   */
  public function iEnableNicknameField() {
    if (!\Drupal::service('module_handler')->moduleExists("social_profile_fields")) {
      throw new \Exception("Could not enable nickname field for profile because the Social Profile Fields module is disabled.");
    }

    \Drupal::configFactory()->getEditable('social_profile_fields.settings')->set("profile_profile_field_profile_nick_name", TRUE)->save();
  }

  /**
   * I restrict real name usage
   *
   * @When /^(?:|I )(un)?restrict real name usage/
   */
  public function iRestrictRealNameUsage($restrict = TRUE) {
    if (!\Drupal::service('module_handler')->moduleExists("social_profile_privacy")) {
      throw new \Exception("Could not restrict real name usage because the Social Profile Privacy module is disabled.");
    }

    // Convert our negative match to a boolean.
    if ($restrict === "un") {
      $restrict = FALSE;
    }

    // TODO: Remove debug.
    if ($restrict !== FALSE && $restrict !== TRUE) {
      throw  new \Exception("Restrict has unknown value " . print_r($restrict, true));
    }

    \Drupal::configFactory()->getEditable('social_profile_privacy.settings')->set("limit_search_and_mention", $restrict)->save();
  }

  /**
   * Allow platforms that re-use the Open Social platform a chance to fill in
   * custom form fields that are not present in the distribution but may lead to
   * validation errors (e.g. because a field is required).
   *
   * @When /^(?:|I )fill in the custom fields for this "([^"]*)"$/
   */
  public function iFillInCustomFieldsForThis($type) {
    // This method is intentionally left blank. Projects extending Open Social
    // are encouraged to overwrite this method and call the methods that are
    // needed to fill in custom required fields for the used type.
  }

  /**
   * @Given I reset the Open Social install
   */
  public function iResetOpenSocial()
  {
    $schema = \Drupal::database()->schema();
    $tables = $schema->findTables('%');
    if ($tables) {
      foreach ($tables as $key => $table_name) {
        $schema->dropTable($table_name);
      }
    }
  }

  /**
   * @Given I am logged in as :name with the :permissions permission(s)
   */
  public function assertLoggedInWithPermissionsByName($name, $permissions) {
    // Create a temporary role with given permissions.
    $permissions = array_map('trim', explode(',', $permissions));
    $role = $this->getDriver()->roleCreate($permissions);

    $manager = $this->getUserManager();

    // Change internal current user.
    $manager->setCurrentUser($manager->getUser($name));
    $user = $manager->getUser($name);

    // Assign the temporary role with given permissions.
    $this->getDriver()->userAddRole($user, $role);
    $this->roles[] = $role;

    // Login.
    $this->login($user);
  }

  /**
   * I enable that the registered users to be verified immediately.
   *
   * @When I enable that the registered users to be verified immediately
   */
  public function iEnableVerifiedImmediately() {
    \Drupal::configFactory()->getEditable('social_user.settings')->set('verified_immediately', TRUE)->save();
  }

  /**
   * I disable that the registered users to be verified immediately.
   *
   * @When I disable that the registered users to be verified immediately
   */
  public function iDisableVerifiedImmediately() {
    \Drupal::configFactory()->getEditable('social_user.settings')->set('verified_immediately', FALSE)->save();
  }

  /**
   * Task is done.
   *
   * @Then /^task "([^"]*)" is done$/
   */
  public function taskIsDone($text) {
    $doneTask = [
      'Choose language'                        => 'body > div > div > aside > ol > li:nth-child(1)',
      'Verify requirements'                    => 'body > div > div > aside > ol > li:nth-child(2)',
      'Set up database'                        => 'body > div > div > aside > ol > li:nth-child(3)',
      'Select optional modules'                => 'body > div > div > aside > ol > li:nth-child(4)',
      'Install site'                           => 'body > div > div > aside > ol > li:nth-child(5)',
      'Configure site'                         => 'body > div > div > aside > ol > li:nth-child(6)',
    ];

    // En sure we have our task set.
    $task = $this->getSession()->getPage()->findAll('css', $doneTask[$text]);

    if ($task === NULL) {
      throw new \InvalidArgumentException(sprintf('Could not evaluate CSS selector: "%s"', $doneTask[$text]));
    }

    /** @var NodeElement $result */
    foreach ($task as $result) {
      if ($result->hasClass('done')) {
        break;
      }
    }
  }

  /**
   * Wait for the Batch API to finish.
   *
   * Wait until the id="updateprogress" element is gone,
   * or timeout after 30 minutes (1800000 ms).
   *
   * @Given /^I wait for the installer to finish$/
   */
  public function iWaitForTheInstallerBatchJobToFinish() {
    $this->getSession()->wait(1800000, 'jQuery("#updateprogress").length === 0');
  }

  /**
   * Add likes to node at the start of a test with existing users as authors.
   *
   * Creates like provided in the form:
   * | title    | bundle | user      |
   * | My event | event  | Jane Doe  |
   * | ...      | ...    | ...       |
   *
   * @Given likes node:
   */
  public function createNodeLikes(TableNode $nodesTable): void {

    $entity_manager = \Drupal::service('entity_type.manager');
    /** @var \Drupal\votingapi\VoteTypeInterface $vote_type */
    $vote_type = $entity_manager->getStorage('vote_type')->load('like');

    /** @var \Drupal\votingapi\VoteStorageInterface $vote_storage */
    $vote_storage = \Drupal::entityTypeManager()->getStorage('vote');

    foreach ($nodesTable->getHash() as $nodeHash) {
      if (!isset($nodeHash['author'])) {
        throw new \Exception("User is not specified as author when using the 'like node with defined author:' step.");
      }

      $owner = user_load_by_name($nodeHash['author']);
      if ($owner === FALSE) {
        throw new \Exception(sprintf("User with username '%s' does not exist.", $nodeHash['author']));
      }

      $node_id = $this->getNodeIdFromTitle($nodeHash['bundle'], $nodeHash['title']);
      if ($node_id === NULL) {
        throw new \Exception("Node '%s' does not exist.", $nodeHash['title']);
      }

      /** @var \Drupal\votingapi\VoteInterface $vote */
      $vote = $vote_storage->create(['type' => 'like']);
      $vote->setVotedEntityId($node_id);
      $vote->setVotedEntityType('node');
      $vote->setValueType($vote_type->getValueType());
      $vote->setValue(1);
      $vote->setOwnerId($owner->id());
      $vote->save();
    }
  }

  /**
   * I wait for field: (field_name) of type: (field_type) to be rendered.
   *
   * @When /^(?:|I )wait for field: "([^"]*)" of type: "([^"]*)" to be rendered$/
   */
  public function iWaitForTheFieldToBeRendered(string $field_name, string $field_type): void {

    // Type of field accepted to be found.
    $types = [
      'link' => 'a[title',
      'input' => 'input[name',
    ];

    // To avoid loop an infinite loop we create a countable and try to keep
    // this function to be executed in max 60 seconds.
    $check_count = 0;

    // Field query-element of field to be found.
    $condition = sprintf("document.querySelectorAll('%s=\"%s\"]').length > 0", $types[$field_type], $field_name);

    while (!$this->getSession()->getDriver()->wait(500, $condition)) {
      // Each loop will wait 500 milliseconds, so when the countable arrived at
      // 120 probably the script take about 60 seconds and this check will throw
      // an exception with error.
      if ($check_count === 120) {
        throw new \Exception(sprintf("The %s field did not render within 60 seconds.", $field_name));
      }

      $check_count++;
    }
  }

}
