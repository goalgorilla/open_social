<?php

/**
 * @file
 * Contains \Drupal\social_user\Plugin\Validation\Constraint\UserNameConstraintValidator.
 */

namespace Drupal\social_user\Plugin\Validation\Constraint;

use Drupal\Core\TypedData\DataDefinition;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UserName constraint for Drupal Social
 * No email address are allowed in the username.
 */
class SocialUserNameConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {

    if ($name = $items->first()->value) {
      $definition = DataDefinition::create('string')->setConstraints(array('Email' => array()));
      $typed_data = \Drupal::typedDataManager()->create($definition, $name);
      $violations = $typed_data->validate();
      if (count($violations) == 0) {
        $this->context->addViolation($constraint->usernameIsEmailMessage);
      }
    }
  }
}
