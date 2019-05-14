<?php

namespace Drupal\social_profile_fields\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class UniqueNicknameValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    foreach ($items as $item) {
      // Next check if the value is unique.
      if (!$this->isUnique($item->value)) {
        $this->context->addViolation($constraint->notUnique, ['%value' => $item->value]);
      }
    }
  }

  /**
   * Is nickname unique?
   *
   * @param string $value
   *   The provided nickname.
   *
   * @return bool
   *   Returns TRUE if the name is not taken. Returns FALSE if the name is
   *   taken.
   */
  private function isUnique($value) {
    // Get all profiles with the provided nickname.
    $storage = \Drupal::entityTypeManager()->getStorage('profile');
    $profiles = $storage->loadByProperties(['field_profile_nick_name' => $value]);

    // Remove current profile from profiles.
    foreach ($profiles as $key => $profile) {
      // Get current profile.
      $currentProfile = NULL;

      if ($profile->id() === $currentProfile->get('profile_id')->value) {
        unset($profiles[$key]);
      }
    }

    // If we have results, the name is taken.
    return count($profiles) === 0;
  }

}
