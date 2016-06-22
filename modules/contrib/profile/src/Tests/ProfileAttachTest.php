<?php

/**
 * @file
 * Contains \Drupal\profile\Tests\ProfileAttachTest.
 */

namespace Drupal\profile\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;

/**
 * Tests attaching of profile entity forms to other forms.
 *
 * @group profile
 */
class ProfileAttachTest extends ProfileTestBase {

  /**
   * Test user registration integration.
   */
  public function testUserRegisterForm() {
    $id = $this->type->id();
    $field_name = $this->field->getName();

    $this->field->setRequired(TRUE);
    $this->field->save();

    // Allow registration without administrative approval and log in user
    // directly after registering.
    \Drupal::configFactory()->getEditable('user.settings')
      ->set('register', USER_REGISTER_VISITORS)
      ->set('verify_mail', 0)
      ->save();
    user_role_grant_permissions(AccountInterface::AUTHENTICATED_ROLE, ['view own test profile']);

    // Verify that the additional profile field is attached and required.
    $name = $this->randomMachineName();
    $pass_raw = $this->randomMachineName();
    $edit = [
      'name' => $name,
      'mail' => $this->randomMachineName() . '@example.com',
      'pass[pass1]' => $pass_raw,
      'pass[pass2]' => $pass_raw,
    ];
    $this->drupalPostForm('user/register', $edit, t('Create new account'));

    $this->assertRaw(new FormattableMarkup('@name field is required.', ['@name' => $this->field->getLabel()]));

    // Verify that we can register.
    $edit["entity_" . $id . "[$field_name][0][value]"] = $this->randomMachineName();
    $this->drupalPostForm(NULL, $edit, t('Create new account'));
    $this->assertText(new FormattableMarkup('Registration successful. You are now logged in.', []));

    $new_user = user_load_by_name($name);
    $this->assertTrue($new_user->isActive(), 'New account is active after registration.');

    // Verify that a new profile was created for the new user ID.
    $profile = \Drupal::entityTypeManager()
      ->getStorage('profile')
      ->loadByUser($new_user, $this->type->id());

    $this->assertEqual($profile->get($field_name)->value, $edit["entity_" . $id . "[$field_name][0][value]"], 'Field value found in loaded profile.');

    // Verify that the profile field value appears on the user account page.
    $this->drupalGet('user');
    $this->assertText($edit["entity_" . $id . "[$field_name][0][value]"], 'Field value found on user account page.');
  }

}
