<?php

namespace Drupal\social_profile\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'ProfileHeroBlock' block.
 *
 * @Block(
 *   id = "profile_hero_block",
 *   admin_label = @Translation("Profile hero block"),
 *   context = {
 *     "user" = @ContextDefinition("entity:user")
 *   }
 * )
 */
class ProfileHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * ProfileHeroBlock constructor.
   *
   * @param array $configuration
   *   The given configuration.
   * @param string $plugin_id
   *   The given plugin id.
   * @param mixed $plugin_definition
   *   The given plugin definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $account = $this->getContextValue('user');

    $storage = $this->entityTypeManager->getStorage('profile');
    $profile = $storage->loadByUser($account, 'profile');

    if ($profile) {
      $build['content'] = $this->entityTypeManager
        ->getViewBuilder('profile')
        ->view($profile, 'hero');
      $build['content']['#cache']['tags'] = $this->getCacheTags();
      $build['content']['#cache']['contexts'] = $this->getCacheContexts();
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $account = $this->getContextValue('user');
    $storage = $this->entityTypeManager->getStorage('profile');
    $profile = $storage->loadByUser($account, 'profile');
    $tags = [
      'user:' . $account->id(),
    ];

    if ($profile) {
      $tags[] = 'profile:' . $profile->id();
    }

    return Cache::mergeTags(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user.permissions']);
  }

}
