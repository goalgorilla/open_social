<?php

namespace Drupal\image_optimization;

/**
 * Provides asymmetric encryption.
 */
trait AsymmetricCryptTrait {

  /**
   * Encrypt data.
   *
   * @param string $data
   *   The data to encrypt.
   * @param string $public_key
   *   The public key to encrypt with.
   *
   * @return string|null
   *   Returns the encrypted data.
   */
  protected function encrypt(string $data, string $public_key): ?string {
    openssl_public_encrypt($data, $encrypted_data, $public_key);
    $encrypted_data = base64_encode($encrypted_data);
    // Forward slashes and plus characters need to be replaced,
    // otherwise we can't extract the encrypted data from the URL.
    return str_replace(['/', '+'], ['_', '-'], $encrypted_data);
  }

  /**
   * Decrypt data.
   *
   * @param string $encrypted_data
   *   The data to decrypt.
   * @param string $private_key
   *   The private key to decrypt with.
   *
   * @return string|null
   *   Returns the decrypted data.
   */
  protected function decrypt(string $encrypted_data, string $private_key): ?string {
    $encrypted_data = str_replace(['_', '-'], ['/', '+'], $encrypted_data);
    openssl_private_decrypt(base64_decode($encrypted_data), $data, $private_key);
    return $data;
  }

}
