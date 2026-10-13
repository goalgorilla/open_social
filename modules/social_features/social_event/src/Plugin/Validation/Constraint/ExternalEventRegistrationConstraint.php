<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Requires a valid registration URL when external registration is enabled.
 *
 * Added to the event registration URL field.
 */
#[Constraint(
  id: 'ExternalEventRegistration',
  label: new TranslatableMarkup('External event registration', [], ['context' => 'Validation'])
)]
class ExternalEventRegistrationConstraint extends SymfonyConstraint {

  /**
   * Error when external registration is enabled without a URL.
   */
  public string $missingUrlMessage = 'Enter a registration URL to use external registration.';

  /**
   * Error when the registration URL is not an absolute http(s) URL.
   */
  public string $invalidUrlMessage = 'Enter a valid registration URL starting with http:// or https://.';

}
