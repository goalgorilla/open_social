<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\SignedFileUpload;

use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_graphql\Wrappers\Input\StagedUploadsCreateInput as StagedUploadsCreateInputWrapper;
use GraphQL\Type\Schema;

/**
 * Transforms raw GraphQL input into a validated object.
 *
 * This DataProducer serves as a bridge between the raw input array from the
 * GraphQL request and the typed, validated wrapper class.
 * It handles dependency injection of Drupal services that the wrapper needs.
 *
 * @DataProducer(
 *   id = "staged_uploads_create_input",
 *   name = @Translation("Staged Uploads Create Input Transformer"),
 *   description = @Translation("Transforms raw GraphQL input array into a validated wrapper object with injected dependencies."),
 *   produces = @ContextDefinition("any",
 *     label = @Translation("StagedUploadsInput")
 *   ),
 *   consumes = {
 *     "input" = @ContextDefinition("any",
 *       label = @Translation("Raw input array"),
 *       required = TRUE
 *     ),
 *     "schema" = @ContextDefinition("any",
 *       label = @Translation("Schema from ResolveInfo"),
 *       required = TRUE
 *     ),
 *   }
 * )
 */
class StagedUploadsCreateInput extends DataProducerPluginBase {

  /**
   * Builds the typed input wrapper from raw GraphQL variables.
   *
   * @param array $input
   *   The decoded `input` argument for stagedUploadsCreate (clientMutationId,
   *   requests, etc.).
   * @param \GraphQL\Type\Schema $schema
   *   The schema provided by our Schema resolver. In the future this will be
   *   replaced by ResolveInfo.
   *
   * @return \Drupal\social_graphql\Wrappers\Input\StagedUploadsCreateInput
   *   Input object with setValues() applied; ready for validate().
   */
  public function resolve(array $input, Schema $schema): StagedUploadsCreateInputWrapper {
    $upload_input = new StagedUploadsCreateInputWrapper(
      $schema,
    );
    $upload_input->setValues($input);
    return $upload_input;
  }

}
