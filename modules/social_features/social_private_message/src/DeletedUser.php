<?php

namespace Drupal\social_private_message;

/**
 * Deletes user.
 *
 * @package Drupal\social_private_message
 */
class DeletedUser {

  /**
   * The user ID.
   *
   * @var int
   */
  protected int $id;

  /**
   * DeletedUser constructor.
   *
   * @param int $id
   *   The user ID.
   */
  public function __construct($id) {
    $this->id = $id;
  }

  /**
   * Get user ID.
   *
   *   The user ID.
   */
  public function id(): int {
    return $this->id;
  }

}
