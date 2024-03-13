<?php

namespace Drupal\social_content_block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Provides an interface for the content builder service.
 *
 * @package Drupal\social_content_block
 */
interface ContentBuilderInterface extends TrustedCallbackInterface {

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
