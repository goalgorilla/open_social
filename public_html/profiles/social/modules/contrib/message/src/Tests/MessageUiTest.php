<?php

/**
 * @file
 * Definition of Drupal\message\Tests\MessageUiTest.
 */

namespace Drupal\message\Tests;

use Drupal\Core\Language\Language;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\message\Entity\Message;
use Drupal\message\Entity\MessageType;
use Drupal\user\Entity\User;

/**
 * Testing the CRUD functionality for the Message type entity.
 *
 * @group Message
 */
class MessageUiTest extends MessageTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'config_translation', 'message'];

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
    $this->account = $this->drupalCreateUser(['administer message types', 'translate configuration']);
  }

  /**
   * Test the translation interface for message.
   */
  public function testMessageTranslate() {
    $this->drupalLogin($this->account);

    // Verifying creation of a message.
    $edit = [
      'label' => 'Dummy message',
      'type' => 'dummy_message',
      'description' => 'This is a dummy text',
      'text[0][value]' => 'This is a dummy message with some dummy text',
    ];
    $this->drupalPostForm('admin/structure/message/type/add', $edit, t('Save message type'));
    $this->assertText('The message type Dummy message created successfully.', 'The message created successfully');
    $this->drupalGet('admin/structure/message/manage/dummy_message');

    $elements = [
      '//input[@value="Dummy message"]' => 'The label input text exists on the page with the right text.',
      '//input[@value="This is a dummy text"]' => 'The description of the message exists on the page.',
      '//textarea[.="This is a dummy message with some dummy text"]' => 'The body of the message exists in the page.',
    ];
    $this->verifyFormElements($elements);

    // Verifying editing message.
    $edit = [
      'label' => 'Edited dummy message',
      'description' => 'This is a dummy text after editing',
      'text[0][value]' => 'This is a dummy message with some edited dummy text',
    ];
    $this->drupalPostForm('admin/structure/message/manage/dummy_message', $edit, t('Save message type'));

    $this->drupalGet('admin/structure/message/manage/dummy_message');

    $elements = [
      '//input[@value="Edited dummy message"]' => 'The label input text exists on the page with the right text.',
      '//input[@value="This is a dummy text after editing"]' => 'The description of the message exists on the page.',
      '//textarea[.="This is a dummy message with some edited dummy text"]' => 'The body of the message exists in the page.',
    ];
    $this->verifyFormElements($elements);

    // Add language.
    ConfigurableLanguage::create(['id' => 'he', 'name' => 'Hebrew'])->save();

    // Change to post form and add text different then the original.
    $edit = [
      'translation[config_names][message.type.dummy_message][label]' => 'Translated dummy message to Hebrew',
      'translation[config_names][message.type.dummy_message][description]' => 'This is a dummy text after translation to Hebrew',
      'text[0][value]' => 'This is a dummy message with translated text to Hebrew',
    ];
    $this->drupalPostForm('admin/structure/message/manage/dummy_message/translate/he/add', $edit, t('Save translation'));

    // Go to the edit form and verify text.
    $this->drupalGet('admin/structure/message/manage/dummy_message/translate/he/edit');

    $elements = [
      '//input[@value="Translated dummy message to Hebrew"]' => 'The text in the form translation is the expected string in Hebrew.',
      '//textarea[.="This is a dummy text after translation to Hebrew"]' => 'The description element have the expected value in Hebrew.',
      '//textarea[.="This is a dummy message with translated text to Hebrew"]' => 'The text element have the expected value in Hebrew.',
    ];
    $this->verifyFormElements($elements);

    // Load the message via code in hebrew and english and verify the text.
    $type = 'dummy_message';
    /* @var $message MessageType */
    $message = MessageType::load($type);
    if (empty($message)) {
      $this->fail('MessageType "' . $type . '" not found.');
    }
    else {
      $this->assertTrue($message->getText('he') == ['This is a dummy message with translated text to Hebrew'], 'The text in hebrew pulled correctly.');
      $this->assertTrue($message->getText() == ['This is a dummy message with some edited dummy text'], 'The text in english pulled correctly.');
    }

    // Delete message via the UI.
    $this->drupalPostForm('admin/structure/message/delete/' . $type, [], 'Delete');
    $this->assertText(t('There is no Message type yet.'));
    $this->assertFalse(MessageType::load($type), 'The message deleted via the UI successfully.');
  }

  /**
   * Test that message render returns message text wrapped in a div.
   */
  public function testMessageTextWrapper() {
    $type = 'dummy_message';
    // Create message to be rendered.
    $message_type = $this->createMessageType($type, 'Dummy message', '', ['Text to be wrapped by div.']);
    $message = Message::create(['type' => $message_type->id()])
      ->setOwner($this->account);

    $message->save();

    // Simulate theming of the message.
    $build = \Drupal::entityTypeManager()->getViewBuilder('message')->view($message);
    $output = \Drupal::service('renderer')->renderRoot($build);
    $this->setRawContent($output);
    $xpath = $this->xpath('//div');

    $this->assertTrue($xpath, 'A div has been found wrapping the message text.');
  }

  /**
   * Verifying the form elements values in easy way.
   *
   * When all the elements are passing a pass message with the text "The
   * expected values is in the form." When one of the Xpath expression return
   * false the message will be display on screen.
   *
   * @param array $elements
   *   Array mapped by in the next format.
   *
   * @code
   *   [XPATH_EXPRESSION => MESSAGE]
   * @endcode
   */
  private function verifyFormElements(array $elements) {
    $errors = [];
    foreach ($elements as $xpath => $message) {
      $element = $this->xpath($xpath);
      if (!$element) {
        $errors[] = $message;
      }
    }

    if (empty($errors)) {
      $this->pass('All elements were found.');
    }
    else {
      $this->fail('The next errors were found: ' . implode("", $errors));
    }
  }
}
