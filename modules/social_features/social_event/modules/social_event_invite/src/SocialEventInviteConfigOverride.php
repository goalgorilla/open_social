<?php

namespace Drupal\social_event_invite;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an overridden block for Settings Tray testing.
 *
 * @package Drupal\social_event_invite
 */
class SocialEventInviteConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The current active database's master connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs the configuration override.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   * @param \Drupal\Core\Database\Connection $database
   *   The current active database's master connection.
   */
  public function __construct(
    RequestStack $request_stack,
    EmailValidatorInterface $email_validator,
    Connection $database
  ) {
    $this->requestStack = $request_stack;
    $this->emailValidator = $email_validator;
    $this->database = $database;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'user.settings';

    if (in_array($config_name, $names, TRUE)) {
      $request = $this->requestStack->getCurrentRequest();

      $invitee_mail = $request->query->get('invitee_mail', '');
      $destination = $request->query->get('destination', '');

      $is_valid = $this->validateInviteData($invitee_mail, $destination);

      if ($is_valid) {
        $overrides[$config_name]['verify_mail'] = FALSE;
      }
    }

    return $overrides;
  }

  /**
   * Validate invite data.
   *
   * @param string $invitee_mail
   *   Encoded email address of invited user.
   * @param string $destination
   *   The url of invited group.
   *
   * @return bool
   *   TRUE if invited data is valid.
   */
  public function validateInviteData($invitee_mail, $destination) {

    if (empty($invitee_mail) || empty($destination)) {
      return FALSE;
    }

    $invitee_mail = base64_decode(str_replace(['-', '_'], [
      '+',
      '/',
    ], $invitee_mail));

    if (!$this->emailValidator->isValid($invitee_mail)) {
      return FALSE;
    }

    $path = $this->getPathByAlias($destination);
    $path = explode('/', $path);
    $id = array_pop($path);

    if (empty($id) || !is_numeric($id)) {
      return FALSE;
    }

    // Verify is it really was requested invite and it still is actual.
    $query = $this->database->select('event_enrollment__field_email', 'eefe');

    $query->fields('eefe', ['entity_id']);
    $query->condition('eefe.field_email_value', $invitee_mail);

    $query->join('event_enrollment__field_request_or_invite_status', 'enfroi', 'eefe.entity_id = enfroi.entity_id');
    $query->condition('enfroi.field_request_or_invite_status_value', ['4', '5'], 'IN');

    $query->join('event_enrollment__field_event', 'eefev', 'eefe.entity_id = eefev.entity_id');
    $query->condition('eefev.field_event_target_id', $id);

    $invitations = $query->execute()->fetchField();

    if (empty($invitations)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Given the alias, return the path it represents.
   *
   * @param string $alias
   *   An alias.
   *
   * @return string
   *   The path represented by alias, or the alias if no path was found.
   */
  private function getPathByAlias($alias) {
    $query = $this->database->select('path_alias', 'base_table');
    $query->condition('base_table.status', '1');
    $query->fields('base_table', ['path']);
    $query->condition('base_table.alias', $this->database->escapeLike($alias), 'LIKE');

    if ($path = $query->execute()->fetchField()) {
      return $path;
    }

    return $alias;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'SocialGroupInviteConfigOverride';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * Creates a configuration object for use during install and synchronization.
   *
   * @param string $name
   *   The configuration object name.
   * @param string $collection
   *   The configuration collection.
   *
   * @return \Drupal\Core\Config\StorableConfigBase|null
   *   The configuration object for the provided name and collection.
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
