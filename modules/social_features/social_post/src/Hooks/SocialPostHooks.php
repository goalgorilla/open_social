<?php
declare(strict_types=1);

namespace Drupal\social_post\Hooks;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\hux\Attribute\Hook;
use Drupal\hux\Attribute\OriginalInvoker;
use Drupal\hux\Attribute\ReplaceOriginalHook;

/**
 * Usage examples.
 */
final class SocialPostHooks {

  #[Hook('entity_access')]
  public function myEntityAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    // A barebones implementation.
    return AccessResult::neutral();
  }

  #[Hook('entity_access', priority: 100)]
  public function myEntityAccess2(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    // You can set priority if you have multiple of the same hook!
    return AccessResult::neutral();
  }

  #[Hook('entity_access', moduleName: 'a_different_module', priority: 200)]
  public function myEntityAccess3(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    // You can masquerade as a different module!
    return AccessResult::neutral();
  }

  #[ReplaceOriginalHook(hook: 'entity_access', moduleName: 'media')]
  public function myEntityAccess4(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    // You can override hooks for other modules! E.g \media_entity_access()
    return AccessResult::neutral();
  }

  #[ReplaceOriginalHook(hook: 'entity_access', moduleName: 'media')]
  public function myEntityAccess5(EntityInterface $entity, string $operation, AccountInterface $account, #[OriginalInvoker] callable $originalInvoker): AccessResultInterface {
    // If you override a hook for another module, you can have the original
    // implementation passed to you as a callable!
    $originalResult = $originalInvoker($entity, $operation, $account);
    // Do something...
    return AccessResult::neutral();
  }

  #[Alter('user_format_name')]
  public function myCustomAlter(string &$name, AccountInterface $account): void {
    $name .= ' altered!';
  }

  #[
    Hook('entity_insert'),
    Hook('entity_delete'),
  ]
  public function myEntityAccess6(EntityInterface $entity): AccessResultInterface {
    // Associate with multiple!
    // Also works with Alters and Replacements.
    return AccessResult::neutral();
  }

  #[Alter('form_post_form')]
  public function formAlter(array $form, FormStateInterface $formState) {
    return $form;
  }
}
