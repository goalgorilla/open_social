<?php

declare(strict_types=1);

namespace OpenSocial\TestBridge\Bridge;

use Consolidation\AnnotatedCommand\Attributes\Command;
use Drupal\Core\Config\ConfigFactoryInterface;
use OpenSocial\TestBridge\Shared\EntityTrait;
use Psr\Container\ContainerInterface;

class GDPRBridge {

  use EntityTrait;

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
  ) {}

  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('config.factory'),
    );
  }

  /**
   * Set the GDPR consent text.
   */
  #[Command('set-gpdr-consent-text')]
  public function setGdprContsentText(string $text) : array {
    $config = $this->configFactory->getEditable('data_policy.data_policy');

    if ($config->isNew()) {
      return ['status' => 'error', 'error' => "The data_policy.data_policy configuration did not yet exist, is the social_Gdpr module enabled?"];
    }

    $config->set('consent_text', $text)->save();

    return ['status' => 'ok'];
  }

}
