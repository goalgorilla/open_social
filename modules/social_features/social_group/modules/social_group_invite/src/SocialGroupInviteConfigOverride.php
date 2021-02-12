<?php

namespace Drupal\social_group_invite;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\ginvite\Plugin\GroupContentEnabler\GroupInvitation;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SocialGroupInviteConfigOverride.
 *
 * @package Drupal\social_group_invite
 */
class SocialGroupInviteConfigOverride implements ConfigFactoryOverrideInterface {

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $requestStack;

  /**
   * Constructs the configuration override.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   */
  public function __construct(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
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

    if (!\Drupal::service('email.validator')->isValid($invitee_mail)) {
      return FALSE;
    }

    $destination = explode('/', $destination);
    $gid = array_pop($destination);

    if (empty($gid) || !is_numeric($gid)) {
      return FALSE;
    }

    // Verify is it really was requested invite and it still is actual.
    $properties = [
      'gid' => $gid,
      'invitee_mail' => $invitee_mail,
      'invitation_status' => GroupInvitation::INVITATION_PENDING,
    ];

    /** @var \Drupal\ginvite\GroupInvitationLoaderInterface $invitations */
    $invitations = \Drupal::service('ginvite.invitation_loader')
      ->loadByProperties($properties);

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
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
