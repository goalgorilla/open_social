<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

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

  /**
   * Prepares Big Pipe NOJS cookie if needed.
   *
   * Add support for Bigpipe in Behat tests.
   *
   * Original PR here:
   * https://github.com/jhedstrom/drupalextension/pull/325
   *
   * @BeforeScenario
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
   * @beforeScenario @api
   */
  public function bootstrapWithAdminUser(BeforeScenarioScope $scope) {
    $admin_user = User::load('1');
    $current_user = \Drupal::getContainer()->get('current_user');
    $current_user->setAccount($admin_user);
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
        \Drupal::service('path.alias_storage')->save("/node/" . $entity->nid, $node->alias);
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
        'entity_id' => $node->id(),
        'entity_type' => $node->getEntityTypeId(),
        'field_name' => 'field_topic_comments',
        'field_comment_body' => str_replace('[id]', $index, $text),
        'uid' => $node->getOwnerId(),
      ])->save();
    }
  }

  /**
   * @Given Search indexes are up to date
   */
  public function updateSearchIndexes() {
    /** @var \Drupal\search_api\Entity\SearchApiConfigEntityStorage $index_storage */
    $index_storage = \Drupal::service("entity_type.manager")->getStorage('search_api_index');

    $indexes = $index_storage->loadMultiple();
    if (!$indexes) {
      return;
    }

    // Loop over all interfaces and let the Search API index any non-indexed
    // items.
    foreach ($indexes as $index) {
      /** @var \Drupal\search_api\IndexInterface $index */
      $index->indexItems();
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
  }

  /**
   * @Given I reset tour :tour_id
   *
   * @param $tour_id
   */
  public function iResetTour($tour_id)
  {
    $query = \Drupal::database()->delete('users_data');
    $query->condition('module', 'social_tour');
    $query->condition('name', 'social-home');
    $query->execute();
  }

  /**
   * I wait for (seconds) seconds.
   *
   * @When /^(?:|I )wait for "([^"]*)" seconds$/
   */
  public function iWaitForSeconds($seconds, $condition = "") {
    $milliseconds = (int) ($seconds * 1000);
    $this->getSession()->wait($milliseconds, $condition);
  }

  /**
   * I enable the module :module_name.
   *
   * @When /^(?:|I )enable the module "([^"]*)"/
   */
  public function iEnableTheModule($module_name) {
    $modules = [$module_name];
    \Drupal::service('module_installer')->install($modules);
  }

  /**
   * I disable the module :module_name.
   *
   * @When /^(?:|I )disable the module "([^"]*)"/
   */
  public function iDisableTheModule($module_name) {
    $modules = [$module_name];
    \Drupal::service('module_installer')->uninstall($modules);
  }

  /**
   * I enable the tour setting.
   *
   * @When I enable the tour setting
   */
  public function iEnableTheTourSetting() {
    \Drupal::configFactory()->getEditable('social_tour.settings')->set('social_tour_enabled', 1)->save();
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
   * I search :index for :term
   *
   * @When /^(?:|I )search (all|users|groups|content) for "([^"]*)"/
   */
  public function iSearchIndexForTerm($index, $term) {
    $this->getSession()->visit($this->locatePath('/search/' . $index . '/' . urlencode($term)));
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

}
