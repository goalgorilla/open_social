<?php

/**
 * @file
 * Contains \Drupal\social\Tests\SocialTest.
 */

namespace Drupal\social\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Social installation profile expectations.
 *
 * @group social
 */
class SocialTest extends WebTestBase {

  protected $profile = 'social';

  /**
   * Tests Social installation profile.
   */
  function testSocial() {
    $this->drupalGet('');
    // Check the login block is present.
    $this->assertLink(t('Create new account'));
    $this->assertResponse(200);

    // Create a user to test tools and navigation blocks for logged in users
    // with appropriate permissions.
    $user = $this->drupalCreateUser(array('access administration pages', 'administer content types'));
    $this->drupalLogin($user);
    $this->drupalGet('');
    $this->assertText(t('Tools'));
    $this->assertText(t('Administration'));

    // Ensure that there are no pending updates after installation.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('update.php/selection');
    $this->assertText('No pending updates.');

    // Ensure that there are no pending entity updates after installation.
    $this->assertFalse($this->container->get('entity.definition_update_manager')->needsUpdates(), 'After installation, entity schema is up to date.');
  }
}
