<?php

namespace Drupal\social_pwa;

use Drupal\user\UserInterface;

interface WebPushManagerInterface {

  public function getAuth(): array;

  public function getSubscriptionsForUser(UserInterface $user): array;

  public function removeSubscriptionsForUser(UserInterface $user, array $endpoints): void;
}
