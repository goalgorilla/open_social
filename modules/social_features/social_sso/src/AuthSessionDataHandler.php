<?php

namespace Drupal\social_sso;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class AuthSessionDataHandler
 * @package Drupal\social_sso
 */
class AuthSessionDataHandler implements AuthDataHandlerInterface  {

  /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
  protected $session;

  /** @var string $sessionPrefix */
  protected $sessionPrefix;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used for reading data from and writing data to session.
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function get($key) {
    return $this->session->get($this->sessionPrefix . $key);
  }

  /**
   * {@inheritdoc}
   */
  public function set($key, $value) {
    $this->session->set($this->sessionPrefix . $key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function setPrefix($prefix) {
    $this->sessionPrefix = $prefix;
  }

}