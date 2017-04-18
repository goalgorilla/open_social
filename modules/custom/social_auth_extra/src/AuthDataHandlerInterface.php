<?php

namespace Drupal\social_auth_extra;

interface AuthDataHandlerInterface {

  /**
   * Set a key which will be used as prefix for keys in the storage.
   *
   * @param string $prefix
   *
   * @return null
   */
  public function setPrefix($prefix);

  /**
   * Get a value from a persistent data store.
   *
   * @param string $key
   *
   * @return mixed
   */
  public function get($key);

  /**
   * Set a value in the persistent data store.
   *
   * @param string $key
   * @param mixed $value
   *
   * @return null
   */
  public function set($key, $value);

}
