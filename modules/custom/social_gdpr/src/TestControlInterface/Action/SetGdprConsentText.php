<?php

declare(strict_types=1);

namespace Drupal\social_gdpr\TestControlInterface\Action;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\social_gdpr\TestControlInterface\Success\GdprConsentTextSetSuccess;
use Drupal\test_control_interface\Attribute\Example;
use Drupal\test_control_interface\Attribute\Operation;
use Drupal\test_control_interface\Enum\OperationType;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * TCI endpoint for GDPR consent text config.
 *
 * Replaces GDPRContext::setGdprContsentText().
 */
final class SetGdprConsentText {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Sets the GDPR consent text configuration.
   *
   * @return \Drupal\social_gdpr\TestControlInterface\Success\GdprConsentTextSetSuccess
   *   The operation result.
   */
  #[Operation(
    id: 'gdpr_consent_text_set',
    patterns: [
      'set the GDPR Consent Text to :text',
      'I set the GDPR Consent Text to :text',
    ],
    type: OperationType::Write,
  )]
  public function setConsentText(
    #[Example('I read and consent to the [id:1]')]
    string $text,
  ): GdprConsentTextSetSuccess {
    $config = $this->configFactory->getEditable('data_policy.data_policy');
    if ($config->isNew()) {
      throw new BadRequestHttpException('The data_policy.data_policy configuration did not yet exist, is the social_gdpr module enabled?');
    }

    $config->set('consent_text', $text)->save();

    return new GdprConsentTextSetSuccess();
  }

}
