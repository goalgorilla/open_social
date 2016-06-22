<?php

/**
 * @file
 * Definition of Drupal\message\Tests\MessageCron.
 */

namespace Drupal\message\Tests;

use Drupal\message\Entity\Message;
use Drupal\message\Entity\MessageType;
use Drupal\user\Entity\User;

/**
 * Test message purging upon cron.
 *
 * @group Message
 */
class MessageCron extends MessageTestBase {

  /**
   * The user object.
   *
   * @var User
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->account = $this->drupalCreateUser();
  }

  /**
   * Testing the deletion of messages in cron according to settings.
   */
  public function testPurge() {
    // Create a purgeable message type with max quota 2 and max days 0.
    $settings = [
      'purge' => [
        'override' => TRUE,
        'enabled' => TRUE,
        'quota' => 2,
        'days' => 0,
      ],
    ];

    /** @var MessageType $message_type */
    $message_type = MessageType::create(['type' => 'type1']);
    $message_type
      ->setSettings($settings)
      ->save();

    // Make sure the purging data is actually saved.
    $this->assertEqual($message_type->getSetting('purge'), $settings['purge'], t('Purge settings are stored in message type.'));

    // Create a purgeable message type with max quota 1 and max days 2.
    $settings['purge']['quota'] = 1;
    $settings['purge']['days'] = 2;
    $message_type = MessageType::create(['type' => 'type2']);
    $message_type
      ->setSettings($settings)
      ->save();

    // Create a non purgeable message type with max quota 1 and max days 10.
    $settings['purge']['enabled'] = FALSE;
    $settings['purge']['quota'] = 1;
    $settings['purge']['days'] = 1;
    $message_type = MessageType::create(['type' => 'type3']);
    $message_type
      ->setSettings($settings)
      ->save();

    // Create messages.
    for ($i = 0; $i < 4; $i++) {
      Message::Create(['type' => 'type1'])
        ->setCreatedTime(time() - 3 * 86400)
        ->setOwnerId($this->account->id())
        ->save();
    }

    for ($i = 0; $i < 3; $i++) {
      Message::Create(['type' => 'type2'])
        ->setCreatedTime(time() - 3 * 86400)
        ->setOwnerId($this->account->id())
        ->save();
    }

    for ($i = 0; $i < 3; $i++) {
      Message::Create(['type' => 'type3'])
        ->setCreatedTime(time() - 3 * 86400)
        ->setOwnerId($this->account->id())
          ->save();
    }

    // Trigger message's hook_cron().
    message_cron();

    // Four type1 messages were created. The first two should have been
    // deleted.
    $this->assertFalse(array_diff(Message::queryByType('type1'), [3, 4]), 'Two messages deleted due to quota definition.');

    // All type2 messages should have been deleted.
    $this->assertEqual(Message::queryByType('type2'), [], 'Three messages deleted due to age definition.');

    // type3 messages should not have been deleted.
    $this->assertFalse(array_diff(Message::queryByType('type3'), [8, 9, 10]), 'Messages with disabled purging settings were not deleted.');
  }

  /**
   * Testing the purge request limit.
   */
  public function testPurgeRequestLimit() {
    // Set maximal amount of messages to delete.
    \Drupal::configFactory()->getEditable('message.settings')
      ->set('delete_cron_limit', 10)
      ->save();

    // Create a purgeable message type with max quota 2 and max days 0.
    $data = [
      'purge' => [
        'override' => TRUE,
        'enabled' => TRUE,
        'quota' => 2,
        'days' => 0,
      ],
    ];

    MessageType::create(['type' => 'type1'])
      ->setSettings($data)
      ->save();

    MessageType::create(['type' => 'type2'])
      ->setSettings($data)
      ->save();

    // Create more messages than may be deleted in one request.
    for ($i = 0; $i < 10; $i++) {
      Message::Create(['type' => 'type1'])
        ->setOwnerId($this->account->id())
        ->save();
      Message::Create(['type' => 'type2'])
        ->setOwnerId($this->account->id())
        ->save();
    }

    // Trigger message's hook_cron().
    message_cron();

    // There are 16 messages to be deleted and 10 deletions allowed, so 8
    // messages of type1 and 2 messages of type2 should be deleted, thus 2
    // messages of type1 and 8 messages of type2 remain.
    $this->assertEqual(count(Message::queryByType('type1')), 2, t('Two messages of type 1 left.'));

    $this->assertEqual(count(Message::queryByType('type2')), 8, t('Eight messages of type 2 left.'));
  }

  /**
   * Test global purge settings and overriding them.
   */
  public function testPurgeGlobalSettings() {
    // Set global purge settings.
    \Drupal::configFactory()->getEditable('message.settings')
      ->set('purge_enable', TRUE)
      ->set('purge_quota', 1)
      ->set('purge_days', 2)
      ->save();

    MessageType::create(['type' => 'type1'])->save();

    // Create an overriding type.
    $data = [
      'purge' => [
        'override' => TRUE,
        'enabled' => FALSE,
        'quota' => 1,
        'days' => 1,
      ],
    ];

    MessageType::create(['type' => 'type2'])
      ->setSettings($data)
      ->save();

    for ($i = 0; $i < 2; $i++) {
      Message::create(['type' => 'type1'])
        ->setCreatedTime(time() - 3 * 86400)
        ->setOwnerId($this->account->id())
        ->save();

      Message::create(['type' => 'type2'])
        ->setCreatedTime(time() - 3 * 86400)
        ->setOwnerId($this->account->id())
        ->save();
    }

    // Trigger message's hook_cron().
    message_cron();

    $this->assertEqual(count(Message::queryByType('type1')), 0, t('All type1 messages deleted.'));
    $this->assertEqual(count(Message::queryByType('type2')), 2, t('Type2 messages were not deleted due to settings override.'));
  }
}
