<?php

namespace Drupal\social_mentions;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\mentions\Entity\MentionsType;

/**
 * Provides helper methods for Social mentions.
 */
class SocialMentionsHelper implements SocialMentionsHelperInterface {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SocialMentionsHelper constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getMentionsPrefixSuffix(): array {
    // Initialize variables.
    $prefix = '[~';
    $suffix = ']';

    $config = $this->configFactory->get('mentions.settings');

    if ($config->get('suggestions_format') === 'username') {
      if ($user_mention = MentionsType::load('UserMention')) {
        $user_mention = $user_mention->getInputSettings();
        $prefix = $user_mention['prefix'];
        $suffix = $user_mention['suffix'];
      }
    }
    else {
      if ($profile_mention = MentionsType::load('ProfileMention')) {
        $profile_mention = $profile_mention->getInputSettings();
        $prefix = $profile_mention['prefix'];
        $suffix = $profile_mention['suffix'];
      }
    }

    return [
      'prefix' => $prefix,
      'suffix' => $suffix,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getMentionsPattern(): string {
    // Retrieve mentions prefix and suffix array.
    $prefix_suffix = $this->getMentionsPrefixSuffix();

    return '/(?:' . preg_quote($prefix_suffix['prefix']) . ')([ a-z0-9@+_.\'-]+)' . preg_quote($prefix_suffix['suffix']) . '/';
  }

  /**
   * {@inheritdoc}
   */
  public function getMentions(string $text): array {
    // Get mentions pattern.
    $pattern = $this->getMentionsPattern();

    preg_match_all($pattern, $text, $matches);

    return $matches[1];
  }

}
