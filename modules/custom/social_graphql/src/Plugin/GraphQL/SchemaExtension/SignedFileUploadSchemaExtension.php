<?php

declare(strict_types=1);

namespace Drupal\social_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\GraphQL\ResolverBuilder;

/**
 * Adds signed file upload creation.
 *
 * @SchemaExtension(
 *   id = "signed_file_upload_schema_extension",
 *   name = "Signed File Upload Schema Extension",
 *   description = "Staged file upload requests for Open Social GraphQL.",
 *   schema = "open_social"
 * )
 */
class SignedFileUploadSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry) : void {
    $builder = new ResolverBuilder();

    $registry->addFieldResolver('Mutation', 'stagedUploadsCreate',
      $builder->compose(
        $builder->produce("staged_uploads_create_input")
          ->map('input', $builder->fromArgument('input'))
          ->map('schema', $builder->schema()),
        $builder->produce("staged_uploads_create")
          ->map('input', $builder->fromParent())
      )
    );
    $registry->addFieldResolver('StagedUploadsCreatePayload', 'stagedUploadGrants',
      $builder->produce('staged_uploads_create_payload_staged_upload_grants')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('Query', 'stagedUploadTargetDetails',
      $builder->produce('query_staged_uploads_target_details')
        ->map('target', $builder->fromArgument('target'))
    );

    $registry->addFieldResolver('StagedUploadGrant', 'target',
      $builder->produce('staged_upload_grant_target')
        ->map('grant', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadGrant', 'filename',
      $builder->produce('staged_upload_grant_filename')
        ->map('grant', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadGrant', 'fileSize',
      $builder->produce('staged_upload_grant_file_size')
        ->map('grant', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadGrant', 'sizeDeferred',
      $builder->produce('staged_upload_grant_size_deferred')
        ->map('grant', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadGrant', 'uploadUrl',
      $builder->produce('staged_upload_grant_upload_url')
        ->map('grant', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadGrant', 'finalizationToken',
      $builder->produce('staged_upload_grant_finalization_token')
        ->map('grant', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadGrant', 'constraints',
      $builder->produce('staged_upload_grant_constraints')
        ->map('grant', $builder->fromParent())
    );

    $registry->addFieldResolver('StagedUploadConstraints', 'maxBytes',
      $builder->produce('staged_upload_constraints_max_bytes')
        ->map('constraints', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadConstraints', 'allowedExtensions',
      $builder->produce('staged_upload_constraints_allowed_extensions')
        ->map('constraints', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadConstraints', 'dimensionsMaximum',
      $builder->produce('staged_upload_constraints_dimensions_maximum')
        ->map('constraints', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadConstraints', 'dimensionsMinimum',
      $builder->produce('staged_upload_constraints_dimensions_minimum')
        ->map('constraints', $builder->fromParent())
    );

    $registry->addFieldResolver('StagedUploadConstraintsDimensions', 'width',
      $builder->produce('staged_upload_constraints_dimensions_width')
        ->map('dimensions', $builder->fromParent())
    );
    $registry->addFieldResolver('StagedUploadConstraintsDimensions', 'height',
      $builder->produce('staged_upload_constraints_dimensions_height')
        ->map('dimensions', $builder->fromParent())
    );
  }

}
