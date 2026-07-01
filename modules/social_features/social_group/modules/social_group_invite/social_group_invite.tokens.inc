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
 * Implements hook_tokens_alter().
 *
 * The ginvite module builds the "register_link" and "my_invitations_link"
 * tokens, but they must point at Open Social's invitation accept flow instead
 * of ginvite's default destinations. We let ginvite generate the base tokens
 * and only override the final replacements here.
 */
function social_group_invite_tokens_alter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata): void {
  if ($context['type'] !== 'group_content' || empty($context['data']['group_content'])) {
    return;
  }

  $tokens = $context['tokens'];

  // We only override these two tokens.
  if (!isset($tokens['register_link']) && !isset($tokens['my_invitations_link'])) {
    return;
  }

  $group_relationship = $context['data']['group_content'];
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

  // Attach the destination to the ginvite-provided register link so that after
  // a signup/sign in step the invite is accepted and the user is redirected to
  // the invited content. Only new users receive the register link.
  if (
    isset($tokens['register_link']) &&
    !empty($destination) &&
    isset($replacements[$tokens['register_link']])
  ) {
    $replacements[$tokens['register_link']] = "{$replacements[$tokens['register_link']]}&{$destination}";
  }

  // Existing users are sent straight to the invitation accept URL.
  if (isset($tokens['my_invitations_link']) && !empty($group_invite_url)) {
    $replacements[$tokens['my_invitations_link']] = $group_invite_url;
  }
}
