<?php

namespace Drupal\social_landing_page\Plugin\Validation\Constraint;

use Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraintValidator;
use Drupal\user\Entity\User;
use Symfony\Component\Validator\Constraint;

/**
 * Validates the SocialAnLinkAccess constraint.
 */
class SocialAnLinkAccessValidator extends LinkAccessConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value->getFieldDefinition()->getName() !== 'field_button_link_an') {
      parent::validate($value, $constraint);
    }
    else {
      try {
        $url = $value->getUrl();
      }
      // If the URL is malformed this constraint cannot check access.
      catch (\InvalidArgumentException $e) {
        return;
      }
      // Disallow URLs if the anonymous user doesn't have access this URI.
      if (!$url->access(User::getAnonymousUser())) {
        // @phpstan-ignore-next-line
        $this->context->addViolation($constraint->message, ['@uri' => $value->uri]);
      }
    }
  }

}
