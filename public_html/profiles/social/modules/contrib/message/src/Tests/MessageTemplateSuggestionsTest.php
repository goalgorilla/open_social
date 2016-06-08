<?php

/**
 * @file
 * Contains \Drupal\message\Tests\MessageTemplateSuggestionsTest.
 */

namespace Drupal\message\Tests;
use Drupal\message\Entity\Message;
use Drupal\user\Entity\User;

/**
 * Tests message template suggestions.
 *
 * @group Message
 */
class MessageTemplateSuggestionsTest extends MessageTestBase {

  /**
   * The user object.
   *
   * @var User
   */
  private $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalcreateuser();
  }

  /**
   * Tests if template_preprocess_message() generates the correct suggestions.
   */
  public function testMessageThemeHookSuggestions() {
    $type = 'dummy_message';
    // Create message to be rendered.
    $message_type = $this->createMessageType($type, 'Dummy message', '', ['[message:author:name]']);
    $message = Message::create(['type' => $message_type->id()])
      ->setOwner($this->user);

    $message->save();
    $view_mode = 'full';

    // Simulate theming of the message.
    $build = \Drupal::entityTypeManager()->getViewBuilder('message')->view($message, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_message', [$variables]);

    $this->assertEqual($suggestions, ['message__full', 'message__' . $type, 'message__' . $type . '__full', 'message__' . $message->id(), 'message__' . $message->id() . '__full'], 'Found expected message suggestions.');
  }

}
