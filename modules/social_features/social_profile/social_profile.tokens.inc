<?php

/**
 * @file
 * Builds placeholder replacement tokens for the Social Profile module.
 */

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\social_profile\Entity\ProfileAffiliationInterface;
use Drupal\social_profile\Service\AffiliationDisplay;
use Drupal\user\UserInterface;

/**
 * Implements hook_token_info().
 */
function social_profile_token_info(): array {
  $types['primary-affiliation'] = [
    'name' => t('Primary affiliation'),
    'description' => t('Tokens for the primary affiliation of a user.'),
    'needs-data' => 'primary-affiliation',
  ];

  $tokens['user']['primary-affiliation'] = [
    'name' => t('Primary affiliation'),
    'description' => t('The primary affiliation of the user. Without a suffix this behaves as the "link" variant.'),
    'type' => 'primary-affiliation',
  ];

  $tokens['primary-affiliation']['link'] = [
    'name' => t('Link'),
    'description' => t('The primary affiliation, linked to the affiliated group and prefixed with " @".'),
  ];

  $tokens['primary-affiliation']['raw'] = [
    'name' => t('Raw'),
    'description' => t('The primary affiliation as plain text, prefixed with " @".'),
  ];

  return [
    'types' => $types,
    'tokens' => $tokens,
  ];
}

/**
 * Implements hook_tokens().
 */
function social_profile_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  if ($type === 'user') {
    // Map every requested affiliation token onto the variant it asks for. The
    // bare token is an alias for the linked variant, and it is answered on
    // purpose: message templates ship with 'token options': {clear: FALSE}, so
    // a token nobody replaces ends up in the mail body verbatim.
    $variants = [];
    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'primary-affiliation':
        case 'primary-affiliation:link':
          $variants[$original] = 'link';
          break;

        case 'primary-affiliation:raw':
          $variants[$original] = 'raw';
          break;
      }
    }

    // Seed an empty replacement for each of them, for the same reason. Every
    // branch below that shows nothing leaves these in place.
    $replacements = array_fill_keys(array_keys($variants), '');

    $account = $data['user'] ?? NULL;

    // The affiliation belongs on the on-platform activity streams only. Every
    // other channel (email, digest, push, the notification centre) renders
    // without an activity view mode and is denied here. An allowlist rather
    // than a denylist, so a new view mode does not start leaking by default:
    // "default" covers the stream views, "featured" the dashboard stream block.
    if (
      $variants !== [] &&
      $account instanceof UserInterface &&
      in_array($options['activity_view_mode'] ?? NULL, ['default', 'featured'], TRUE)
    ) {
      /** @var \Drupal\profile\ProfileStorageInterface $profile_storage */
      $profile_storage = \Drupal::entityTypeManager()->getStorage('profile');
      $profile = $profile_storage->loadByUser($account, 'profile');

      if (!$profile instanceof ProfileAffiliationInterface) {
        // "This author has no profile" has to be invalidated once they get one.
        $bubbleable_metadata->addCacheTags(['profile_list']);
      }
      else {
        /** @var \Drupal\social_profile\Service\AffiliationDisplay $affiliation_display */
        $affiliation_display = \Drupal::service(AffiliationDisplay::class);

        foreach ($variants as $original => $variant) {
          if ($variant === 'raw') {
            $label = $affiliation_display->getPlainLabel($profile, $bubbleable_metadata);
            if ($label !== '') {
              $replacements[$original] = ' @' . $label;
            }
            continue;
          }

          // renderInIsolation() below does not bubble, and the deny path
          // returns no render array at all, so the metadata is collected
          // through the argument.
          $build = $affiliation_display->buildLabel($profile, $bubbleable_metadata);
          if ($build === []) {
            continue;
          }
          // The separator lives here rather than in the label so that the views
          // field showing the same label in a column does not inherit it. No
          // wrapping element: basic_html only allows a class attribute on <a>,
          // and the activity text is downgraded to basic_html when it is
          // stored.
          $build['#prefix'] = ' @';
          $replacements[$original] = \Drupal::service('renderer')->renderInIsolation($build);
        }
      }
    }
  }

  return $replacements;
}
