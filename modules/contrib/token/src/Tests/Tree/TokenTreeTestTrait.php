<?php

/**
 * @file
 * Contains \Drupal\token\Tests\Tree\TokenTreeTestTrait.
 */

namespace Drupal\token\Tests\Tree;

/**
 * Helper trait to assert tokens in token tree browser.
 */
trait TokenTreeTestTrait {

  /**
   * Get an array of token groups from the last retrieved page.
   *
   * @return array
   *   Array of token group names.
   */
  protected function getTokenGroups() {
    $groups = $this->xpath('//tr[contains(@class, "token-group")]/td[1]');
    return array_map(function ($item) {
      return (string) $item;
    }, $groups);
  }

  /**
   * Check to see if the specified token group is present in the token browser.
   *
   * @param string $token_group
   *   The name of the token group.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output.
   */
  protected function assertTokenGroup($token_group, $message = '', $group = 'Other') {
    $groups = $this->getTokenGroups();

    if (!$message) {
      $message = "Token group $token_group found.";
    }

    $this->assertTrue(in_array($token_group, $groups), $message, $group);
  }

  /**
   * Check to see if the specified token is present in the token browser.
   *
   * @param $token
   *   The token name with the surrounding square brackets [].
   * @param string $parent
   *   (optional) The parent CSS identifier of this token.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output.
   */
  protected function assertTokenInTree($token, $parent = '', $message = '', $group = 'Other') {
    $xpath = $this->getXpathForTokenInTree($token, $parent);

    if (!$message) {
      $message = "Token $token found.";
    }

    $this->assertIdentical(1, count($this->xpath($xpath)), $message, $group);
  }

  /**
   * Check to see if the specified token is present in the token browser.
   *
   * @param $token
   *   The token name with the surrounding square brackets [].
   * @param string $parent
   *   (optional) The parent CSS identifier of this token.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output.
   */
  protected function assertTokenNotInTree($token, $parent = '', $message = '', $group = 'Other') {
    $xpath = $this->getXpathForTokenInTree($token, $parent);

    if (!$message) {
      $message = "Token $token not found.";
    }

    $this->assertIdentical(0, count($this->xpath($xpath)), $message, $group);
  }

  /**
   * Get xpath to check for token in tree.
   *
   * @param $token
   *   The token name with the surrounding square brackets [].
   * @param string $parent
   *   (optional) The parent CSS identifier of this token.
   *
   * @return string
   *   The xpath to check for the token and parent.
   */
  protected function getXpathForTokenInTree($token, $parent = '') {
    $xpath = "//tr";
    if ($parent) {
      $xpath .= '[contains(@class, "child-of-token-' . $parent . ' ")]';
    }
    $xpath .= '/td[contains(@class, "token-key") and text() = "' . $token . '"]';
    return $xpath;
  }
}
