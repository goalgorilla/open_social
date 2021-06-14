<?php

namespace Drupal\social_graphql\Plugin\GraphQL\SchemaExtension;

use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\social_graphql\GraphQL\StandardisedMutationSchemaTrait;

/**
 * Base class that can be used for Open Social schema extension plugins.
 */
abstract class SchemaExtensionPluginBase extends SdlSchemaExtensionPluginBase {

  use StandardisedMutationSchemaTrait;

}
