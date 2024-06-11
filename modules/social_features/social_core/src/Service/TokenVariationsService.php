<?php

declare(strict_types=1);

namespace Drupal\social_core\Service;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationManager;
use Drupal\Core\Utility\Token;

/**
 * TokenVariationsService class.
 */
final class TokenVariationsService {

  /**
   * Available variations.
   */
  const MODIFIERS = [
    'uppercase',
    'lowercase',
    'capitalize',
  ];

  /**
   * The token service.
   */
  protected Token $tokenService;

  /**
   * The string translation service.
   */
  protected TranslationManager $stringTranslation;

  /**
   * TokenVariationsService constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\StringTranslation\TranslationManager $string_translation
   *   The string translation service.
   */
  public function __construct(Token $token, TranslationManager $string_translation) {
    $this->tokenService = $token;
    $this->stringTranslation = $string_translation;
  }

  /**
   * Returns the list of available variations.
   *
   * @return array|string
   *   The list of available variations.
   */
  public function getVariations(bool $string = FALSE): array|string {
    if ($string) {
      return implode(', ', self::MODIFIERS);
    }

    return self::MODIFIERS;
  }

  /**
   * Apply variation to token values.
   *
   * @param \Drupal\Component\Render\MarkupInterface|\Drupal\Core\StringTranslation\TranslatableMarkup|string $value
   *   The original value of the token.
   * @param string $variation
   *   The token variation.
   *
   * @return \Drupal\Component\Render\MarkupInterface|string
   *   The list of tokens with variations.
   */
  public function applyVariation(MarkupInterface|TranslatableMarkup|string $value, string $variation): MarkupInterface|string {
    $arguments = [];
    $translatable = FALSE;

    // Convert TranslatableMarkup to string,
    // and extract arguments.
    if ($value instanceof TranslatableMarkup) {
      $arguments = $value->getArguments();
      $value = $value->getUntranslatedString();
      $translatable = TRUE;
    }

    // Convert Markup to string.
    if ($value instanceof MarkupInterface) {
      $value = $value->__toString();
    }

    switch ($variation) {
      case 'uppercase':
        $value = $translatable ? mb_strtoupper($this->stringTranslation->translate($value, $arguments)->__toString()) : mb_strtoupper($value);
        break;

      case 'lowercase':
        $value = $translatable ? mb_strtolower($this->stringTranslation->translate($value, $arguments)->__toString()) : mb_strtolower($value);
        break;

      case 'capitalize':
        $value = $translatable ? Unicode::ucwords($this->stringTranslation->translate($value, $arguments)->__toString()) : Unicode::ucwords($value);
        break;
    }

    return Markup::create($value);
  }

  /**
   * Returns the replacements including the variations.
   *
   * @param array $tokens
   *   The tokens array hook_tokens.
   * @param array $replacements
   *   The replacements array from hook_tokens.
   * @param array $tokens_to_apply_variations
   *   An associative array with token to apply variations,
   *   keyed by the token type.
   *
   * @return array
   *   The replacement tokens, including the variations.
   */
  public function variations(array $tokens, array &$replacements, array $tokens_to_apply_variations): array {
    foreach ($tokens_to_apply_variations as $token => $value) {
      // If available prefixed tokens.
      if ($prefixed_tokens = $this->tokenService->findWithPrefix($tokens, $token)) {
        foreach ($prefixed_tokens as $variation => $original) {
          // If variation is available.
          if (in_array($variation, self::MODIFIERS)) {
            // Add variation to replacements array.
            $replacements[$original] = $this->applyVariation($value, $variation);
          }
        }
      }
    }

    return $replacements;
  }

}
