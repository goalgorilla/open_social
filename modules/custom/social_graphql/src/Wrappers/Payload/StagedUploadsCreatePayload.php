<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Wrappers\Payload;

use Drupal\social_graphql\GraphQL\Payload\Payload;

/**
 * The staged uploads create payload.
 */
class StagedUploadsCreatePayload extends Payload {

  /**
   * Staged upload grants when the mutation succeeds.
   *
   * @var \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant[]|null
   */
  protected ?array $stagedUploadGrants = NULL;

  /**
   * Stores the list of grants from a successful mutation.
   *
   * @param \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant[] $stagedUploadGrants
   *   Grant wrappers including target and session credentials.
   *
   * @return $this
   */
  public function setStagedUploadGrants(array $stagedUploadGrants): self {
    $this->stagedUploadGrants = $stagedUploadGrants;
    return $this;
  }

  /**
   * Gets staged upload grants when present.
   *
   * @return \Drupal\social_graphql\Wrappers\SignedFileUpload\StagedUploadGrant[]|null
   *   Grant wrappers, or NULL when the mutation did not produce grants.
   */
  public function getStagedUploadGrants(): ?array {
    return $this->stagedUploadGrants;
  }

}
