<?php

namespace Drupal\social_magic_login\Service;


use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;

/**
 * Class MagicLoginCreate.
 */
class MagicUrlCreate {

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Create a magic login link.
   *
   * @param int $uid
   *   The uid of the user.
   * @param string $destination
   *   The destination.
   */
  public function create($uid, $destination) {
    // 1. Get user and check if this is allowed to use a link.
    // 2. Generate some kind of hash to identify the user.
    // 3. Get the destination the user is supposed to go to.
    // 4. Store the ID hash, destination and user ID.
    // 5. Generate the link.
  }

}
