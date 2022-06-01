<?php

namespace Drupal\Tests\social\Functional\Installer;

use Drupal\FunctionalTests\Installer\InstallerTestBase;

/**
 * Tests Open Socials installer support.
 *
 * @group Installer
 */
class DistributionInstallerTest extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'social';

  /**
   * {@inheritdoc}
   */
  protected function setUpLanguage() {
    $session_assert = $this->assertSession();

    // Verify that the distribution name appears.
    $session_assert->pageTextContains('Open Social');
    // Verify that the distribution name is used in the site title.
    $session_assert->titleEquals('Choose language | ' . 'Open Social');
    // Verify that the "Choose profile" step does not appear.
    $session_assert->pageTextNotContains('profile');

    parent::setUpLanguage();
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpProfile() {
    // This step is skipped, because there is a distribution profile.
  }

  /**
   * Confirms that the installation succeeded.
   */
  public function testInstalled() {
    $this->assertSession()->addressEquals('/');
    $this->assertSession()->statusCodeEquals(200);
    // Confirm that we are logged-in after installation.
    $this->assertSession()->pageTextContains($this->rootUser->getAccountName());
//    $this->assertSession()->pageTextContains('Congratulations, you installed');

    // Confirm that Drupal recognizes this distribution as the current profile.
    $this->assertEquals('social', \Drupal::installProfile());
    $this->assertEquals('social', $this->config('core.extension')->get('profile'), 'The install profile has been written to core.extension configuration.');
    $this->assertEquals('socialblue', \Drupal::theme()->getActiveTheme()->getName(), 'The correct theme is installed as default.');
    $this->assertTrue(\Drupal::service('module_handler')->moduleExists('social_core'), 'The core module is installed.');
  }

}
