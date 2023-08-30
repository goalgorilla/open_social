<?php

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Drupal\ultimate_cron\Entity\CronJob;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Provides helpful test steps around the handling of e-mails.
 *
 * To enable email collection for one or more scenario's add the @email-spool
 * annotation to the scenario or to an entire feature. Email collection in the
 * mail-spool directory will automatically happen for annotated tests.
 *
 * Steps exist to check that a certain email was or was not sent.
 */
class EmailContext implements Context {

  /**
   * We need to enable the spool directory.
   *
   * @BeforeScenario @email-spool
   */
  public function enableEmailSpool() : void {
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
  public function disableEmailSpool() : void {
    // Update Drupal configuration.
    $swiftmailer_config = \Drupal::configFactory()->getEditable('swiftmailer.transport');
    $swiftmailer_config->set('transport', 'native');
    $swiftmailer_config->save();
  }

  /**
   * I run the digest cron.
   *
   * @Then I run the :arg1 digest cron
   */
  public function iRunTheDigestCron(string $frequency) : void {
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
  public function iShouldHaveAnEmailWithTitleAndBody(string $subject, string $body) : void {
    $this->assertEmailWithSubjectAndBody($subject, [$body]);
  }

  /**
   * I read an email with multiple content.
   *
   * @Then I should have an email with subject :arg1 and in the content:
   */
  public function iShouldHaveAnEmailWithTitleAndBodyMulti(string $subject, TableNode $table) : void {
    $body = [];
    $hash = $table->getHash();
    foreach ($hash as $row) {
      $body[] = $row['content'];
    }

    $this->assertEmailWithSubjectAndBody($subject, $body);
  }

  /**
   * No emails have been sent.
   *
   * @Then no emails have been sent
   */
  public function noEmailsHaveBeenSent() : void {
    $finder = $this->getSpooledEmails();

    $count = $finder->count();
    if ($count !== 0) {
      throw new \Exception("No email messages should have been sent, but found $count.");
    }
  }

  /**
   * I do not have an email with a specific subject.
   *
   * Can be used in case you don't want an email to be sent regardless of the
   * body.
   *
   * @Then should not have an email with subject :subject
   * @Then I should not have an email with subject :subject
   */
  public function iShouldNotHaveAnEmailWithSubject(string $subject) : void {
    $emails = $this->findEmailsWithSubject($subject);
    $email_count = count($emails);
    if ($email_count !== 0) {
      throw new \Exception("Expected no emails with subject '$subject' but found $email_count");
    }
  }

  /**
   * I do not have an email.
   *
   * @Then /^(?:|I )should not have an email with subject "([^"]*)" and "([^"]*)" in the body$/
   */
  public function iShouldNotHaveAnEmailWithTitleAndBody(string $subject, string $body) : void {
    $this->assertNoEmailWithSubjectAndBody($subject, [$body]);
  }

  /**
   * I do not have an email with multiple content.
   *
   * @Then I should not have an email with subject :arg1 and in the content:
   */
  public function iShouldNotHaveAnEmailWithTitleAndBodyMulti(string $subject, TableNode $table) : void {
    $body = [];
    $hash = $table->getHash();
    foreach ($hash as $row) {
      $body[] = $row['content'];
    }

    $this->assertNoEmailWithSubjectAndBody($subject, $body);
  }

  /**
   * Ensure at least one email exists matching the provided subject and body.
   *
   * @param string $subject
   *   The exact subject the email should have.
   *
   * @param list<string> $expected_lines
   *   A list of lines that should all be present in the body of the e-mail.
   *
   * @throws \Exception
   *   An exception that details to the developer what was wrong (e.g. no
   *   matching subject, no matching lines, or missing a specific line match).
   */
  protected function assertEmailWithSubjectAndBody(string $subject, array $expected_lines) : void {
    $emails = $this->findEmailsWithSubject($subject);

    // If no emails with the subject exist then we want to report that so a
    // developer knows that may be the cause of their failure.
    $subject_match_count = count($emails);
    if ($subject_match_count === 0) {
      throw new \Exception("No emails with subject '$subject' were found.");
    }

    $partial_matches = [];
    $count_expected = count($expected_lines);
    foreach ($emails as $email) {
      $matched_lines = $this->getMatchingLinesForEmail($expected_lines, $email);

      $count_matched = count($matched_lines);
      // If we have a complete match then we're done.
      if ($count_matched === $count_expected) {
        return;
      }

      if ($count_matched > 0) {
        $partial_matches[] = $matched_lines;
      }
    }

    $partial_match_count = count($partial_matches);
    // If we had no partial matches then we can provide a simplified error message.
    if ($partial_match_count === 0) {
      $message = $subject_match_count === 1
        ? "One email with the subject '$subject' was found but none of the expected lines were found in the body of the email."
        : "$subject_match_count emails with the subject '$subject' were found but none of the expected lines were found in the body of the email.";
      throw new \Exception($message);
    }
    // If we had a single partial match then we tell the developer and there's
    // probably a simple typo in the email or test.
    if ($partial_match_count === 1) {
      $missing_lines = array_diff($expected_lines, $partial_matches[0]);
      $message = "One email was found which matched the subject and some lines but the following lines were missing from the e-mail: \n  " . implode("\n  ", $missing_lines);
      throw new \Exception($message);
    }
    // With multiple partial matches we want to provide the developer as much
    // information as possible.
    $message = "$partial_match_count emails were found that matched the subject but all of them were missing some expected lines from the email:\n";
    foreach ($partial_matches as $i => $partial_match) {
      $missing_lines = array_diff($expected_lines, $partial_match);
      $message .= "------- Partial match $i --------\n  " . implode("\n  ", $missing_lines);
    }
    throw new \Exception($message);
  }

  /**
   * Ensure there's no emails with the specified subject and body.
   *
   * @param string $subject
   *   The subject to match against.
   * @param list<string> $expected_lines
   *   An array of strings that match the body's contents. For an email to be
   *   considered a match, all lines in the array must match.
   *
   * @throws \Exception
   *   In case an email was found matching the subject and all expected lines of
   *   text.
   */
  protected function assertNoEmailWithSubjectAndBody(string $subject, array $expected_lines) : void {
    $emails = $this->findEmailsWithSubject($subject);

    $count_expected = count($expected_lines);
    foreach ($emails as $email) {
      $matched_lines = $this->getMatchingLinesForEmail($expected_lines, $email);

      $count_matched = count($matched_lines);
      if ($count_matched === $count_expected) {
        throw new \Exception("An email exists with the specified subject and body.");
      }
    }
  }

  /**
   * Purge the messages in the spool.
   */
  protected function purgeSpool() : void {
    $filesystem = new Filesystem();
    $finder = $this->getSpooledEmails();

    /** @var File $file */
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
      assert($email instanceof \Swift_Message);

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
      return $finder->files()->name("*.message")->in($spoolDir);
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
   * @return
   *   A deserialized email.
   */
  protected function getEmailContent(SplFileInfo $file) {
    assert($file->getExtension() === "message", "File passed to " . __FUNCTION__ . " must be a serialized .message file.");
    return unserialize($file->getContents());
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
    $body = $email->getBody();

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
