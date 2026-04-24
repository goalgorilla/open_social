<?php

namespace Drupal\social_graphql\GraphQL\Scalar;

use Drupal\graphql\GraphQL\CustomScalarInterface;
use GraphQL\Error\UserError;
use GraphQL\Language\AST\Node;
use GraphQL\Utils\AST;
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Exception\InvalidDocumentException;
use OpenSocial\RichTextJson\Exception\ValidationException;

/**
 * The representation for Rich Text JSON.
 *
 * @implements \Drupal\graphql\GraphQL\CustomScalarInterface<\OpenSocial\RichTextJson\Document\ValidatedDocument>
 */
class RichTextJSON implements CustomScalarInterface {

  /**
   * {@inheritdoc}
   */
  public function serialize($value): mixed {
    return $value->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function parseValue($value): ValidatedDocument {
    if (is_object($value) && $value instanceof \stdClass) {
      $value = (array) $value;
    }
    if (!is_array($value)) {
      throw new UserError('Rich Text JSON document should be a valid JSON object.');
    }

    return $this->convertToDocument($value);
  }

  /**
   * {@inheritdoc}
   */
  public function parseLiteral(Node $valueNode, ?array $variables = NULL): ValidatedDocument {
    $value = AST::valueFromASTUntyped($valueNode, $variables);
    if (!is_array($value)) {
      throw new UserError('Rich Text JSON document should be a valid JSON object.');
    }

    return $this->convertToDocument($value);
  }

  /**
   * Convert a RichTextJSON input array to a validated document.
   *
   * Throws a user safe exception for invalid documents.
   *
   * @param array $value
   *   The input value.
   *
   * @return \OpenSocial\RichTextJson\Document\ValidatedDocument
   *   The validated document.
   */
  protected function convertToDocument(array $value) : ValidatedDocument {
    try {
      return ValidatedDocument::fromArray($value);
    }
    catch (InvalidDocumentException $e) {
      throw new UserError("Invalid Rich Text JSON document: " . $e->getMessage(), 0, $e);
    }
    catch (ValidationException $e) {
      throw new UserError("Rich Text JSON document failed validation: " . $e->getMessage(), 0, $e);
    }
  }

}
