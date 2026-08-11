<?php

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Drupal\Component\Utility\Xss;
use Drupal\social\Behat\Loki\Client as LokiClient;
use Drupal\social\Behat\Loki\LogEntry;

/**
 * Ensures that logs are clean at the end of tests.
 *
 * It's possible that tests functionally pass but still signal problems that
 * should be fixed. By querying the Loki backend that local/CI environments
 * ship CMS logs to, and checking that there are no notices, warnings or
 * errors generated during the test, we improve product stability.
 */
class LogContext implements Context {

  /**
   * Our client for the Loki HTTP API.
   */
  protected LokiClient $loki;

  /**
   * The start of the window checked for log problems.
   *
   * Reset to "now" every time a database fixture is loaded, mirroring the
   * previous dblog-based behaviour of truncating the watchdog table at that
   * point.
   */
  protected \DateTimeImmutable $since;

  public function __construct() {
    $this->loki = new LokiClient();
    $this->since = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
  }

  /**
   * Gets an array of log level labels.
   *
   * Only contains levels we care about in tests (we ignore debug or info level
   * logs).
   *
   * @return array<string, string>
   *   An array of log level labels, keyed by OTel severity text (i.e.
   *   Monolog's upper-case level name).
   */
  private static function getLogLevelLabelMap() : array {
    return [
      'NOTICE' => 'Notice',
      'WARNING' => 'Warning',
      'ERROR' => 'Error',
      'CRITICAL' => 'Critical Error',
      'ALERT' => 'Alert',
      'EMERGENCY' => 'Emergency',
    ];
  }

  /**
   * Mark the start of the window checked for log problems.
   *
   * This is triggered by DatabaseContext::triggerOnDatabaseLoaded so it runs
   * every time a database is imported.
   */
  public function onDatabaseLoaded() : void {
    $this->since = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
  }

  /**
   * Find the log entries since the start of the test and check for problems.
   *
   * We check this after every step so that people viewing the test output have
   * a clear indication of what step caused the problem.
   *
   * @AfterStep
   */
  public function afterStep(AfterStepScope $scope) : void {
    $problems = $this->findProblems();

    $problem_count = count($problems);
    if ($problem_count !== 0) {
      throw new \Exception("The log showed $problem_count issues raised during this step.\n\n" . implode("\n--------------\n", $problems));
    }
  }

  /**
   * Give the last step's logs a final chance to reach Loki.
   *
   * afterStep() only queries Loki once per step. A problem logged by an
   * earlier step gets a second chance to surface via the *next* step's
   * afterStep() call if cms-otel-collector hadn't shipped it yet, but the
   * scenario's last step has no such follow-up. Wait briefly and check once
   * more before the scenario ends, so a slow-to-ship problem from that last
   * step still fails the scenario instead of passing silently.
   *
   * @AfterScenario
   */
  public function afterScenario(AfterScenarioScope $scope) : void {
    if (!$scope->getTestResult()->isPassed()) {
      return;
    }

    usleep(500_000);
    $problems = $this->findProblems();

    $problem_count = count($problems);
    if ($problem_count !== 0) {
      throw new \Exception("The log showed $problem_count issues raised during this scenario.\n\n" . implode("\n--------------\n", $problems));
    }
  }

  /**
   * Find unexpected log entries logged since the scenario started.
   *
   * @return string[]
   *   Formatted problem descriptions, or an empty array if none were found.
   */
  private function findProblems() : array {
    $error_labels = static::getLogLevelLabelMap();

    $severity_pattern = implode('|', array_keys($error_labels));
    $entries = $this->loki->query(
      '{service_name="cms"} | severity_text=~"' . $severity_pattern . '"',
      $this->since,
    );

    $problems = [];
    foreach ($entries as $entry) {
      if ($this->isIgnoredLogMessage($entry)) {
        continue;
      }

      $severity = $entry->getSeverityText();
      $problems[] = $error_labels[$severity] . "(" . ($entry->getChannel() ?? 'unknown') . "): " . $this->formatMessage($entry);
    }

    return $problems;
  }

  /**
   * I should see log message.
   *
   * A message logged by the previous step is not queryable immediately: it
   * travels php-fpm -> log file -> cms-otel-collector -> Loki before a query
   * can return it. Poll for a bounded window instead of querying once. The
   * window is generous on purpose: on a contended CI node the pipeline latency
   * plus the request that produces the message has exceeded 5 seconds, so a
   * short window fails scenarios that are otherwise fine. The loop returns as
   * soon as the message shows up, so the full window only costs time when the
   * message is genuinely missing.
   *
   * @Then I should see log message :value
   */
  public function iShouldSeeLogMessage($value) {
    $deadline = microtime(TRUE) + 30;
    do {
      foreach ($this->loki->query('{service_name="cms"}', $this->since) as $entry) {
        if ($entry->message === $value) {
          return;
        }
      }
      usleep(250_000);
    } while (microtime(TRUE) < $deadline);

    throw new \Exception('The log message with value "' . $value . '" was not found in logs.');
  }

