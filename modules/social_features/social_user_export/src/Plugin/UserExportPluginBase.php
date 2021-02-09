<?php

namespace Drupal\social_user_export\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for User export plugin plugins.
 */
abstract class UserExportPluginBase extends PluginBase implements UserExportPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  public $dateFormatter;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  public $database;

  /**
   * UserExportPluginBase constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->database = $database;
  }

  /**
   * The create method.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container interface.
   * @param array $configuration
   *   An array of configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   *
   * @return \Drupal\Core\Plugin\ContainerFactoryPluginInterface|\Drupal\social_user_export\Plugin\UserExportPluginBase
   *   Returns the UserExportPluginBase.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('database')
    );
  }

  /**
   * Returns the header.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   *   The header.
   */
  public function getHeader() {
    return '';
  }

  /**
   * Returns the value.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The User entity to get the value from.
   *
   * @return string
   *   The value.
   */
  public function getValue(UserInterface $entity) {
    return '';
  }

  /**
   * Get the Profile entity.
   *
   * @param \Drupal\user\UserInterface $entity
   *   The user entity to get the profile from.
   *
   * @return \Drupal\profile\Entity\ProfileInterface|null
   *   Returns the Profile or NULL if profile does not exist.
   */
  public function getProfile(UserInterface $entity) {
    $user_profile = NULL;

    /** @var \Drupal\profile\ProfileStorageInterface $storage */
    try {
      $storage = $this->entityTypeManager->getStorage('profile');
      if ($storage instanceof ProfileStorageInterface) {
        $user_profile = $storage->loadByUser($entity, 'profile');
      }
    }
    catch (\Exception $e) {
    }
    return $user_profile;
  }

  /**
   * Returns the value of a field for a given profile.
   *
   * @param string $field_name
   *   The field name to get the value for.
   * @param \Drupal\profile\Entity\ProfileInterface $user_profile
   *   The profile to get the data for.
   *
   * @return string
   *   Returns value of the field.
   */
  public function profileGetFieldValue($field_name, ProfileInterface $user_profile = NULL) {
    if ($user_profile === NULL) {
      return '';
    }

    try {
      $value = $user_profile->get($field_name)->value;
    }
    catch (\Exception $e) {
      $value = '';
    }
    return $value;
  }

  /**
   * Returns the value for the address field and element within address.
   *
   * @param string $field_name
   *   The field name to get the value for.
   * @param string $address_element
   *   The address element to get the value for, e.g. 'country_code'.
   * @param \Drupal\profile\Entity\ProfileInterface $user_profile
   *   The profile to get the data for.
   *
   * @return string
   *   Returns the value of the address element for the profile.
   */
  public function profileGetAddressFieldValue($field_name, $address_element, ProfileInterface $user_profile = NULL) {
    if ($user_profile === NULL) {
      return '';
    }

    $value = '';
    try {
      $address = $user_profile->get($field_name)->getValue();
      if (isset($address['0'][$address_element])) {
        $value = $address['0'][$address_element];
      }
    }
    catch (\Exception $e) {
      $value = '';
    }
    return $value;
  }

  /**
   * Returns the values of a taxonomy reference field.
   *
   * @param string $field_name
   *   The field name to get the value for, should be taxonomy term reference.
   * @param \Drupal\profile\Entity\ProfileInterface $user_profile
   *   The profile to get the data for.
   *
   * @return string
   *   Returns comma separated string of taxonomy terms of the field.
   */
  public function profileGetTaxonomyFieldValue($field_name, ProfileInterface $user_profile = NULL) {
    if ($user_profile === NULL) {
      return '';
    }

    $names = array_map(
      function (TermInterface $taxonomy_term) {
        return $taxonomy_term->getName();
      },
      $user_profile->get($field_name)->referencedEntities()
    );

    return implode(', ', $names);
  }

}
