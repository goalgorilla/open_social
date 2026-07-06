<?php

declare(strict_types=1);

namespace Drupal\Tests\activity_send\Kernel;

use Drupal\activity_send\Plugin\SendActivityDestinationBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the offline detection used to gate outgoing e-mail notifications.
 *
 * @group activity_send
 * @coversDefaultClass \Drupal\activity_send\Plugin\SendActivityDestinationBase
 */
final class IsUserOfflineTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'activity_creator',
    'activity_send',
  ];

  /**
   * The account whose online status is checked.
   */
  private User $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['activity_send']);
    $this->createSessionsTable();

    $this->account = User::create([
      'name' => 'active_user',
      'status' => 1,
    ]);
    $this->account->save();
  }

  /**
   * The integer 0 default must always send, bypassing the online check.
   *
   * @covers ::isUserOffline
   */
  public function testIntegerZeroAlwaysSends(): void {
    $this->setOfflineWindow(0);
    // Even a user who is online right now must be treated as "offline" so the
    // e-mail is sent.
    $this->setSessionTimestamp($this->now());

    $this->assertTrue(
      SendActivityDestinationBase::isUserOffline($this->account),
      'An offline window of integer 0 always sends, even to online users.',
    );
  }

  /**
   * A legacy string "0" must behave exactly like the integer 0 (regression).
   *
   * Before the fix the strict "=== 0" comparison did not match the string "0"
   * that the settings form used to persist, so the always-send bypass was
   * silently skipped and online users lost their e-mails (PROD-35934).
   *
   * @covers ::isUserOffline
   */
  public function testLegacyStringZeroAlwaysSends(): void {
    $this->setOfflineWindow('0');
    $this->setSessionTimestamp($this->now());

    $this->assertTrue(
      SendActivityDestinationBase::isUserOffline($this->account),
      'A stored string "0" must be treated as 0 and always send.',
    );
  }

  /**
   * With a positive window, a currently active user is suppressed.
   *
   * @covers ::isUserOffline
   */
  public function testOnlineUserWithinWindowIsSuppressed(): void {
    $this->setOfflineWindow(90);
    // Active within the window: last activity is now.
    $this->setSessionTimestamp($this->now());

    $this->assertFalse(
      SendActivityDestinationBase::isUserOffline($this->account),
      'A user active within the offline window is considered online.',
    );
  }

  /**
   * With a positive window, a user idle beyond it receives the e-mail.
   *
   * Uses a legacy string window to also cover the timestamp arithmetic, which
   * likewise relied on the value being numeric.
   *
   * @covers ::isUserOffline
   */
  public function testIdleUserBeyondWindowReceives(): void {
    $this->setOfflineWindow('90');
    // Last activity well beyond the 90 second window.
    $this->setSessionTimestamp($this->now() - 1000);

    $this->assertTrue(
      SendActivityDestinationBase::isUserOffline($this->account),
      'A user idle beyond the offline window is considered offline.',
    );
  }

  /**
   * A user with no session row at all is offline and receives the e-mail.
   *
   * @covers ::isUserOffline
   */
  public function testUserWithoutSessionReceives(): void {
    $this->setOfflineWindow(90);

    $this->assertTrue(
      SendActivityDestinationBase::isUserOffline($this->account),
      'A user with no session is considered offline.',
    );
  }

  /**
   * Sets the offline window value directly in the active config storage.
   *
   * Writing to the storage bypasses the config save event, so the value is
   * persisted verbatim without schema casting. This lets a string be stored to
   * reproduce the legacy on-disk state that this fix defends against.
   *
   * @param int|string $value
   *   The value to store.
   */
  private function setOfflineWindow(int|string $value): void {
    $this->container->get('config.storage')->write('activity_send.settings', [
      'activity_send_offline_window' => $value,
    ]);
    // Ensure the immutable config read inside isUserOffline() is fresh.
    $this->container->get('config.factory')->reset('activity_send.settings');
  }

  /**
   * Writes a session row for the test account with the given timestamp.
   *
   * @param int $timestamp
   *   The Unix timestamp of the last activity.
   */
  private function setSessionTimestamp(int $timestamp): void {
    $this->container->get('database')->merge('sessions')
      ->key('sid', 'sid_' . $this->account->id())
      ->fields([
        'uid' => (int) $this->account->id(),
        'timestamp' => $timestamp,
        'hostname' => '127.0.0.1',
        'session' => '',
      ])
      ->execute();
  }

  /**
   * The current request time.
   */
  private function now(): int {
    return $this->container->get('datetime.time')->getRequestTime();
  }

  /**
   * Creates the core sessions table, which has no hook_schema definition.
   */
  private function createSessionsTable(): void {
    $schema = $this->container->get('database')->schema();
    if ($schema->tableExists('sessions')) {
      return;
    }
    $schema->createTable('sessions', [
      'fields' => [
        'uid' => ['type' => 'int', 'unsigned' => TRUE, 'not null' => TRUE],
        'sid' => ['type' => 'varchar_ascii', 'length' => 128, 'not null' => TRUE],
        'hostname' => ['type' => 'varchar_ascii', 'length' => 128, 'not null' => TRUE, 'default' => ''],
        'timestamp' => ['type' => 'int', 'not null' => TRUE, 'default' => 0, 'size' => 'big'],
        'session' => ['type' => 'blob', 'not null' => FALSE, 'size' => 'big'],
      ],
      'primary key' => ['sid'],
      'indexes' => [
        'timestamp' => ['timestamp'],
        'uid' => ['uid'],
      ],
    ]);
  }

}
