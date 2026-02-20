<?php

namespace Drupal\social_graphql\Plugin\GraphQL\Types;

use GraphQL\Error\Error;
use GraphQL\Language\AST\Node;
use GraphQL\Language\AST\ValueNode;
use GraphQL\Utils\AST;

/**
 * The representation for Rich Text JSON.
 *
 * @implements \Drupal\social_graphql\Plugin\GraphQL\Types\CustomScalarInterface<\OpenSocial\RichTextJson\Document\RichTextDocument>
 */
class RichTextJSON implements CustomScalarInterface {

  /**
   * {@inheritdoc}
   */
  public static function serialize($value): mixed {
    return $value->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public static function parseValue($value): mixed {
    if (is_array($value)) {
      return $value;
    }
    if (is_object($value) && $value instanceof \stdClass) {
      return (array) $value;
    }
    throw new Error('RichTextJSON must be an object.');
  }

  /**
   * {@inheritdoc}
   */
  public static function parseLiteral(Node&ValueNode $valueNode, ?array $variables = NULL) {
    $value = AST::valueFromASTUntyped($valueNode, $variables);
    if (!is_array($value)) {
      throw new Error('RichTextJSON must be an object.');
    }
    return $value;
  }

}
