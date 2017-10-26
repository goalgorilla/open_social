<?php
// @codingStandardsIgnoreFile

use Behat\Behat\Context\Context;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;

class EmailContext implements Context {

  /**
   * We need to enable the spool directory.
   *
   * @BeforeScenario @email-spool
   */
  public function enableEmailSpool() {
    $swiftmailer_config = \Drupal::configFactory()->getEditable('swiftmailer.transport');
    $swiftmailer_config->set('transport', 'spool');
    $swiftmailer_config->set('spool_directory', $this->getSpoolDir());
    $swiftmailer_config->save();

    // Clean up emails that were left behind.
    $this->purgeSpool();
  }

  /**
   * Revert back to the old situation (native PHP mail).
   *
   * @AfterScenario @email-spool
   */
  public function disableEmailSpool() {
    $swiftmailer_config = \Drupal::configFactory()->getEditable('swiftmailer.transport');
    $swiftmailer_config->set('transport', 'native');
    $swiftmailer_config->save();

    // Clean up emails after us.
    $this->purgeSpool();
  }

  /**
   * @return Finder
   */
  public function getSpooledEmails() {
    $finder = new Finder();

    try {
      $spoolDir = $this->getSpoolDir();
      $finder->files()->in($spoolDir);
      return $finder;
    }
    catch (InvalidArgumentException $exception) {
      return NULL;
    }
  }

  /**
   * @param $file
   *
   * @return string
   */
  public function getEmailContent($file) {
    return unserialize(file_get_contents($file));
  }

  /**
   * @return string
   */
  protected function getSpoolDir() {
    return '/var/www/html/swiftmailer-spool';
  }

  /**
   * Purge the messages in the spool.
   */
  protected function purgeSpool() {
    $filesystem = new Filesystem();
    $finder = $this->getSpooledEmails();

    if ($finder) {
      /** @var File $file */
      foreach ($finder as $file) {
        $filesystem->remove($file->getRealPath());
      }
    }
  }
}