<?php

/**
 * @file
 * Builds placeholder replacement tokens related to the module.
 */

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupRelationshipInterface;

/**
 * Implements hook_tokens().
 *
 * This function needs mainly to replace "ginvite_tokens()" tokens build.
 */
function social_group_invite_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  if ($type !== 'group_content' || empty($data[$type])) {
    return [];
  }

  // We need to build only these two tokens.
  if (
    !isset($tokens['register_link']) &&
    !isset($tokens['my_invitations_link'])
  ) {
    return [];
  }

  $group_relationship = $data['group_content'];
  assert($group_relationship instanceof GroupRelationshipInterface);

  $gcid = $group_relationship->id();

  if ($gcid) {
    $group_invite_url = Url::fromRoute(
      route_name: 'social_group_invite.invitation.accept',
      route_parameters: ['group_content' => $gcid],
      options: ['absolute' => TRUE],
    )->toString();

    // We are using a destination for use with the register link.
    // This destination is only used when user's don't have an account yet.
    $destination = UrlHelper::buildQuery(['destination' => $group_invite_url]);
  }

  // For groups that only group members have access to, we should change the
  // destination to the user's invites page.
  $group = $group_relationship->getGroup();
  if (
    $group->hasField('field_flexible_group_visibility') &&
    $group->get('field_flexible_group_visibility')->getString() === 'members'
  ) {
    // Update the destination for use with the register link.
    $url = Url::fromRoute('social_core.my_invites')->toString();
    $destination = UrlHelper::buildQuery(['destination' => $url]);
    $group_invite_url = Url::fromRoute('social_core.my_invites', [], ['absolute' => TRUE])->toString();
  }

  // Attach destination to accept invite.
  // So, after a signup/sign step, the invite will be accepted and
  // a user will be redirected to the invited page.
  foreach ($tokens as $name => $original) {
    if ($name === 'register_link' && !empty($destination)) {
      // To avoid duplication we want to get only "register_link" token from
      // the "ginvite" module.
      $ginvite_replacements = ginvite_tokens($type, ['register_link' => $tokens['register_link']], $data, $options, $bubbleable_metadata);

      // For new users for register_link token.
      $link = $ginvite_replacements[$original];
      $replacements[$original] = "{$link}&{$destination}";
    }

    if ($name === 'my_invitations_link' && !empty($group_invite_url)) {
      // For existing users for my_invitations_link token.
      $replacements[$original] = $group_invite_url;
    }
  }

  return $replacements ?? [];
}
