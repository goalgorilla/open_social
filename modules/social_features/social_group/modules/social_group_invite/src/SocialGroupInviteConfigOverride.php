<?php

namespace Drupal\social_group_invite;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an overridden elements.
 *
 * @package Drupal\social_group_invite
 */
class SocialGroupInviteConfigOverride implements ConfigFactoryOverrideInterface {

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
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs the configuration override.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   * @param \Drupal\Core\Database\Connection $database
   *   The current active database's master connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal configuration factory.
   */
  public function __construct(
    RequestStack $request_stack,
    EmailValidatorInterface $email_validator,
    Connection $database,
    ConfigFactoryInterface $config_factory
  ) {
    $this->requestStack = $request_stack;
    $this->emailValidator = $email_validator;
    $this->database = $database;
    $this->configFactory = $config_factory;
  }

  /**
   * Load overrides.
   */
  public function loadOverrides($names) {
    $overrides = [];

    $config_name = 'user.settings';

    // Get default verify_mail users settings.
    $enabled_verify_mail = $this->configFactory->getEditable($config_name)->get('verify_mail');

    // Get email_verification of social group invite settings.
    $group_invite = $this->configFactory->getEditable('social_group.settings');
    $ignore_email_verification = (bool) $group_invite->get('group_invite.email_verification');

    // Skip email verification step on registration for user group invitation.
    if (
      in_array($config_name, $names, TRUE) &&
      $ignore_email_verification === TRUE &&
      $enabled_verify_mail === TRUE
    ) {
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

    // Get decoded email of invited user from params.
    $invitee_mail = base64_decode(str_replace(['-', '_'], [
      '+',
      '/',
    ], $invitee_mail));

    if (!$this->emailValidator->isValid($invitee_mail)) {
      return FALSE;
    }

    // Get group id to which user was invited from params.
    preg_match('/\/social-group-invite\/(\d+)+\/accept/', $destination, $matches);
    $entity_id = array_pop($matches);

    if (empty($entity_id) || !is_numeric($entity_id)) {
      return FALSE;
    }

    // Verify is it really was requested invite and it still is actual.
    $query = $this->database->select('group_content__invitee_mail', 'gcim');
    $query->fields('gcim', ['entity_id']);
    $query->condition('gcim.invitee_mail_value', $invitee_mail);
    $query->condition('gcim.entity_id', $entity_id);
    $query->join('group_content__invitation_status', 'gcis', 'gcim.entity_id = gcis.entity_id');
    $query->condition('gcis.invitation_status_value', '0');

    $invitations = $query->execute()->fetchField();

    if (empty($invitations)) {
      return FALSE;
    }
    else {
      return TRUE;
    }
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
