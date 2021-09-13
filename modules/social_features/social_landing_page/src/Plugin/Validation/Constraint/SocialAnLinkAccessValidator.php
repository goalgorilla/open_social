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
    // When we try to add some link to the 'field_button_link_an' field in the
    // Hero block on the landing page to which have access only AN users,
    // for example 'user/register', we received error that we do not have access
    // to the link and can not save landing page. That is why we need specific
    // validation for this field.
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
