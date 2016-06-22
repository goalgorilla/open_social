<?php

/**
 * @file
 * Contains \Drupal\token\Plugin\Derivative\DevelLocalTask.
 */

namespace Drupal\token\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DevelLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->hasLinkTemplate('token-devel')) {
        $this->derivatives["$entity_type_id.token_devel_tab"] = [
          'route_name' => "entity.$entity_type_id.token_devel",
          'weight' => 110,
          'title' => $this->t('Tokens'),
          'parent_id' => "devel.entities:$entity_type_id.devel_tab",
        ];
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }
}
