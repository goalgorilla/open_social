<?php

namespace Drupal\Tests\image_optimization\Kernel;

/**
 * Trait with methods needed by tests.
 */
trait KeysTrait {

  /**
   * The private key.
   *
   * @var string|null
   */
  protected ?string $privateKey = '-----BEGIN PRIVATE KEY-----
MIIBuQIBADANBgkqhkiG9w0BAQEFAASCAaMwggGfAgEAAlcCl8L3d0gfV0qoZuDO
tDKLcQ2i/owdVMSfycvhAbyy31U64vJRlIdXhU5kIVw2D2z+rQTKlMue3Vg1gjJl
A1PUKeOAyZiOKYQhZtYujzC0LZyoTNGnJA0CAwEAAQJXAZJYvoTxlP3m5XmnH+Uf
FmNrLrg52rW9klZSXYweBBdYpJ9Y35a3C5hk0k9p+zMiJAiGrCZuIleFdNdTojc1
yhh6WDiPJui2sCVj1gpoOTNHq/74ugCBAiwBs/6/ozmKiv/MPT/buncVXsmtW0yw
i9dbcxxJz3Tfk3LfeW+XP5b1St4tvQIsAYW8ZtzJIDCiRLhUlPgghpMGyvvIEX+p
niVywElmMk0cpLUk1XV+5qwJ7JECLACm9H5d+sLax2lmavWxSbidO41u0McqRaV3
RvXcw1x6EhsRXXIFn8D+kmXhAisai+q1vz1iEqt7osdC33RLL3tECyyl9XfANUDD
vyJN/lV5wTiI+EveDA8BAiwAxsx5oIsPmEeGhcJccS0f/09MtQrK3PG+Qya7FjsW
4wAZdTAGKjWiqo+UKw==
-----END PRIVATE KEY-----
';

  /**
   * The public key.
   *
   * @var string|null
   */
  protected ?string $publicKey = '-----BEGIN PUBLIC KEY-----
MHIwDQYJKoZIhvcNAQEBBQADYQAwXgJXApfC93dIH1dKqGbgzrQyi3ENov6MHVTE
n8nL4QG8st9VOuLyUZSHV4VOZCFcNg9s/q0EypTLnt1YNYIyZQNT1CnjgMmYjimE
IWbWLo8wtC2cqEzRpyQNAgMBAAE=
-----END PUBLIC KEY-----';

  /**
   * Set up public and private keys.
   */
  public function setUpKeys() {
    $public_key_path = 'public://public_key.pem';
    $private_key_path = 'public://private_key.pem';

    file_put_contents($public_key_path, $this->publicKey);
    file_put_contents($private_key_path, $this->privateKey);
    chmod($public_key_path, 0660);
    chmod($private_key_path, 0660);

    $settings = $this->config('image_optimization.settings');
    $settings->set('public_key', $public_key_path);
    $settings->set('private_key', $private_key_path);
    $settings->save();
  }

}
