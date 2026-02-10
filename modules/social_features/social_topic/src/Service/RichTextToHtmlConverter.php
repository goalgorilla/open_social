<?php

declare(strict_types=1);

namespace Drupal\social_topic\Service;

use Drupal\social_graphql\GraphQL\Violation;
use OpenSocial\RichTextJson\Document\ValidatedDocument;
use OpenSocial\RichTextJson\Exception\InvalidDocumentException;
use OpenSocial\RichTextJson\Exception\ValidationException;
use OpenSocial\RichTextJson\Renderer\HtmlRenderer;

/**
 * Converts Open Social Rich Text JSON to HTML using the exchange library.
 */
class RichTextToHtmlConverter {

  /**
   * Converts a Rich Text JSON document (as array) to HTML.
   *
   * @param array<string, mixed> $data
   *   The document data (e.g. from GraphQL input).
   *
   * @return \Drupal\social_topic\Service\RichTextConversionResult
   *   The conversion result with HTML on success or violations on failure.
   */
  public function convert(array $data): RichTextConversionResult {
    try {
      $validated = ValidatedDocument::fromArray($data);
      $renderer = new HtmlRenderer();
      $html = $renderer->renderDocument($validated->getDocument());
      return new RichTextConversionResult($html, []);
    }
    catch (InvalidDocumentException $e) {
      return new RichTextConversionResult(NULL, [
        new Violation('BODY_INVALID_STRUCTURE'),
      ]);
    }
    catch (ValidationException $e) {
      $violations = [];
      foreach ($e->getErrors() as $error) {
        $violations[] = new Violation('BODY_VALIDATION_ERROR');
      }
      return new RichTextConversionResult(NULL, $violations);
    }
  }

}
