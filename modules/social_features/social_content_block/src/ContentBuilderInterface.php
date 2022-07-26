<?php

namespace Drupal\social_content_block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides an interface for the content builder service.
 *
 * @package Drupal\social_content_block
 */
interface ContentBuilderInterface extends TrustedCallbackInterface {

  /**
   * ContentBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current active database's master connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   * @param \Drupal\social_content_block\ContentBlockManagerInterface $content_block_manager
   *   The content block manager.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Connection $connection,
    TranslationInterface $string_translation,
    ContentBlockManagerInterface $content_block_manager,
    EntityRepositoryInterface $entity_repository,
    TimeInterface $time
  );

  /**
   * Function to get all the entities based on the filters.
   *
   * @param string|int $block_id
   *   The block id where we get the settings from.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getEntities($block_id): array;

  /**
   * Lazy builder callback for displaying a content blocks.
   *
   * @param string|int|null $entity_id
   *   The entity ID.
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $entity_bundle
   *   The bundle of the entity.
   *
   * @return array
   *   A render array for the action link, empty if the user does not have
   *   access.
   */
  public function build($entity_id, string $entity_type_id, string $entity_bundle): array;

  /**
   * Update the sorting field after a plugin choice change.
   *
   * @param array $form
   *   The form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function updateFormSortingOptions(array $form, FormStateInterface $form_state): array;

}
