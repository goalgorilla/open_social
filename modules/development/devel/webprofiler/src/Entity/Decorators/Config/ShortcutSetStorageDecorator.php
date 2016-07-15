<?php

/**
 * @file
 * Contains \Drupal\webprofiler\Entity\Decorators\Config\ShortcutSetStorageDecorator.
 */

namespace Drupal\webprofiler\Entity\Decorators\Config;

use Drupal\Core\Session\AccountInterface;
use Drupal\shortcut\ShortcutSetInterface;
use Drupal\shortcut\ShortcutSetStorageInterface;

/**
 * Class ShortcutSetStorageDecorator
 */
class ShortcutSetStorageDecorator extends ConfigEntityStorageDecorator implements ShortcutSetStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function assignUser(ShortcutSetInterface $shortcut_set, $account) {
    // TODO: Implement assignUser() method.
  }

  /**
   * {@inheritdoc}
   */
  public function unassignUser($account) {
    // TODO: Implement unassignUser() method.
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAssignedShortcutSets(ShortcutSetInterface $entity) {
    // TODO: Implement deleteAssignedShortcutSets() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getAssignedToUser($account) {
    // TODO: Implement getAssignedToUser() method.
  }

  /**
   * {@inheritdoc}
   */
  public function countAssignedUsers(ShortcutSetInterface $shortcut_set) {
    // TODO: Implement countAssignedUsers() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultSet(AccountInterface $account) {
    // TODO: Implement getDefaultSet() method.
  }

}
