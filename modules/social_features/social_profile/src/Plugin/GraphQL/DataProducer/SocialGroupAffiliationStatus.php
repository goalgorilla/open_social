<?php

declare(strict_types=1);

namespace Drupal\social_profile\Plugin\GraphQL\DataProducer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\social_profile\GroupAffiliation;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns whether the group affiliation feature is enabled.
 */
#[DataProducer(
  id: "social_group_affiliation_status",
  name: new TranslatableMarkup("Group affiliation feature status"),
  description: new TranslatableMarkup("Returns TRUE when group affiliation is enabled."),
  produces: new ContextDefinition(
    data_type: "boolean",
    label: new TranslatableMarkup("Feature enabled"),
  ),
)]
final class SocialGroupAffiliationStatus extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('social_profile.group_affiliation'),
      $container->get('config.factory'),
    );
  }

  /**
   * SocialGroupAffiliationStatus constructor.
   */
  public function __construct(
    array $configuration,
    string $pluginId,
    array $pluginDefinition,
    protected GroupAffiliation $groupAffiliation,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * Resolver.
   */
  public function resolve(FieldContext $context): bool {
    $context->addCacheTags([GroupAffiliation::GENERAL_CACHE_TAG]);
    $context->addCacheableDependency($this->configFactory->get('social_profile.settings'));

    return $this->groupAffiliation->isAffiliationFeatureEnabled();
  }

}
