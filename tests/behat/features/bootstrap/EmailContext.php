<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Drupal\ultimate_cron\Entity\CronJob;
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
    // Update Drupal configuration.
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
    // Update Drupal configuration.
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
   * @throws Exception
   */
  public function getSpooledEmails() {
    $finder = new Finder();
    $spoolDir = $this->getSpoolDir();

    if(empty($spoolDir)) {
      throw new \Exception('Could not retrieve the spool directory, or the directory does not exist.');
    }

    try {
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
    $path = '/var/www/html/profiles/contrib/social/tests/behat/features/swiftmailer-spool';
    if (!file_exists($path)) {
      mkdir($path, 0777, true);
    }

    return $path;
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
   * Find an email with the given subject and body.
   *
   * @param string $subject
   *   The subject of the email.
   * @param array $body
   *   Text that should be in the email.
   *
   * @return bool
   *   Email was found or not.
   * @throws Exception
   */
  protected function findSubjectAndBody($subject, $body) {
    $finder = $this->getSpooledEmails();

    $found_email = FALSE;

    if ($finder) {
      /** @var File $file */
      foreach ($finder as $file) {
        /** @var Swift_Message $email */
        $email = $this->getEmailContent($file);
        $email_subject = $email->getSubject();
        $email_body = $email->getBody();

        // Make it a traversable HTML doc.
        $doc = new \DOMDocument();
        $doc->loadHTML($email_body);
        $xpath = new \DOMXPath($doc);
        // Find the post header and email content in the HTML file.
        $content = $xpath->evaluate('string(//*[contains(@class,"postheader")])');
        $content .= $xpath->evaluate('string(//*[contains(@class,"main")])');
        $content_found = 0;

        foreach ($body as $string) {
          if (strpos($content, $string)) {
            $content_found++;
          }
        }

        if ($email_subject == $subject && $content_found === count($body)) {
          $found_email = TRUE;
        }
      }
    }
    else {
      throw new \Exception('There are no email messages.');
    }

    return $found_email;
  }

  /**
   * I run the digest cron.
   *
   * @Then I run the :arg1 digest cron
   */
  public function iRunTheDigestCron($frequency) {
    // Update the timings in the digest table.
    $query =  \Drupal::database()->update('user_activity_digest');
    $query->fields(['timestamp' => 1]);
    $query->condition('frequency', $frequency);
    $query->execute();

    // Update last run time to make sure we can run the digest cron.
    \Drupal::state()->set('digest.' . $frequency . '.last_run', 1);

    \Drupal::service('cron')->run();

    if (\Drupal::moduleHandler()->moduleExists('ultimate_cron')) {
      $jobs = CronJob::loadMultiple();

      /** @var CronJob $job */
      foreach($jobs as $job) {
        $job->run(t('Launched by drush'));
      }
    }
  }

  /**
   * I read an email.
   *
   * @Then /^(?:|I )should have an email with subject "([^"]*)" and "([^"]*)" in the body$/
   */
  public function iShouldHaveAnEmailWithTitleAndBody($subject, $body) {
    $found_email = $this->findSubjectAndBody($subject, [$body]);

    if (!$found_email) {
      throw new \Exception('There is no email with that subject and body.');
    }
  }

  /**
   * I read an email with multiple content.
   *
   * @Then I should have an email with subject :arg1 and in the content:
   */
  public function iShouldHaveAnEmailWithTitleAndBodyMulti($subject, TableNode $table) {
    $body = [];
    $hash = $table->getHash();
    foreach ($hash as $row) {
      $body[] = $row['content'];
    }

    $found_email = $this->findSubjectAndBody($subject, $body);

    if (!$found_email) {
      throw new \Exception('There is no email with that subject and body.');
    }
  }

  /**
   * I do not have an email.
   *
   * @Then /^(?:|I )should not have an email with subject "([^"]*)" and "([^"]*)" in the body$/
   */
  public function iShouldNotHaveAnEmailWithTitleAndBody($subject, $body) {
    $found_email = $this->findSubjectAndBody($subject, [$body]);

    if ($found_email) {
      throw new \Exception('There is an email with that subject and body.');
    }
  }

  /**
   * I do not have an email with multiple content.
   *
   * @Then I should not have an email with subject :arg1 and in the content:
   */
  public function iShouldNotHaveAnEmailWithTitleAndBodyMulti($subject, TableNode $table) {
    $body = [];
    $hash = $table->getHash();
    foreach ($hash as $row) {
      $body[] = $row['content'];
    }

    $found_email = $this->findSubjectAndBody($subject, $body);

    if ($found_email) {
      throw new \Exception('There is an email with that subject and body.');
    }
  }
}
