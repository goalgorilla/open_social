<?php

/**
 * @file
 * Contains \Drupal\token\Tests\TokenTestTrait.
 */

namespace Drupal\token\Tests;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Helper test trait with some added functions for testing.
 */
trait TokenTestTrait {

  function assertToken($type, array $data, $token, $expected, array $options = array()) {
    return $this->assertTokens($type, $data, array($token => $expected), $options);
  }

  function assertTokens($type, array $data, array $tokens, array $options = array()) {
    $input = $this->mapTokenNames($type, array_keys($tokens));
    $bubbleable_metadata = new BubbleableMetadata();
    $replacements = \Drupal::token()->generate($type, $input, $data, $options, $bubbleable_metadata);
    foreach ($tokens as $name => $expected) {
      $token = $input[$name];
      if (!isset($expected)) {
        $this->assertTrue(!isset($replacements[$token]), t("Token value for @token was not generated.", array('@type' => $type, '@token' => $token)));
      }
      elseif (!isset($replacements[$token])) {
        $this->fail(t("Token value for @token was not generated.", array('@type' => $type, '@token' => $token)));
      }
      elseif (!empty($options['regex'])) {
        $this->assertTrue(preg_match('/^' . $expected . '$/', $replacements[$token]), t("Token value for @token was '@actual', matching regular expression pattern '@expected'.", array('@type' => $type, '@token' => $token, '@actual' => $replacements[$token], '@expected' => $expected)));
      }
      else {
        $this->assertEqual($replacements[$token], $expected, t("Token value for @token was '@actual', expected value '@expected'.", array('@type' => $type, '@token' => $token, '@actual' => $replacements[$token], '@expected' => $expected)));
      }
    }

    return $replacements;
  }

  function mapTokenNames($type, array $tokens = array()) {
    $return = array();
    foreach ($tokens as $token) {
      $return[$token] = "[$type:$token]";
    }
    return $return;
  }

  function assertNoTokens($type, array $data, array $tokens, array $options = array()) {
    $input = $this->mapTokenNames($type, $tokens);
    $bubbleable_metadata = new BubbleableMetadata();
    $replacements = \Drupal::token()->generate($type, $input, $data, $options, $bubbleable_metadata);
    foreach ($tokens as $name) {
      $token = $input[$name];
      $this->assertTrue(!isset($replacements[$token]), t("Token value for @token was not generated.", array('@type' => $type, '@token' => $token)));
    }
  }

  function saveAlias($source, $alias, $language = Language::LANGCODE_NOT_SPECIFIED) {
    $alias = array(
      'source' => $source,
      'alias' => $alias,
      'language' => $language,
    );
    \Drupal::service('path.alias_storage')->save($alias['source'], $alias['alias']);
    return $alias;
  }

  function saveEntityAlias($entity_type, EntityInterface $entity, $alias, $language = Language::LANGCODE_NOT_SPECIFIED) {
    $uri = $entity->urlInfo()->toArray();
    return $this->saveAlias($uri['path'], $alias, $language);
  }

  /**
   * Make a page request and test for token generation.
   */
  function assertPageTokens($url, array $tokens, array $data = array(), array $options = array()) {
    if (empty($tokens)) {
      return TRUE;
    }

    $token_page_tokens = array(
      'tokens' => $tokens,
      'data' => $data,
      'options' => $options,
    );
    \Drupal::state()->set('token_page_tokens', $token_page_tokens);

    $options += array('url_options' => array());
    $this->drupalGet($url, $options['url_options']);
    $this->refreshVariables();
    $result = \Drupal::state()->get('token_page_tokens', array());

    if (!isset($result['values']) || !is_array($result['values'])) {
      return $this->fail('Failed to generate tokens.');
    }

    foreach ($tokens as $token => $expected) {
      if (!isset($expected)) {
        $this->assertTrue(!isset($result['values'][$token]) || $result['values'][$token] === $token, t("Token value for @token was not generated.", array('@token' => $token)));
      }
      elseif (!isset($result['values'][$token])) {
        $this->fail(t('Failed to generate token @token.', array('@token' => $token)));
      }
      else {
        $this->assertIdentical($result['values'][$token], (string) $expected, t("Token value for @token was '@actual', expected value '@expected'.", array('@token' => $token, '@actual' => $result['values'][$token], '@expected' => $expected)));
      }
    }
  }

}
