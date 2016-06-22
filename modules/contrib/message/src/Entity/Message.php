<?php

/**
 * @file
 * Contains Drupal\message\Entity\Message.
 */

namespace Drupal\message\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Language\Language;
use Drupal\Core\Render\Markup;
use Drupal\message\MessageInterface;
use Drupal\message\MessageTypeInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Message entity class.
 *
 * @ContentEntityType(
 *   id = "message",
 *   label = @Translation("Message"),
 *   bundle_label = @Translation("Message type"),
 *   module = "message",
 *   base_table = "message",
 *   data_table = "message_field_data",
 *   translatable = TRUE,
 *   bundle_entity_type = "message_type",
 *   entity_keys = {
 *     "id" = "mid",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode"
 *   },
 *   bundle_keys = {
 *     "bundle" = "type"
 *   },
 *   handlers = {
 *     "view_builder" = "Drupal\message\MessageViewBuilder",
 *     "list_builder" = "Drupal\message\MessageListBuilder",
 *     "views_data" = "Drupal\message\MessageViewsData",
 *   },
 *   field_ui_base_route = "entity.message_type.edit_form"
 * )
 */
class Message extends ContentEntityBase implements MessageInterface, EntityOwnerInterface {

  /**
   * The message ID.
   *
   * @var int
   */
  protected $mid;

  /**
   * The UUID string.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The message type object.
   *
   * @var \Drupal\message\MessageTypeInterface
   */
  protected $type;

  /**
   * The user object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $uid;

  /**
   * The time stamp the message was created.
   *
   * @var int
   */
  protected $created;

  /**
   * Holds the arguments of the message instance.
   *
   * @var array
   */
  protected $arguments;

  /**
   * The language to use when fetching text from the message type.
   *
   * @var string
   */
  protected $language = Language::LANGCODE_NOT_SPECIFIED;

  /**
   * {@inheritdoc}
   */
  public function setType(MessageTypeInterface $type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return MessageType::load($this->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUUID() {
    return $this->get('uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getArguments() {
    $arguments = $this->get('arguments')->getValue();

    // @todo: See if there is a easier way to get only the 0 key.
    return $arguments ? $arguments[0] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setArguments(array $values) {
    $this->set('arguments', $values);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['mid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Message ID'))
      ->setDescription(t('The message ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The message UUID'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The message type.'))
      ->setSetting('target_type', 'message_type')
      ->setReadOnly(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The message language code.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user that is the message author.'))
      ->setSettings([
        'target_type' => 'user',
        'default_value' => 0,
      ])
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the node was created.'))
      ->setTranslatable(TRUE);

    $fields['arguments'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Arguments'))
      ->setDescription(t('Holds the arguments of the message in serialise format.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getText($langcode = Language::LANGCODE_NOT_SPECIFIED, $delta = FALSE) {
    if (!$message_type = $this->getType()) {
      // Message type does not exist any more.
      // We don't throw an exception, to make sure we don't break sites that
      // removed the message type, so we silently ignore.
      return [];
    }

    $message_arguments = $this->getArguments();
    $message_type_text = $message_type->getText($langcode, $delta);

    $output = $this->processArguments($message_arguments, $message_type_text);

    $token_replace = $message_type->getSetting('token replace', TRUE);
    $token_options = $message_type->getSetting('token options');
    if (!empty($token_replace)) {
      // Token should be processed.
      $output = $this->processTokens($output, !empty($token_options['clear']));
    }

    return $output;
  }

  /**
   * Process the message given the arguments saved with it.
   *
   * @param array $arguments
   *   Array with the arguments.
   * @param array $output
   *   Array with the templated text saved in the message type.
   *
   * @return array
   *   The templated text, with the placehodlers replaced with the actual value,
   *   if there are indeed arguments.
   */
  protected function processArguments(array $arguments, array $output) {
    // Check if we have arguments saved along with the message.
    if (empty($arguments)) {
      return $output;
    }

    foreach ($arguments as $key => $value) {
      if (is_array($value) && !empty($value['callback']) && is_callable($value['callback'])) {

        // A replacement via callback function.
        $value += ['pass message' => FALSE];

        if ($value['pass message']) {
          // Pass the message object as-well.
          $value['callback arguments']['message'] = $this;
        }

        $arguments[$key] = call_user_func_array($value['callback'], $value['arguments']);
      }
    }

    foreach ($output as $key => $value) {
      $output[$key] = new FormattableMarkup($value, $arguments);
    }

    return $output;
  }

  /**
   * Replace placeholders with tokens.
   *
   * @param array $output
   *   The templated text to be replaced.
   * @param bool $clear
   *   Determine if unused token should be cleared.
   *
   * @return array
   *   The output with placeholders replaced with the token value,
   *   if there are indeed tokens.
   */
  protected function processTokens(array $output, $clear) {
    $options = [
      'langcode' => $this->language,
      'clear' => $clear,
    ];

    foreach ($output as $key => $value) {
      $output[$key] = \Drupal::token()
        ->replace($value, ['message' => $this], $options);
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $token_options = !empty($this->data['token options']) ? $this->data['token options'] : [];

    $tokens = [];

    // Handle hard coded arguments.
    foreach ($this->getType()->getText() as $text) {
      preg_match_all('/[@|%|\!]\{([a-z0-9:_\-]+?)\}/i', $text, $matches);

      foreach ($matches[1] as $delta => $token) {
        $output = \Drupal::token()->replace('[' . $token . ']', ['message' => $this], $token_options);
        if ($output != '[' . $token . ']') {
          // Token was replaced and token sanitizes.
          $argument = $matches[0][$delta];
          $tokens[$argument] = Markup::create($output);
        }
      }
    }

    $arguments = $this->getArguments();
    $this->setArguments(array_merge($tokens, $arguments));

    parent::save();
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\message\MessageInterface
   *   A message entity ready to be save.
   */
  public static function create(array $values = []) {
    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\message\MessageInterface
   *   A requested message entity.
   */
  public static function load($id) {
    return parent::load($id);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\message\MessageInterface[]
   *   Array of requested message entities.
   */
  public static function loadMultiple(array $ids = NULL) {
    return parent::loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteMultiple(array $ids) {
    \Drupal::entityTypeManager()->getStorage('message')->delete($ids);
  }

  /**
   * {@inheritdoc}
   */
  public static function queryByType($type) {
    return \Drupal::entityQuery('message')
      ->condition('type', $type)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return trim(implode("\n", $this->getText()));
  }

  /**
   * {@inheritdoc}
   */
  public function setLanguage($language) {
    $this->language = $language;
  }

}
