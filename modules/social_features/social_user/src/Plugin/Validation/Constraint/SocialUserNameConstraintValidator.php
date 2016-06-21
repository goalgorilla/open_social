<?php

namespace Drupal\social_user\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UserName constraint no email address allowed in the username.
 */
class SocialUserNameConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {
  protected $typedDataManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      // Load the service required to construct this class.
      $container->get('typed_data_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (is_null($items)) {
      return;
    }

    if ($nameField = $items->first()) {
      $name = $nameField->__get('value');
      $definition = DataDefinition::create('string')->setConstraints(array('Email' => array()));
      $typed_data = $this->typedDataManager->create($definition, $name);
      $violations = $typed_data->validate();
      if (count($violations) == 0) {
        $this->context->addViolation($constraint->usernameIsEmailMessage);
      }
    }
  }

}
