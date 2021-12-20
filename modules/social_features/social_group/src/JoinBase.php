<?php

namespace Drupal\social_group;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base join implementation.
 *
 * @ingroup social_group_api
 */
abstract class JoinBase extends PluginBase implements JoinPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    TranslationInterface $translation
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->setStringTranslation($translation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation'),
    );
  }

}
