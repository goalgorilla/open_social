<?php

declare(strict_types=1);

namespace Drupal\social_graphql\SignedFileUpload;

use Drupal\signed_file_upload\DataObject\EditorUploadDestination;
use Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination;
use GraphQL\Executor\Values;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Schema;

/**
 * Resolves GraphQL StagedUploadTarget enum values using ENUM_VALUE directives.
 *
 * Each enum case must declare exactly one destination directive in the SDL:
 * stagedFileUploadEntityFieldDestination or stagedFileUploadEditorDestination.
 */
trait StagedUploadTargetEnumDestinationResolverTrait {

  /**
   * Maps a StagedUploadTarget enum name to a typed upload destination.
   *
   * @param \GraphQL\Type\Schema $schema
   *   GraphQL schema with StagedUploadTarget and destination directives.
   * @param string $enumValue
   *   The enum case name submitted by the client (e.g. from mutation input).
   *
   * @return \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination|\Drupal\signed_file_upload\DataObject\EditorUploadDestination
   *   Destination for signed_file_upload constraints and sessions.
   *
   * @throws \RuntimeException
   *   When the schema, enum value, or directive configuration is invalid.
   */
  protected function getDestinationFromEnum(Schema $schema, string $enumValue): EntityFieldUploadDestination|EditorUploadDestination {
    $type = $schema->getType('StagedUploadTarget');
    if (!$type instanceof EnumType) {
      throw new \RuntimeException("Schema type StagedUploadTarget is not an enum.");
    }

    $enumCase = $type->getValue($enumValue);
    if ($enumCase === NULL) {
      throw new \RuntimeException("Unknown enum type for StagedUploadTarget: $enumValue");
    }

    $astNode = $enumCase->astNode;
    if ($astNode === NULL) {
      throw new \RuntimeException('Staged upload target has no schema AST metadata.');
    }

    $entityDirective = $schema->getDirective('stagedFileUploadEntityFieldDestination');
    $editorDirective = $schema->getDirective('stagedFileUploadEditorDestination');
    if ($entityDirective === NULL || $editorDirective === NULL) {
      $which = [];
      if ($entityDirective === NULL) {
        $which[] = 'entity';
      }
      if ($editorDirective === NULL) {
        $which[] = 'editor';
      }
      $name = implode(" and ", $which);
      throw new \RuntimeException("Missing $name destination directive in schema.");
    }

    try {
      $entityArgs = Values::getDirectiveValues($entityDirective, $astNode);
      $editorArgs = Values::getDirectiveValues($editorDirective, $astNode);
    }
    catch (\Throwable $e) {
      throw new \RuntimeException(
        'Invalid staged upload target directive arguments.',
        0,
        $e,
      );
    }

    // Slightly complex if-nesting here to make sure PHPStan understand, but
    // basically requires exactly one of the two.
    $hasEntity = $entityArgs !== NULL;
    $hasEditor = $editorArgs !== NULL;
    if ($hasEntity) {
      if ($hasEditor) {
        throw new \RuntimeException("Staged upload target value '$enumValue' declares both entity-field and editor directives. May only specify one.");
      }

      return $this->buildEntityFieldDestination($entityArgs);
    }
    if (!$hasEditor) {
      throw new \RuntimeException("Staged upload target enum value '$enumValue' has no destination directive.");
    }
    return $this->buildEditorDestination($editorArgs);
  }

  /**
   * Builds entity-field destination from entity directive arguments.
   *
   * @param mixed[] $args
   *   Directive arguments from the enum value AST node.
   *
   * @return \Drupal\signed_file_upload\DataObject\EntityFieldUploadDestination
   *   Destination describing entity type, bundle, and field name.
   */
  private function buildEntityFieldDestination(array $args): EntityFieldUploadDestination {
    $entityTypeId = $this->requireNonEmptyString($args, 'entityTypeId');
    $bundle = $this->requireNonEmptyString($args, 'bundle');
    $fieldName = $this->requireNonEmptyString($args, 'fieldName');
    return new EntityFieldUploadDestination($entityTypeId, $bundle, $fieldName);
  }

  /**
   * Builds editor destination from editor directive arguments.
   *
   * @param mixed[] $args
   *   Directive arguments from the enum value AST node.
   *
   * @return \Drupal\signed_file_upload\DataObject\EditorUploadDestination
   *   Destination describing text format and editor configuration.
   */
  private function buildEditorDestination(array $args): EditorUploadDestination {
    $textFormatId = $this->requireNonEmptyString($args, 'textFormatId');
    $editorId = $this->requireNonEmptyString($args, 'editorId');
    return new EditorUploadDestination($textFormatId, $editorId);
  }

  /**
   * Returns a non-empty string directive argument or throws.
   *
   * @param mixed[] $args
   *   Parsed directive argument map.
   * @param string $key
   *   Argument name (e.g. entityTypeId, fieldName).
   *
   * @return non-empty-string
   *   The trimmed non-empty string value.
   *
   * @throws \RuntimeException
   *   When the argument is missing, not a string, or empty.
   */
  private function requireNonEmptyString(array $args, string $key): string {
    $value = $args[$key] ?? NULL;
    if (!is_string($value) || $value === '') {
      throw new \RuntimeException("Directive argument $key must be a non-empty string.");
    }
    return $value;
  }

}
