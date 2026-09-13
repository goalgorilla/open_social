<?php

declare(strict_types=1);

namespace Drupal\social_gdpr\TestControlInterface\Action;

use Drupal\data_policy\Entity\DataPolicy;
use Drupal\social_gdpr\TestControlInterface\Success\DataPoliciesCreatedSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoints for data policy creation.
 *
 * Replaces GDPRContext::createDataPolicies().
 */
final class CreateDataPolicies {

  /**
   * Creates multiple data policies from a table; one row per policy.
   *
   * @param list<\Drupal\social_gdpr\TestControlInterface\Dto\CreateDataPolicyRequest> $body
   *   The list of data policies to create.
   *
   * @return \Drupal\social_gdpr\TestControlInterface\Success\DataPoliciesCreatedSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'data_policy_create_bulk',
    patterns: ['data_policies:'],
    type: OperationType::Write,
  )]
  public function create(
    #[Example([
      [
        'name' => 'Terms & Conditions',
        'field_description' => 'No rights in this test',
      ],
    ])]
    array $body,
  ): DataPoliciesCreatedSuccess {
    if ($body === []) {
      throw new BadRequestHttpException('No data policy rows in request body; refusing to return success with zero data policies created.');
    }

    $ids = [];
    foreach ($body as $dataPolicy) {
      $dataPolicyObject = DataPolicy::create($dataPolicy->toValuesArray());
      $violations = $dataPolicyObject->validate();
      if ($violations->count() !== 0) {
        $messages = [];
        foreach ($violations as $violation) {
          $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
        }
        throw new BadRequestHttpException('The data policy you tried to create is invalid: ' . implode("\n", $messages));
      }
      $dataPolicyObject->save();
      $ids[] = (int) $dataPolicyObject->id();
    }

    return new DataPoliciesCreatedSuccess(ids: $ids);
  }

}
