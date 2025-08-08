<?php

namespace Drupal\social\Behat;

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Behat\Mink\Session;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\social\Behat\Mailpit\Client as MailpitClient;
use Drupal\social\Behat\Mailpit\Message;
use Drupal\social\Behat\Mailpit\MessageSummary;
use Drupal\ultimate_cron\Entity\CronJob;

/**
 * Provides helpful test steps around the handling of e-mails.
 *
 * Steps exist to check that a certain email was or was not sent.
 */
class EmailContext extends RawMinkContext {

  /**
   * Our client for the Mailpit API.
   */
  protected MailpitClient $mailpit;

  /**
   * The time when the test started.
   *
   * Used to ignore older emails without having to delete them, so that they can
   * be inspected by developers.
   *
   * The format of this string was specifically chosen to be parseable by
   * Mailpit because it supports fewer formats than its API docs suggest.
   */
  protected string $previousEmailDate = "1970-01-01 00:00:00.000 +0000";

  /**
   * Create a new EmailContext instance.
   *
   * @param string $logsPath
   *   The path to the Behat logs where e-mails will be output on failure.
   */
  public function __construct(
    private string $logsPath,
  ) {
    $this->mailpit = new MailpitClient();
  }

  /**
   * Get the timestamp of the previous email in Mailpit.
   *
   * This allows searches for emails sent during the current scenario to exclude
   * previous emails without having to delete them.
   *
   * @BeforeScenario
   */
  public function getLastEmailTimestamp() : void {
    $this->previousEmailDate = $this->mailpit
      ->getLastEmailDate()
      // Add one millisecond because Mailpit filters inclusive after.
      // Larger values could cause race conditions in tests that execute quickly
      // but steps generally take at least a few milliseconds.
      ->add(\DateInterval::createFromDateString('1 millisecond'))
      ->format("Y-m-d H:i:s.v O");
  }

  /**
   * Close any window used to inspect mail.
   *
   * @AfterScenario
   */
  public function closeMailSession(AfterScenarioScope $afterScenario) : void {
    // Only stop the session if one was registered.
    if ($this->getMink()->hasSession('mail')) {
      $this->getSession('mail')->stop();
    }
  }

  /**
   * Extract all received emails into metadata, HTML, and text.
   *
   * To help developers debug, in case a scenario failed, we connect with the
   * Mailpit API and capture the text contents, HTML contents, and the header
   * metadata of all received emails. This is written in 3 files to the logs
   * folder. This makes it easier for a developer to examine the contents of an
   * email.
   *
   * @AfterScenario
   */
  public function extractReceivedEmails(AfterScenarioScope $afterScenario) : void {
    if ($afterScenario->getTestResult()->isPassed()) {
      return;
    }

    $result = $this->mailpit->search(
      after: $this->previousEmailDate,
    );

    foreach ($result->messages as $message) {
      $path = "$this->logsPath/mail-$message->id";

      file_put_contents("$path.html", $this->mailpit->renderMessageHtml($message));
      file_put_contents("$path.txt", $this->mailpit->renderMessageText($message));
      file_put_contents("$path.headers.json", json_encode($this->mailpit->getMessageHeaders($message), JSON_PRETTY_PRINT));
    }
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
    $result = $this->mailpit->search(
      after: $this->previousEmailDate,
    );

    if ($result->messagesCount !== 0) {
      throw new \Exception("No email messages should have been sent, but found {$result->messagesCount}.");
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
    $result = $this->mailpit->search(
      after: $this->previousEmailDate,
      subject: $subject,
    );

    if ($result->messagesCount !== 0) {
      throw new \Exception("Expected no emails with subject '$subject' but found $result->messagesCount.");
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
   * @param list<string> $expected_lines
   *   A list of lines that should all be present in the body of the e-mail.
   *
   * @throws \Exception
   *   An exception that details to the developer what was wrong (e.g. no
   *   matching subject, no matching lines, or missing a specific line match).
   */
  protected function assertEmailWithSubjectAndBody(string $subject, array $expected_lines) : void {
    if ($this->findEmailWithSubjectAndBody($subject, $expected_lines) === NULL) {
      throw new \Exception("No emails with the expected subject and contents were found.");
    }
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
    if ($message = $this->findEmailWithSubjectAndBody($subject, $expected_lines)) {
      throw new \Exception("Email with subject and body containing the provided text was found (Message ID: $message->id).");
    }
  }

  /**
   * Checks whether an email matches the subject and all lines in the body.
   *
   * @param string $subject
   *   The subject to match against.
   * @param list<string> $expected_lines
   *   An array of strings that match the body's contents. For an email to be
   *   considered a match, all lines in the array must match.
   *
   * @return \Drupal\social\Behat\Mailpit\MessageSummary|null
   *   Whether there is an email that matches both the subject and contains all
   *   the expected lines in the body.
   */
  protected function findEmailWithSubjectAndBody(string $subject, array $expected_lines) : ?MessageSummary {
    $result = $this->mailpit->search(
      after: $this->previousEmailDate,
      subject: $subject,
    );

    foreach ($result->messages as $message) {
      $mail = $this->openMail($message);

      // If none of the lines were filtered out that means they're all in the
      // mail and this is an unexpected mail that we should fail on.
      // @todo Replace with array_all in PHP 8.4 and up.
      if (array_filter($expected_lines, fn ($line) => $mail->hasContent($line)) === $expected_lines) {
        return $message;
      }
    }

    // We got through all mails with matching subject but none matched all the
    // body lines.
    return NULL;
  }

  /**
   * Open an email as a webpage so that its HTML contents can be inspected.
   *
   * @param \Drupal\social\Behat\Mailpit\Message|\Drupal\social\Behat\Mailpit\MessageSummary|string $message
   *   The message to view.
   *
   * @return \Behat\Mink\Element\DocumentElement
   *   Returns the HTML of the Message as a page which allows calling normal
   *   Behat selectors on it.
   */
  protected function openMail(Message|MessageSummary|string $message) {
    $this->getMailSession()
      ->visit($this->mailpit->getMessageHtmlUrl($message));

    return $this->getSession("mail")->getPage();
  }

  /**
   * Retrieve or create a session for inspecting emails.
   *
   * By not using the default session we ensure that we don't disrupt any
   * ongoing navigation and allow test writers to not care about the order of
   * their assertions in feature files.
   *
   * @return \Behat\Mink\Session
   *   A session for mail inspection.
   */
  protected function getMailSession() {
    // If we don't have a mail session yet, create one using the same Driver as
    // our main session. This should ensure we don't disrupt any ongoing
    // navigation.
    if (!$this->getMink()->hasSession("mail")) {
      $this->getMink()->registerSession(
        'mail',
        new Session($this->getSession()->getDriver())
      );
    }

    return $this->getsession('mail');
  }

}
