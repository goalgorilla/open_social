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
   * Get a list of spooled emails.
   *
   * @return Finder|null
   *   Returns a Finder if the directory exists.
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
   * Get content of email.
   *
   * @param string $file
   *   Path to the file.
   *
   * @return string
   *   An unserialized email.
   */
  public function getEmailContent($file) {
    return unserialize(file_get_contents($file));
  }

  /**
   * Get the path where the spooled emails are stored.
   *
   * @return string
   *   The path where the spooled emails are stored.
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

  /**
   * I read an email.
   *
   * @Then /^(?:|I )should have an email with subject "([^"]*)" and "([^"]*)" in the body$/
   */
  public function iShouldHaveAnEmailWithTitleAndBody($subject, $body) {
    $finder = $this->getSpooledEmails();

    $found_email = FALSE;

    if ($finder) {
      /** @var File $file */
      foreach ($finder as $file) {
        /** @var Swift_Message $email */
        $email = $this->getEmailContent($file);
        $email_subject = $email->getSubject();
        $email_body = $email->getBody();

        var_dump($email_body);

        if ($email_subject == $subject) {
          $found_email = TRUE;
        }
      }
    }

    if (!$found_email) {
      throw new \Exception(sprintf('There is no email with that subject and body.'));
    }
  }
}