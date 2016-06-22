<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileBaseTest.
 */

namespace Drupal\profile\Tests;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\profile\Entity\ProfileType;
use Drupal\simpletest\WebTestBase;
use Drupal\profile\Tests\ProfileTestTrait;

/**
 * Tests profile access handling.
 */
abstract class ProfileTestBase extends WebTestBase {

  use ProfileTestTrait;

  public static $modules = ['profile', 'field_ui', 'text', 'block'];

  /**
   * Testing profile type entity.
   *
   * @var \Drupal\profile\Entity\ProfileType
   */
  protected $type;

  /**
   * Testing profile type entity view display.
   *
   * @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   */
  protected $display;

  /**
   * Testing profile type entity form display.
   *
   * @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form
   */
  protected $form;

  /**
   * Testing field on profile type.
   *
   * @var \Drupal\Core\Field\FieldConfigInterface
   */
  protected $field;

  /**
   * Testing admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    $this->type = $this->createProfileType('test', 'Test profile', TRUE);

    $id = $this->type->id();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'profile_fullname',
      'entity_type' => 'profile',
      'type' => 'text',
    ]);
    $field_storage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $this->type->id(),
      'label' => 'Full name',
    ]);
    $this->field->save();

    // Configure the default display.
    $this->display = EntityViewDisplay::load("profile.{$this->type->id()}.default");
    if (!$this->display) {
      $this->display = EntityViewDisplay::create([
        'targetEntityType' => 'profile',
        'bundle' => $this->type->id(),
        'mode' => 'default',
        'status' => TRUE,
      ]);
      $this->display->save();
    }
    $this->display
      ->setComponent($this->field->getName(), ['type' => 'string'])
      ->save();

    // Configure rhe default form.
    $this->form = EntityFormDisplay::load("profile.{$this->type->id()}.default");
    if (!$this->form) {
      $this->form = EntityFormDisplay::create([
        'targetEntityType' => 'profile',
        'bundle' => $this->type->id(),
        'mode' => 'default',
        'status' => TRUE,
      ]);
      $this->form->save();
    }
    $this->form
      ->setComponent($this->field->getName(), [
        'type' => 'string_textfield',
      ])->save();

    $this->checkPermissions([
      'administer profile types',
      "view own $id profile",
      "view any $id profile",
      "add own $id profile",
      "add any $id profile",
      "edit own $id profile",
      "edit any $id profile",
      "delete own $id profile",
      "delete any $id profile",
    ]);

    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ['access user profiles']);
    $this->adminUser = $this->drupalCreateUser([
      'administer profile types',
      'administer profiles',
      "view any $id profile",
      "add any $id profile",
      "edit any $id profile",
      "delete any $id profile",
    ]);
  }

}