  /**
   * Drupal can produce a lot of log messages that are not actual problems.
   *
   * @param \Drupal\social\Behat\Loki\LogEntry $entry
   *   The log entry to check.
   *
   * @return bool
   *   Whether to ignore this message.
   */
  protected function isIgnoredLogMessage(LogEntry $entry) : bool {
    $channel = $entry->getChannel();
    $severity = $entry->getSeverityText();
    $message = $entry->message;

    return
      // Ignore notices from the user module since we don't really care about
      // users logging in or being deleted, those conditions are part of test
      // assertions.
      ($channel === 'user' && $severity === 'NOTICE')
      // Ignore notices for the content type since we don't care about content
      // creation (and those should really be INFO anywhere, but that's a Drupal
      // core problem for another day).
      || ($channel === 'content' && $severity === 'NOTICE')
      // Ignore comments being posted.
      || ($channel === 'comment' && $severity === 'NOTICE')
      // Ignore language creation notices.
      || ($channel === 'language' && $severity === 'NOTICE' && str_contains($message, "language has been created"))
      // Drupal treats access denied results as warnings but they're actually
      // just part of business as usual.
      || ($channel === 'access denied' && $severity === 'WARNING')
      // Ignore group content deletions.
      || ($channel === 'group_content' && $severity === 'NOTICE' && str_contains($message, "deleted"))
      // Ignore block content additions.
      || ($channel === 'block_content' && $severity === 'NOTICE')
      // Ignore page not found warnings since they may be part of tests and
      // should be asserted.
      || ($channel === 'page not found' && $severity === 'WARNING')
      // Ignore an existing bug. Monolog interpolates Drupal's log placeholders
      // before the message reaches Loki, so what used to be checked against
      // dblog's separate `variables` column is now checked against the
      // rendered message text instead.
      // @todo https://www.drupal.org/project/social/issues/3319407
      || ($channel === 'php' && $severity === 'WARNING' && str_contains($message, 'Undefined array key "arguments"') && str_contains($message, "ViewsBulkOperationsBulkForm"))
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3319408
      || ($channel === 'search_api' && $severity === 'WARNING' && str_contains($message, 'Social Groups') && str_contains($message, "rendered_item"))
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3319409
      || ($channel === 'search_api_db' && $severity === 'WARNING' && str_contains($message, "field_group_allowed_join_method") && str_contains($message, "Unknown field @field: please check (and re-save) the index's fields settings."))
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3319526
      || ($channel === 'activity_send_email_worker' && $severity === 'NOTICE' && str_contains($message, "The activity was already deleted. We marked it as successful."))
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3320117
      || ($channel === 'php' && $severity === 'WARNING' && (str_contains($message, 'Undefined array key "#comment_display_mode"') || str_contains($message, 'Undefined array key "#comment_type"')))
      // Ignore update notices.
      || ($channel === 'update' && $severity === 'NOTICE')
      // Ignore group type created notice. The original check matched the raw,
      // unsubstituted "Added group type %label." template; the interpolated
      // message instead reads e.g. "Added group type Marketing Team.", so we
      // match on the fixed prefix instead.
      || ($channel === 'group' && $severity === 'NOTICE' && str_starts_with($message, 'Added group type '))
      // Ignore grequest notices. The original check matched a raw "@type: "
      // placeholder token that no longer appears verbatim once interpolated,
      // so (per the "Ignore grequest notices" intent) we ignore all NOTICE
      // severity group_relationship messages instead.
      || ($channel === 'group_relationship' && $severity === 'NOTICE')
      ;
  }

  /**
   * Prepare a log message for inclusion in a Behat failure.
   *
   * Unlike dblog, Loki entries carry the message with any Drupal log
   * placeholders (e.g. "%type", "@message") already interpolated by
   * drupal/monolog's MessagePlaceholderProcessor, so no further substitution
   * is needed here.
   *
   * @param \Drupal\social\Behat\Loki\LogEntry $entry
   *   The log entry to format.
   *
   * @return string
   *   The formatted message.
   */
  private function formatMessage(LogEntry $entry) : string {
    return Xss::filterAdmin($entry->message);
  }

}
