<?php

declare(strict_types=1);

namespace Drupal\social_email_broadcast;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;

/**
 * Common service contains methods for "Social Email Broadcast".
 */
class SocialEmailBroadcast {

  /**
   * Immediate frequency.
   */
  const FREQUENCY_IMMEDIATELY = 'immediately';

  /**
   * None frequency.
   */
  const FREQUENCY_NONE = 'none';

  /**
   * Bulk emails user's frequencies table name.
   */
  const TABLE = 'user_email_send';

  /**
   * Constructs a SocialEmailBroadcast object.
   */
  public function __construct(
    private readonly Connection $connection,
  ) {}

  /**
   * Set bulk mailing user frequency.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account entity.
   * @param array $settings
   *   The list of frequency settings.
   *
   * @throws \Exception
   */
  public function setBulkEmailUserSettings(AccountInterface $account, array $settings): void {
    if (empty($settings)) {
      return;
    }

    foreach ($settings as $name => $frequency) {
      $query = $this->connection->merge(self::TABLE);
      $query->fields([
        'uid' => $account->id(),
        'name' => $name,
        'frequency' => $frequency,
      ]);
      $query->keys([
        'uid' => $account->id(),
        'name' => $name,
      ]);
      $query->execute();
    }
  }

  /**
   * Get all bulk mailing frequencies for given user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account entity.
   *
   * @return array
   *   The list of frequencies associated by name.
   *
   * @throws \Exception
   */
  public function getBulkEmailUserSettings(AccountInterface $account): array {
    return (array) $this->connection->select($alias = self::TABLE)
      ->fields($alias, ['name', 'frequency'])
      ->condition('uid', $account->id())
      ->execute()
      ?->fetchAllKeyed();
  }

  /**
   * Get frequency for given user and bulk email name.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account entity.
   * @param string $name
   *   The bulk email setting name.
   *
   * @return string
   *   The frequency name.
   *
   * @throws \Exception
   */
  public function getBulkEmailUserSetting(AccountInterface $account, string $name): string {
    $frequency = (string) $this->connection->select($alias = self::TABLE)
      ->fields($alias, ['frequency'])
      ->condition('uid', $account->id())
      ->condition('name', $name)
      ->execute()
      ?->fetchField();

    return $frequency ?: self::FREQUENCY_IMMEDIATELY;
  }

}
