<?php

namespace Drupal\social_profile_fields\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\profile\ProfileStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueInteger constraint.
 */
class UniqueNicknameValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Profile storage.
   *
   * @var \Drupal\profile\ProfileStorageInterface
   */
  protected $profileStorage;

  /**
   * UniqueNicknameValidator constructor.
   *
   * @param \Drupal\profile\ProfileStorageInterface $profile_storage
   *   Profile storage.
   */
  public function __construct(ProfileStorageInterface $profile_storage) {
    $this->profileStorage = $profile_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $item */
    foreach ($items as $item) {
      // Next check if the value is unique.
      if (
        !$item->isEmpty() &&
        !$this->isUnique($item->get('value')->getString())
      ) {
        $this->context->addViolation($constraint->notUnique, [
          '%value' => $item->get('value')->getString(),
        ]);
      }
    }
  }

  /**
   * Checks if a nickname is unique.
   *
   * @param string $value
   *   The provided nickname.
   *
   * @return bool
   *   Returns TRUE if the name is not taken. Returns FALSE if the name is
   *   taken.
   */
  private function isUnique(string $value): bool {
    // Get all profiles with the provided nickname.
    $profiles = $this->profileStorage->loadByProperties(['field_profile_nick_name' => $value]);

    // Remove current profile from profiles.
    foreach ($profiles as $key => $profile) {
      // Get the profile we're performing actions on.
      /** @var \Drupal\profile\Entity\ProfileInterface $current_profile */
      $current_profile = _social_profile_get_profile_from_route();

      if (
        $current_profile instanceof ProfileInterface &&
        $profile->id() === $current_profile->get('profile_id')->getString()
      ) {
        unset($profiles[$key]);
      }
    }

    // If we have results, the name is taken.
    return count($profiles) === 0;
  }

}
