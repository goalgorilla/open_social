<?php

/**
 * @file
 * Definition of Drupal\Tests\message\Kernel\MessageTokenTest.
 */

namespace Drupal\Tests\message\Kernel;

use Drupal\Component\Utility\Html;
use Drupal\KernelTests\KernelTestBase;
use Drupal\message\Entity\Message;
use Drupal\message\Tests\MessageTypeCreateTrait;
use Drupal\user\Entity\User;

/**
 * Test the Message and tokens integration.
 *
 * @group Message
 */
class MessageTokenTest extends KernelTestBase {

  use MessageTypeCreateTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['message', 'user', 'system'];

  /**
   * The user object.
   *
   * @var User
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('message');
    $this->installEntitySchema('user');
    $this->user = User::create([
      'uid' => mt_rand(5, 10),
      'name' => $this->randomString(),
    ]);
    $this->user->save();
  }

  /**
   * Test token replacement in a message type.
   */
  public function testTokens() {
    $message_type = $this->createMessageType('dummy_message', 'Dummy message', '', ['[message:author:name]']);
    $message = Message::create(['type' => $message_type->id()])
      ->setOwnerId($this->user->id());

    $message->save();

    $this->assertEquals((string) $message, Html::escape($this->user->label()), 'The message rendered the author name.');
  }

  /**
   * Test clearing unused tokens.
   */
  public function testTokenClearing() {
    // Clearing enabled.
    $token_options = ['token options' => ['clear' => TRUE]];
    $message_type = $this->createMessageType('dummy_message', 'Dummy message', '', ['[message:author:name] [bogus:token]'], $token_options);
    $message = Message::create(['type' => $message_type->id()])
      ->setOwnerId($this->user->id());

    $message->save();

    $this->assertEquals((string) $message, Html::escape($this->user->label()), 'The message rendered the author name and stripped unused tokens.');

    // Clearing disabled.
    $token_options = ['token options' => ['clear' => FALSE]];
    $message_type->setSettings($token_options);
    $message_type->save();

    $this->assertEquals((string) $message, Html::escape($this->user->label() . ' [bogus:token]'), 'The message rendered the author name and did not strip the token.');
  }

  /**
   * Test the hard coded tokens.
   */
  public function testHardCodedTokens() {
    $random_text = $this->randomString();
    $token_messages = [
      'some text @{message:author} ' . $random_text,
      'some text %{message:author} ' . $random_text,
      'some text @{wrong:token} ' . $random_text,
    ];

    $replaced_messages = [
      'some text ' . Html::escape($this->user->label()) . ' ' . $random_text,
      'some text <em class="placeholder">' . Html::escape($this->user->label()) . '</em> ' . $random_text,
      'some text @{wrong:token} ' . $random_text,
    ];

    // Create the message type.
    $message_type = $this->createMessageType('dummy_message', 'Dummy message', '', $token_messages);

    // Assert the arguments.
    $original_message = Message::create([
      'type' => $message_type->id(),
      'uid' => $this->user->id(),
    ]);
    $this->assertTrue($original_message->getArguments() == FALSE, 'No message arguments exist prior to saving the message.');
    $original_message->save();
    // Make very, very sure the message arguments are not coming from the
    // object save created.
    \Drupal::entityTypeManager()->getStorage('message')->resetCache();
    $message = Message::load($original_message->id());
    $this->assertNotSame($message, $original_message);

    $arguments = $message->getArguments();
    $this->assertEquals(count($arguments), 2, 'Correct number of arguments added after saving the message.');

    // Assert message is rendered as expected.
    $this->assertEquals($replaced_messages, $message->getText(), 'The text rendered as expected.');
  }

}
