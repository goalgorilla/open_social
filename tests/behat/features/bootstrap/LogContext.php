<?php

namespace Drupal\social\Behat;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Database\StatementWrapper;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Ensures that logs are clean at the end of tests.
 *
 * It's possible that tests functionally pass but still signal problems that
 * should be fixed. By enabling the dblog module and checking that there are no
 * notices, warnings or errors generated during the test we improve product
 * stability.
 */
class LogContext implements Context {

  /**
   * Gets an array of log level labels.
   *
   * Only contains levels we care about in tests (we ignore debug or info level
   * logs).
   *
   * @return array<string, string>
   *   An array of log level labels.
   */
  private static function getLogLevelLabelMap() : array {
    return [
      RfcLogLevel::NOTICE => 'Notice',
      RfcLogLevel::WARNING => 'Warning',
      RfcLogLevel::ERROR => 'Error',
      RfcLogLevel::CRITICAL => 'Critical Error',
      RfcLogLevel::ALERT => 'Alert',
      RfcLogLevel::EMERGENCY => 'Emergency',
    ];
  }

  /**
   * Ensure the dblog is enabled and store existing log entry count.
   *
   * This is triggered by DatabaseContext::triggerOnDatabaseLoaded so it runs
   * every time a database is imported.
   */
  public function onDatabaseLoaded() : void {
    \Drupal::service('module_installer')->install(['dblog']);

    $this->deleteAllLogMessages();
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
    $messages = $this->getLogMessages();

    $error_labels = static::getLogLevelLabelMap();

    $problems = [];
    foreach ($messages as $dblog) {
      // Ignore debug information and only trigger on errors.
      if (!isset($error_labels[$dblog->severity])) {
        continue;
      }

      if ($this->isIgnoredLogMessage($dblog)) {
        continue;
      }

      $problems[] = $error_labels[$dblog->severity] . "(" . $dblog->type . "): " . $this->formatMessage($dblog);
    }

    $problem_count = count($problems);
    if ($problem_count !== 0) {
      throw new \Exception("The log showed $problem_count issues raised during this step.\n\n" . implode("\n--------------\n", $problems));
    }
  }

  /**
   * I should see log message
   *
   * @Then I should see log message :value
   */
  public function iShouldSeeLogMessage($value) {
    $log_messages = $this->getLogMessages();
    $log_message_exist = FALSE;

    foreach ($log_messages as $log_message) {
      if ($log_message->message === $value) {
        return TRUE;
      }
    }

    if (!$log_message_exist) {
      throw new \Exception('The log message with value "' . $value . '" was not found in logs.');
    }
  }

  /**
   * Drupal can produce a lot of log messages that are not actual problems.
   *
   * @param \StdClass $row
   *   The row from the watchdog table.
   *
   * @return bool
   *   Whether to ignore this message.
   */
  protected function isIgnoredLogMessage($row) : bool {
    return
      // Ignore notices from the user module since we don't really care about
      // users logging in or being deleted, those conditions are part of test
      // assertions.
      ($row->type === 'user' && (int) $row->severity === RfcLogLevel::NOTICE)
      // Ignore notices for the content type since we don't care about content
      // creation (and those should really be INFO anywhere, but that's a Drupal
      // core problem for another day).
      || ($row->type === 'content' && (int) $row->severity === RfcLogLevel::NOTICE)
      // Ignore comments being posted.
      || ($row->type === 'comment' && (int) $row->severity === RfcLogLevel::NOTICE)
      // Ignore language creation notices.
      || ($row->type === 'language' && (int) $row->severity === RfcLogLevel::NOTICE && str_contains($row->message, "language has been created"))
      // Drupal treats access denied results as warnings but they're actually
      // just part of business as usual.
      || ($row->type === 'access denied' && (int) $row->severity === RfcLogLevel::WARNING)
      // Ignore group content deletions.
      || ($row->type === 'group_content' && (int) $row->severity === RfcLogLevel::NOTICE && str_contains($row->message, "deleted"))
      // Ignore block content additions.
      || ($row->type === 'block_content' && (int) $row->severity === RfcLogLevel::NOTICE)
      // Ignore page not found warnings since they may be part of tests and
      // should be asserted.
      || ($row->type === 'page not found' && (int) $row->severity === RfcLogLevel::WARNING)
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3319407
      || ($row->type === 'php' && (int) $row->severity === RfcLogLevel::WARNING && str_contains($row->variables, 'Undefined array key "arguments"') && str_contains($row->variables, "ViewsBulkOperationsBulkForm"))
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3319408
      || ($row->type === 'search_api' && (int) $row->severity === RfcLogLevel::WARNING && str_contains($row->variables, 'Social Groups') && str_contains($row->variables, "rendered_item"))
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3319409
      || ($row->type === 'search_api_db' && (int) $row->severity === RfcLogLevel::WARNING && str_contains($row->variables, "field_group_allowed_join_method") && str_contains($row->message, "Unknown field @field: please check (and re-save) the index's fields settings."))
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3319526
      || ($row->type === 'activity_send_email_worker' && (int) $row->severity === RfcLogLevel::NOTICE && str_contains($row->message, "The activity was already deleted. We marked it as successful."))
      // Ignore an existing bug.
      // @todo https://www.drupal.org/project/social/issues/3320117
      || ($row->type === 'php' && (int) $row->severity === RfcLogLevel::WARNING && (str_contains($row->variables, 'Undefined array key "#comment_display_mode"') || str_contains($row->variables, 'Undefined array key "#comment_type"')))
      ;
  }

  /**
   * Format a message with variables provided.
   *
   * Modified from DbLogcontroller::formatMessage.
   *
   * @param \StdClass $row
   *   The watchdog database row.
   *
   * @return string|null
   *   A formatted string or NULL if message or variable were missing.
   */
  private function formatMessage($row) : ?string {
    if (!isset($row->message, $row->variables)) {
      return NULL;
    }

    $variables = @unserialize($row->variables, ['allowed_classes' => TRUE]);

    // Messages without variables or user specified text.
    if ($variables === NULL) {
      return Xss::filterAdmin($row->message);
    }

    if (!is_array($variables)) {
      return 'Log data is corrupted and cannot be unserialized: ' . Xss::filterAdmin($row->message);
    }

    // Format message with injected variables. We don't do translation in tests.
    // @phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    return (string) (new TranslatableMarkup(Xss::filterAdmin($row->message), $variables, [], \Drupal::service('string_translation')));
  }

  /**
   * Clear out the watchdog table.
   */
  private function deleteAllLogMessages() : void {
    \Drupal::database()->truncate('watchdog')->execute();
  }

  /**
   * Get the messages stored in the watchdog table.
   *
   * We must query for this manually taking inspiration from the DbLogController
   * because there's no service that provides proper non-database access.
   *
   * @return \Drupal\Core\Database\StatementWrapper
   *   The result of the log message query.
   */
  private function getLogMessages() : StatementWrapper {
    $query = \Drupal::database()->select('watchdog', 'w')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->fields('w', [
      'wid',
      'uid',
      'severity',
      'type',
      'timestamp',
      'message',
      'variables',
      'link',
    ]);
    $query->leftJoin('users_field_data', 'ufd', '[w].[uid] = [ufd].[uid]');

    return $query->execute();
  }

}
