<?php

namespace Drupal\social_profile\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
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
  public static function create(ContainerInterface $container) : self {
    return new static(
      $container->get('entity_type.manager')->getStorage('profile')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) : void {
    assert($constraint instanceof UniqueNickname);
    foreach ($items as $item) {
      // Next check if the value is unique.
      if (!$this->isUnique($item)) {
        $this->context->addViolation($constraint->notUnique, ['%value' => $item->value]);
      }
    }
  }

  /**
   * Checks if a nickname is unique.
   *
   * @param \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $item
   *   The provided nickname field item.
   *
   * @return bool
   *   Returns TRUE if the name is not taken. Returns FALSE if the name is
   *   taken.
   */
  private function isUnique(StringItem $item) : bool {
    // Get all profiles with the provided nickname.
    $profiles = $this->profileStorage->loadByProperties(['field_profile_nick_name' => $item->value]);

    // Get the profile we're performing actions on.
    $current_profile = $item->getEntity();
    assert($current_profile instanceof ProfileInterface, "The UniqueNickname constraint is used on a field that doesn't belong to a profile entity.");
    // Remove it from the count if it was already in there.
    unset($profiles[$current_profile->id()]);

    // If we have results, the name is taken.
    return count($profiles) === 0;
  }

}
