<?php

declare(strict_types=1);

namespace Drupal\social\Behat;

use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\data_policy\Entity\DataPolicy;

/**
 * Defines test steps around the usage of the GPDR module.
 */
class GDPRContext extends RawMinkContext {

  /**
   * Keep track of the data policies that were created.
   *
   * This allows us to clean up at the end of the scenario. The array contains
   * the ID if we already have it in the step or the title otherwise. We avoid
   * looking up the topic because a user may be testing an error state.
   *
   * @var array<int|string>
   */
  private array $created = [];

  /**
   * Create multiple data policies at the start of a test.
   *
   * Creates data policies provided in the form:
   * | name               | field_description       | revision_log_message |
   * | Terms & Conditions | No rights in this test  |                      |
   * | ...                | ...                     | ...                  |
   *
   * @Given data_policies:
   */
  public function createDataPolicies(TableNode $dataPoliciesTable) : void {
    foreach ($dataPoliciesTable->getHash() as $dataPolicyHash) {
      $dataPolicy = $this->dataPolicyCreate($dataPolicyHash);
      $this->created[] = $dataPolicy->id();
    }
  }

  /**
   * Set the GDPR consent text confguration.
   *
   * @Given /^(?:|I )set the GDPR Consent Text to "(?P<text>[^"]+)"$/
   */
  public function setGdprContsentText(string $text) {
    $config = \Drupal::configFactory()
      ->getEditable('data_policy.data_policy');

    if ($config->isNew()) {
      throw new \Exception("The data_policy.data_policy configuration did not yet exist, is the social_Gdpr module enabled?");
    }

    $config->set('consent_text', $text)->save();
  }

  /**
   * Create a data policy.
   *
   * @return \Drupal\data_policy\Entity\DataPolicy
   *   The data policy values.
   */
  private function dataPolicyCreate($data_policy) : DataPolicy {
    $data_policy_object = DataPolicy::create($data_policy);
    $violations = $data_policy_object->validate();
    if ($violations->count() !== 0) {
      throw new \Exception("The data policy you tried to create is invalid: $violations");
    }
    $data_policy_object->save();

    return $data_policy_object;
  }

}
