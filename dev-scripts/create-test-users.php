<?php
/**
 * Create test-users.
 *
 * Normal user:
 * Username: normaluser
 * password: normaluser
 * Role: Authenticated user
 *
 * Administrator:
 * Username: administrator
 * Password: administrator
 * Role: Administrator
 */

// First create the normaluser.
$user = \Drupal\user\Entity\User::create();
$user->setPassword('normaluser');
$user->enforceIsNew();
$user->setEmail('drupalsocial+normaluser@goalgorilla.com');
$user->setUsername('normaluser');
$user->activate();
$res = $user->save();

// Create the administrator.
$user = \Drupal\user\Entity\User::create();
$user->setPassword('administrator');
$user->enforceIsNew();
$user->setEmail('drupalsocial+administrator@goalgorilla.com');
$user->setUsername('administrator');
$user->activate();
$user->addRole('administrator');
$res = $user->save();

