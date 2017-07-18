<?php

namespace Drupal\social_auth_extra;

/**
 * Interface AuthDataHandlerInterface.
 *
 * @package Drupal\social_auth_extra
 */
interface AuthDataHandlerInterface {

  /**
   * Set a key which will be used as prefix for keys in the storage.
   *
   * @param string $prefix
   *   Key to use as a prefix in the storage.
   *
   * @return null
   *   Returns null.
   */
  public function setPrefix($prefix);

  /**
   * Get a value from a persistent data store.
   *
   * @param string $key
   *   A key to request data for.
   *
   * @return mixed
   *   Value in the data store.
   */
  public function get($key);

  /**
   * Set a value in the persistent data store.
   *
   * @param string $key
   *   The key to store the value by.
   * @param mixed $value
   *   The value to store.
   *
   * @return null
   *   Returns null.
   */
  public function set($key, $value);

}
