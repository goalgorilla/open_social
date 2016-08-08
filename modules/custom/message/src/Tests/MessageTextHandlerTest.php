<?php

/**
 * @file
 * Definition of Drupal\message\Tests\MessageTextHandlerTest.
 */

namespace Drupal\message\Tests;

use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

/**
 * Test the views text handler.
 *
 * @group Message
 */
class MessageTextHandlerTest extends MessageTestBase {

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

    $this->account = $this->drupalCreateUser(['overview messages']);
  }

  /**
   * Testing the deletion of messages in cron according to settings.
   */
  public function testTextHandler() {
    $this->createMessageType('dummy_message', 'Dummy message', '', ['Dummy text message']);
    Message::create(['type' => 'dummy_message'])->save();

    $this->drupalLogin($this->account);
    $this->drupalGet('admin/content/messages');
    $this->assertText('Dummy text message');
  }

}
