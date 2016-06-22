<?php

/**
 * @file
 * Contains \Drupal\message\Entity\MessageType.
 */

namespace Drupal\message\Entity;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\Language;
use Drupal\language\ConfigurableLanguageManagerInterface;
use Drupal\message\MessageException;
use Drupal\message\MessageTypeInterface;


/**
 * Defines the Message type entity class.
 *
 * @ConfigEntityType(
 *   id = "message_type",
 *   label = @Translation("Message type"),
 *   config_prefix = "type",
 *   bundle_of = "message",
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "label",
 *     "langcode" = "langcode",
 *   },
 *   admin_permission = "administer message types",
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\message\Form\MessageTypeForm",
 *       "edit" = "Drupal\message\Form\MessageTypeForm",
 *       "delete" = "Drupal\message\Form\MessageTypeDeleteConfirm"
 *     },
 *     "list_builder" = "Drupal\message\MessageTypeListBuilder",
 *     "view_builder" = "Drupal\message\MessageViewBuilder",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/message/type/add",
 *     "edit-form" = "/admin/structure/message/manage/{message_type}",
 *     "delete-form" = "/admin/structure/message/delete/{message_type}"
 *   }
 * )
 */
class MessageType extends ConfigEntityBundleBase implements MessageTypeInterface {

  /**
   * The ID of this message type.
   *
   * @var string
   */
  protected $type;

  /**
   * The UUID of the message type.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The human-readable name of the message type.
   *
   * @var string
   */
  protected $label;

  /**
   * A brief description of this message type.
   *
   * @var string
   */
  protected $description;

  /**
   * The serialised text of the message type.
   *
   * @var array
   */
  protected $text = [];

  /**
   * Array with the arguments and their replacement value, or callacbks.
   *
   * The argument keys will be replaced when rendering the message, and it
   * should be prefixed by @, %, ! - similar to way it's done in Drupal
   * core's t() function.
   *
   * @code
   *
   * // Assuming out message-text is:
   * // %user-name created <a href="@message-url">@message-title</a>
   *
   * $message_type->arguments = [
   *   // Hard code the argument.
   *   '%user-name' => 'foo',
   *
   *   // Use a callback, and provide callbacks arguments.
   *   // The following example will call Drupal core's url() function to
   *   // get the most up-to-date path of message ID 1.
   *   '@message-url' => [
   *      'callback' => 'url',
   *      'callback arguments' => ['message/1'],
   *    ],
   *
   *   // Use callback, but instead of passing callback argument, we will
   *   // pass the Message entity itself.
   *   '@message-title' => [
   *      'callback' => 'example_bar',
   *      'pass message' => TRUE,
   *    ],
   * ];
   * @endcode
   *
   * Arguments assigned to message-type can be overridden by the ones
   * assigned to the message.
   *
   * @var array
   */
  public $arguments = [];

  /**
   * Serialized array with misc options.
   *
   * Purge settings (under $message_type->data['purge]). Note that the
   * purge settings can be added only to the message-type.
   * - 'enabled': TRUE or FALSE to explicitly enable or disable message
   *    purging. IF not set, the default purge settings defined in the
   *    "Message settings" will apply.
   * - 'quota': Optional; Maximal (approximate) amount of allowed messages
   *    of the message type. IF not set, the default purge settings defined in
   *    the "Message settings" will apply.
   * - 'days': Optional; Maximal message age in days. IF not set, the default
   *    purge settings defined in the
   *    "Message settings" will apply.
   *
   * Token settings:
   * - 'token replace': Indicate if message's text should be passed
   *    through token_replace(). defaults to TRUE.
   * - 'token options': Array with options to be passed to
   *    token_replace().
   *
   * Tokens settings assigned to message-type can be overriden by the ones
   * assigned to the message.
   *
   * @var array
   *
   * @todo: A better name would be $settings, however we might want to keep this
   * for easier migration from Drupal 7?
   */
  public $settings = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $this->settings = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default_value = NULL) {
    if (isset($this->settings[$key])) {
      return $this->settings[$key];
    }

    return $default_value;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->type = $type;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setUuid($uuid) {
    $this->uuid = $uuid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getText($langcode = Language::LANGCODE_NOT_SPECIFIED, $delta = NULL) {
    $text = $this->text;

    $language_manager = \Drupal::languageManager();
    if ($language_manager instanceof ConfigurableLanguageManagerInterface) {

      if ($langcode == Language::LANGCODE_NOT_SPECIFIED) {
        // Get the default language code when not specified.
        $langcode = $language_manager->getDefaultLanguage()->getId();
      }

      $config_translation = $language_manager->getLanguageConfigOverride($langcode, 'message.type.' . $this->id());
      $translated_text = $config_translation->get('text');

      // If there was no translated text, we return nothing instead of falling
      // back to the default language.
      $text = $translated_text ?: [];
    }

    if ($delta) {
      // Return just the delta if it exists.
      return !empty($text[$delta]) ?: '';
    }

    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function isLocked() {
    return !$this->isNew();
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\message\MessageTypeInterface
   *   A message type object ready to be save.
   */
  public static function create(array $values = []) {
    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $this->text = array_filter($this->text);

    $language_manager = \Drupal::languageManager();

    if ($language_manager instanceof ConfigurableLanguageManagerInterface) {
      // Set the values for the default site language.
      $config_translation = $language_manager->getLanguageConfigOverride($language_manager->getDefaultLanguage()->getId(), 'message.type.' . $this->id());
      $config_translation->set('text', $this->text);
      $config_translation->save();
    }

    parent::preSave($storage);
  }

}
