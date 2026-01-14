<?php

declare(strict_types=1);

namespace Drupal\Tests\social_graphql\Kernel;

use Symfony\Component\Filesystem\Filesystem;

/**
 * A Kernel and Functional test safe trait to help with OAuth requests.
 *
 * @phpstan-require-extends \Drupal\KernelTests\KernelTestBase
 */
trait OAuthTestTrait {

  /**
   * The path to use for the public key.
   */
  protected ?string $publicKeyPath = NULL;

  /**
   * The path to use for the private key.
   */
  protected ?string $privateKeyPath = NULL;

  /**
   * The private key.
   */
  protected string $privateKey = <<<KEY
  -----BEGIN RSA PRIVATE KEY-----
  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
  -----END RSA PRIVATE KEY-----
  KEY;

  /**
   * The public key.
   */
  protected string $publicKey = <<<KEY
  -----BEGIN PUBLIC KEY-----
  MIICITANBgkqhkiG9w0BAQEFAAOCAg4AMIICCQKCAgBdtsRL+IfPTcany8zdYgAW
  1jmchFKc+UCXRxXjxlMhfhbJ3xgOD+kcdirzpa9squFwsS5Dnt1ZH/d8X3GdgqDu
  VSk6UnwDwB2HVNlz7P8G9WjOTPmlLzqYWFaBR7Kahzq0hYiua+dGKVKkf4WkkBsJ
  d128VV+niLmbq71759B5ZUZCBc/6/I1PR7XrZnJexf3LbtO/SnPX/tj/8hNMIM7a
  qVZ1VyBaze36l1rX4IUOrkSK2wVz1TSwj848xaX7Bjv45F98zXxffrGVd2pykVSO
  XihRyuB3niERHZzxmLdDIIFZNrpDFnm5Ma6VywBhzis5ZwcKP87PMhCDkvy+pJgQ
  IAJK52OOfTgKnpmdEvQUbn6YBLF5kY3nKDPOcwPlaGsAT4jtGWeW0MLU3IFjr+u4
  hKPILwRpfb2qWaAiINtkZumSlAf6ZjzfLPw+bCAIAGbn6UGMqujgp+SAWmxGsr0J
  yUFRPl+yYW4X8Tqb3bh0//PIjXGFMmreEkIuHqk3FT1Wha9dEdAWW5pBWGqCY3VK
  Az5a8WEdJ9eJh43zHhbOO5RVw1D22nDMeVKi3So6JvxCgla37o9W1P8XWSMQTo8z
  3CyPSYSb4DXHF5RHZyZB/c3M5am4zZMI4PSpEgbFHZFXi/rj9jHPrrNm34k/3ey7
  jKxttfsAUqhcu9tCJx+7DwIDAQAB
  -----END PUBLIC KEY-----
  KEY;

  /**
   * Set up public and private keys.
   */
  public function setUpKeys() : void {
    // Use the path that's defined if it was set explicitly, otherwise default
    // to the site directory for kernel tests or use the private filesystem if
    // we don't have a site directory available.
    $this->publicKeyPath ??= "{$this->siteDirectory}/keys/public.key";
    $this->privateKeyPath ??= "{$this->siteDirectory}/keys/private.key";

    $fs = new Filesystem();
    $fs->dumpFile($this->publicKeyPath, $this->publicKey);
    $fs->dumpFile($this->privateKeyPath, $this->privateKey);
    $fs->chmod([$this->publicKeyPath, $this->privateKeyPath], 0660);

    $settings = $this->config('simple_oauth.settings');
    $settings->set('public_key', $this->publicKeyPath);
    $settings->set('private_key', $this->privateKeyPath);
    $settings->save();
  }

}
