<?php

declare(strict_types=1);

namespace OpenSocial\RichTextJson\Renderer;

/**
 * Sanitizes URLs to prevent XSS attacks.
 *
 * Blocks dangerous URL schemes like javascript:, data:, and vbscript:.
 */
final class UrlSanitizer {

  /**
   * Dangerous URL schemes that should be blocked.
   *
   * @var array<string, true>
   */
  private const DANGEROUS_SCHEMES = [
    'javascript' => TRUE,
    'data' => TRUE,
    'vbscript' => TRUE,
  ];

  /**
   * Sanitizes a URL, returning empty string if dangerous.
   *
   * @param string $url
   *   The URL to sanitize.
   *
   * @return string
   *   The sanitized URL, or empty string if dangerous.
   */
  public function sanitize(string $url): string {
    // Decode HTML entities to catch encoded attacks.
    $decoded = html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // Normalize control characters and whitespace for scheme detection.
    $normalized = preg_replace('/[\x00-\x20\x7F]+/', '', $decoded) ?? '';

    // Extract scheme if present.
    $scheme = $this->extractScheme($normalized);

    if ($scheme !== NULL && isset(self::DANGEROUS_SCHEMES[strtolower($scheme)])) {
      return '';
    }

    return $url;
  }

  /**
   * Extracts the scheme from a URL.
   *
   * @param string $url
   *   The URL to extract from.
   *
   * @return string|null
   *   The scheme (without colon), or NULL if no scheme.
   */
  private function extractScheme(string $url): ?string {
    // Match scheme at the start: letters followed by colon.
    if (preg_match('/^([a-zA-Z][a-zA-Z0-9+.-]*):/', $url, $matches) === 1) {
      return $matches[1];
    }

    return NULL;
  }

}
