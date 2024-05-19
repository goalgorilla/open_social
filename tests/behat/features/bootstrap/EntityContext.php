<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalExtension\Context\DrupalContext;

/**
 * Provides entity scaffolding steps for all entities.
 *
 * Because we use the test bridge to create entities we can abstract the steps
 * to create entities with specific author, anonymous author, or current author.
 */
final class EntityContext extends RawMinkContext {

  /**
   * The Drupal context which gives us access to user management.
   */
  private DrupalContext $drupalContext;

  /**
   * The test bridge that allows running code in the Drupal installation.
   */
  private TestBridgeContext $testBridge;

  /**
   * Map some names for backwards compatibility.
   */
  private array $mapping = [
    'event enrollees' => 'event-enrollees',
  ];

  /**
   * Make some contexts available here so we can delegate steps.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();

    $this->drupalContext = $environment->getContext(SocialDrupalContext::class);
    $this->testBridge = $environment->getContext(TestBridgeContext::class);
  }

  /**
   * Create multiple entities at the start of a test.
   *
   * Creates the entities of the requested type. For example:
   *
   * Given topics:
   *   | author   | title    | body            | field_content_visibility |
   *   | username | My title | My description  | public                   |
   *   | ...      | ...      | ...             | ...                      |
   *
   * Given groups:
   *   | author | title   | description    | author   | type           |
   *   | user-1 | Group 1 | My description | username | flexible_group |
   *   | user-1 | Group 2 | My description | username | flexible_group |
   *   | ...    | ...     | ...            | ...      | ...            |
   *
   * @Given :entity_type:
   */
  public function createEntities(string $entity_type, TableNode $entitiesTable) : void {
    $entity_type = $this->mapping[$entity_type] ?? $entity_type;
    $response = $this->testBridge->command(
      "create-$entity_type",
      ...[$entity_type => $entitiesTable->getHash()],
    );
    $this->assertEntityCreationSuccessful($entity_type, $response);
  }

  /**
   * Create multiple entities at the start of a test.
   *
   * Creates the entities of the requested type. For example:
   *
   * Given topics with non-anonymous author:
   *   | title    | body     | field_content_visibility | field_topic_type |
   *   | My title | My Topic | public                   | News             |
   *   | ...      | ...      | ...                      | ...              |
   *
   * @Given :entity_type with non-anonymous author:
   */
  public function createEntitiesWithAuthor(string $entity_type, TableNode $entitiesTable) : void {
    $entity_type = $this->mapping[$entity_type] ?? $entity_type;
    // Create a new random user to own the content, this ensures the author
    // isn't anonymous.
    // @todo Replace with local random generator.
    $user = [
      'name' => $this->drupalContext->getRandom()->name(8),
      'pass' => $this->drupalContext->getRandom()->name(16),
      'roles' => ["authenticated"],
    ];
    $user['mail'] = "{$user['name']}@example.com";

    $response = $this->testBridge->command(
      "create-users",
      users: [$user]
    );
    $this->assertEntityCreationSuccessful('users', $response);

    $entities = [];
    foreach ($entitiesTable->getHash() as $entityHash) {
      if (isset($entityHash['author'])) {
        throw new \Exception("Can not specify an author when using the '$entity_type with non-anonymous owner:' step, use '$entity_type:' instead.");
      }

      $entityHash['author'] = $user['name'];
      $entities[] = $entityHash;
    }

    $response = $this->testBridge->command(
      "create-$entity_type",
      ...[$entity_type => $entities],
    );
    $this->assertEntityCreationSuccessful($entity_type, $response);
  }

  /**
   * Create multiple entities at the start of a test.
   *
   * Creates the entities of the requested type. For example:
   *
   * Given topics authored by current user:
   *   | title    | body     | field_content_visibility | field_topic_type |
   *   | My title | My Topic | public                   | News             |
   *   | ...      | ...      | ...                      | ...              |
   *
   * @Given :entity_type authored by current user:
   */
  public function createEntitiesAuthoredByCurrentUser(string $entity_type, TableNode $entitiesTable) : void {
    $entity_type = $this->mapping[$entity_type] ?? $entity_type;
    $current_user = $this->drupalContext->getUserManager()->getCurrentUser();
    $entities = [];
    foreach ($entitiesTable->getHash() as $entityHash) {
      if (isset($entityHash['author'])) {
        throw new \Exception("Can not specify an author when using the '$entity_type authored by current user:' step, use '$entity_type:' instead.");
      }

      $entityHash['author'] = (is_object($current_user) ? $current_user->name : NULL) ?? 'anonymous';
      $entities[] = $entityHash;
    }

    $response = $this->testBridge->command(
      "create-$entity_type",
      ...[$entity_type => $entities],
    );
    $this->assertEntityCreationSuccessful($entity_type, $response);
  }

  /**
   * Ensure entity creation did not have any errors.
   *
   * @param array $response
   *   The response provided by the test bridge.
   *
   * @throws \RuntimeException
   *   In case the bridge provided unexpected output.
   * @throws \InvalidArgumentException
   *   In case createion failed due to invalid input.
   */
  private function assertEntityCreationSuccessful(string $entity_type, array $response) : void {
    if (isset($response['status'], $response['error']) && $response['error'] === "Command 'create-$entity_type' not found") {
      throw new \InvalidArgumentException("There's no bridge command registered to create $entity_type. Expected command 'create-$entity_type' to be available.");
    }

    if (!isset($response['created'], $response['errors'])) {
      throw new \RuntimeException("Invalid response from test bridge: " . json_encode($response));
    }

    if ($response['errors'] !== []) {
      throw new \InvalidArgumentException("Could not create all requested entities: \n - " . implode("\n - ", $response['errors']));
    }
  }

}
