<?php

declare(strict_types=1);

namespace Drupal\social_event\Plugin\Validation\Constraint;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\social_event\Entity\Node\Event;
use Drupal\social_event\ExternalRegistrationUrl;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the registration URL field of an externally registered event.
 */
class ExternalEventRegistrationConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    assert($constraint instanceof ExternalEventRegistrationConstraint);

    if (!$value instanceof FieldItemListInterface) {
      return;
    }

    $event = $value->getEntity();
    if (!$event instanceof Event || !$event->isExternalRegistration()) {
      return;
    }

    $url = $event->getExternalRegistrationUrl();
    if ($url === NULL) {
      // An empty link is dropped before form errors are placed, so this
      // message belongs on the field rather than on a delta that no longer
      // exists.
      $this->context->buildViolation($constraint->missingUrlMessage)
        ->addViolation();
      return;
    }

    if (!ExternalRegistrationUrl::isValid($url)) {
      $this->context->buildViolation($constraint->invalidUrlMessage)
        ->atPath('0.uri')
        ->addViolation();
    }
  }

}
