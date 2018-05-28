<?php

namespace Drupal\social_private_message;

/**
 * Class DeletedUser.
 *
 * @package Drupal\social_private_message
 */
class DeletedUser {

  /**
   * The user ID.
   *
   * @var int
   */
  protected $id;

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
   * @return int
   *   The user ID.
   */
  public function id() {
    return $this->id;
  }

}
