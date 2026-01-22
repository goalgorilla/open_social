<?php

declare(strict_types=1);

namespace Drupal\social_profile_organization_tag\Hooks;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;
use Drupal\hux\Attribute\Alter;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_profile_organization_tag\Service\SocialProfileOrganizationTag;
use Drupal\user\UserInterface;
use Drupal\Component\Render\FormattableMarkup;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Loader\Configurator\Traits\AutowireTrait;

/**
 * Hook implementations for the "social_profile_organization_tag" module.
 */
class SocialProfileOrganizationTagHooks {

  use AutowireTrait;

  /**
   * SocialProfileOrganizationTagHooks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\social_profile_organization_tag\Service\SocialProfileOrganizationTag $socialProfileOrganizationTag
   *   The social profile organization tag service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(
    #[Autowire(service: 'entity_type.manager')]
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: SocialProfileOrganizationTag::class)]
    protected SocialProfileOrganizationTag $socialProfileOrganizationTag,
    #[Autowire(service: 'renderer')]
    protected RendererInterface $renderer,
  ) {}

  /**
   * Replace user name in mentions.
   *
   * This method modifies token replacements by appending the organization
   * associated with a user profile to specified profile name tokens.
   *
   * @param array $replacements
   *   An associative array of token replacements, where tokens are the keys
   *   and their corresponding replacement values are the values.
   * @param array $context
   *   A context array.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata that may be affected by the token processing.
   *
   * @see hook_tokens_alter()
   */
  #[Alter('tokens')]
  public function mentionsTokenAlter(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata): void {
    if (
      $context['type'] !== 'social_mentions' ||
      !array_key_exists('user_name', $context['tokens']) ||
      count($replacements) <= 0 ||
      !isset($context['data']['profile'])
    ) {
      return;
    }

    $profile = $context['data']['profile'];
    $display_name = $this->entityTypeManager->getViewBuilder('profile')->view($profile, 'name_raw');
    $replacements['[social_mentions:user_name]'] = $this->renderer->renderInIsolation($display_name);
  }

  /**
   * Append the organization suffix to profile names tokens.
   *
   * This method modifies token replacements by appending the organization
   * associated with a user profile to specified profile name tokens.
   *
   * @param array $replacements
   *   An associative array of token replacements, where tokens are the keys
   *   and their corresponding replacement values are the values.
   * @param array $context
   *   A context array.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata that may be affected by the token processing.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @see hook_tokens_alter()
   */
  #[Alter('tokens')]
  public function tagSuffixInProfileName(array &$replacements, array $context, BubbleableMetadata $bubbleable_metadata): void {
    if (!isset($replacements['[message:author:display-name]']) && !isset($replacements['[message:revision_author:display-name]'])) {
      return;
    }

    // These tokens are used for profile name display in message tokens.
    $tokens = [
      '[message:author:display-name]',
      '[message:revision_author:display-name]',
    ];

    $account = &$context['data']['user'];
    if (!$account instanceof UserInterface) {
      return;
    }

    $storage = $this->entityTypeManager->getStorage('profile');
    $user_profile = $storage->loadByUser($account, 'profile');
    if (!$user_profile instanceof ProfileInterface) {
      return;
    }

    $organization = $this->socialProfileOrganizationTag->getTagText($user_profile, FALSE);
    if (!$organization) {
      return;
    }

    foreach ($tokens as $name) {
      if (!isset($replacements[$name])) {
        continue;
      }

      $replacements[$name] = new FormattableMarkup('@displayName@organization', [
        '@displayName' => $replacements[$name],
        '@organization' => $organization,
      ]);
    }
  }

}
