<?php

namespace Drupal\activity_send_push_notification;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PushBase.
 *
 * @package Drupal\activity_send_push_notification
 */
abstract class PushBase extends PluginBase implements PushInterface {

  use StringTranslationTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current active user ID.
   *
   * @var int
   */
  protected $currentUserId;

  /**
   * Constructs a PushBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param int $current_user_id
   *   The current active user ID.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TranslationInterface $string_translation,
    ConfigFactoryInterface $config_factory,
    $current_user_id
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setStringTranslation($string_translation);

    $this->configFactory = $config_factory;
    $this->currentUserId = $current_user_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
      $container->get('config.factory'),
      $container->get('current_user')->id()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(FormStateInterface $form_state) {
  }

  /**
   * Returns the submitted form value for a specific key.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string|array $key
   *   Values are stored as a multi-dimensional associative array. If $key is a
   *   string, it will return $values[$key]. If $key is an array, each element
   *   of the array will be used as a nested key. If $key = array('foo', 'bar')
   *   it will return $values['foo']['bar'].
   * @param mixed $default
   *   (optional) The default value if the specified key does not exist.
   *
   * @return mixed
   *   The value for the given key, or NULL.
   */
  protected function getFormValue(FormStateInterface $form_state, $key, $default = NULL) {
    if (!is_array($key)) {
      $key = [$key];
    }

    $key = array_merge(['push_notifications', $this->getPluginId()], $key);

    return $form_state->getValue($key, $default);
  }

}
