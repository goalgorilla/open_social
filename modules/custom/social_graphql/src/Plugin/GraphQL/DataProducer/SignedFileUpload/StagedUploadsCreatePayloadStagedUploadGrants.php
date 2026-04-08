<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\Payload\StagedUploadsCreatePayload;

/**
 * Returns staged upload grants from the mutation payload.
 *
 * @DataProducer(
 *   id = "staged_uploads_create_payload_staged_upload_grants",
 *   name = @Translation("Staged uploads create payload staged upload grants"),
 *   description = @Translation("Returns the list of staged upload grants from the payload."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("Staged upload grants"),
 *     required = FALSE
 *   ),
 *   consumes = {
 *     "payload" = @ContextDefinition("any",
 *       label = @Translation("Staged uploads create payload"),
 *       required = TRUE
 *     )
 *   }
 * )
 */
class StagedUploadsCreatePayloadStagedUploadGrants extends DataProducerPluginBase {

  /**
   * Returns upload session grants when the mutation created them.
   *
   * @param \Drupal\social_graphql\Wrappers\Payload\StagedUploadsCreatePayload $payload
   *   The mutation payload (GraphQL parent value).
   *
   * @return \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant[]|null
   *   Grants on success; NULL if validation failed or sessions rolled back.
   */
  public function resolve(StagedUploadsCreatePayload $payload): ?array {
    return $payload->getStagedUploadGrants();
  }

}
