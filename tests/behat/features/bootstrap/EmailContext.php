<?php
// @codingStandardsIgnoreFile

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Drupal\ultimate_cron\Entity\CronJob;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Mime\Email as MimeEmail;
use Drupal\symfony_mailer\Email as DrupalSymfonyEmail;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Drupal\symfony_mailer\Address;

class EmailContext implements Context {

  /**
   * We need to enable the spool directory.
   *
   * @BeforeScenario @email-spool
   */

  public function enableEmailSpool() : void {
    // Set transport to null to stop sending out emails.
    $config = \Drupal::configFactory()
      ->getEditable('symfony_mailer.mailer_transport.sendmail');
    $config->set('plugin', 'null');
    $config->set('configuration', []);
    $config->save();

    // Add the mail logger plugin to the default policy.
    $config = \Drupal::configFactory()
      ->getEditable('symfony_mailer.mailer_policy._');
    $mailer_configuration = $config->get('configuration');
    $mailer_configuration['log_mail'] = ['spool_directory' => $this->getSpoolDir()];
    $config->set('configuration', $mailer_configuration)->save();

    // Clean up emails that were left behind.
    $this->purgeSpool();
  }

  /**
   * Revert back to the old situation (native PHP mail).
   *
   * @AfterScenario @email-spool
   */
  public function disableEmailSpool() : void {
    // Restore transport back to mailcatcher.
    $config = \Drupal::configFactory()
      ->getEditable('symfony_mailer.mailer_transport.sendmail');
    $config->set('plugin', 'smtp');
    $config->set('configuration.user', '');
    $config->set('configuration.pass', '');
    $config->set('configuration.host', 'mailcatcher');
    $config->set('configuration.port', '1025');
    $config->save();

    // Remove the mail logger plugin to the default policy.
    $config = \Drupal::configFactory()
      ->getEditable('symfony_mailer.mailer_policy._');
    $mailer_configuration = $config->get('configuration');
    unset($mailer_configuration['log_mail']);
    $config->set('configuration', $mailer_configuration)->save();
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

    $spool_directory = $this->getSpoolDir();
    $emails = $this->getSpooledEmails();
    foreach ($emails as $serialized) {
      $path = $spool_directory . DIRECTORY_SEPARATOR . $serialized->getBasename($serialized->getExtension());

      $email = $this->getEmailContent($serialized);
      file_put_contents($path . "html", $email->getHtmlBody());
      file_put_contents($path . "txt", $email->getTextBody());
      file_put_contents($path . "metadata", $email->getHeaders()->toString());
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
        @$doc->loadHTML($email_body);
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

  /**
   * Purge the messages in the spool.
   */
  protected function purgeSpool() : void {
    $filesystem = new Filesystem();
    $finder = $this->getSpooledEmails();

    /** @var \Symfony\Component\Finder\SplFileInfo $file */
    foreach ($finder as $file) {
      $filesystem->remove($file->getRealPath());
    }
  }

  /**
   * Find all emails that were sent with a given subject.
   *
   * @param string $subject
   *   The subject to search for.
   *
   * @return array
   *   An array of matching emails.
   * @throws \Exception
   *   An exception in case no emails were sent or email collection is
   *   incorrectly configured.
   */
  protected function findEmailsWithSubject(string $subject) : array {
    $finder = $this->getSpooledEmails();

    if ($finder->count() === 0) {
      throw new \Exception('No email messages have been sent in the test.');
    }

    $emails = [];
    foreach ($finder as $file) {
      $email = $this->getEmailContent($file);

      if ($email->getSubject() === $subject) {
        $emails[] = $email;
      }
    }

    return $emails;
  }

  /**
   * Get a list of spooled emails.
   *
   * @return Finder
   *   Returns a Finder if the directory exists.
   * @throws \Exception
   *   An exception is thrown in case the configured directory does not exist.
   */
  protected function getSpooledEmails() : Finder {
    $finder = new Finder();
    $spoolDir = $this->getSpoolDir();

    try {
      // We don't provide a filter on extension here because we use the same
      // finder in our purgeSpool function. extractSentEmails does create files
      // with other extensions, but that should always happen last.
      return $finder->files()->in($spoolDir);
    }
    catch (\InvalidArgumentException $exception) {
      throw new \Exception("The e-mail spool directory does not exist or is incorrectly configured, expected '{$spoolDir}' to exist.");
    }
  }

  /**
   * Get the path where the spooled emails are stored.
   *
   * @return string
   *   The path where the spooled emails are stored.
   */
  protected function getSpoolDir() : string {
    // This path should exist within the repository and have a .gitignore file
    // that ignores all e-mails so developers don't accidentally commit them.
    return \Drupal::service('extension.list.profile')->getPath('social') . '/tests/behat/mail-spool';
  }

  /**
   * Get content of email.
   *
   * @param \Symfony\Component\Finder\SplFileInfo $file
   *   The .message file to get content for.
   *
   * @return \Drupal\symfony_mailer\Email
   *   A deserialized email.
   */
  protected function getEmailContent(SplFileInfo $file) : DrupalSymfonyEmail {
    return unserialize(file_get_contents($file), ["allowed_classes" => [
      DrupalSymfonyEmail::class,
      Headers::class,
      UnstructuredHeader::class,
      Address::class,
      MimeEmail::class
    ]]);
  }

  /**
   * Find the matching lines in a specific email.
   *
   * @param list<string> $expected_lines
   *   The list of lines of text that is expected to be in the email.
   * @param $email
   *   The email to check.
   *
   * @return list<string>
   *   A list of lines from $expected_lines that was found in the body of
   *   $email.
   */
  protected function getMatchingLinesForEmail(array $expected_lines, $email) : array {
    $body = $email->getHtmlBody();

    // Make it a traversable HTML doc.
    $doc = new \DOMDocument();
    @$doc->loadHTML($body);
    $xpath = new \DOMXPath($doc);
    // Find the post header and email content in the HTML file.
    $content = $xpath->evaluate('string(//*[contains(@class,"postheader")])');
    $content .= $xpath->evaluate('string(//*[contains(@class,"main")])');

    $matched_lines = [];

    foreach ($expected_lines as $string) {
      if (str_contains($content, $string)) {
        $matched_lines[] = $string;
      }
    }

    return $matched_lines;
  }

}
