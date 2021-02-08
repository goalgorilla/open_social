<?php

namespace Drupal\mentions\Plugin\Mentions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Utility\Token;
use Drupal\mentions\MentionsPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Entity.
 *
 * @Mention(
 *  id = "entity",
 *  name = @Translation("Entity")
 * )
 */
class Entity implements MentionsPluginInterface {
  private $tokenService;
  private $entityTypeManager;

  /**
   * Entity constructor.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(Token $token, EntityTypeManagerInterface $entity_type_manager) {
    $this->tokenService = $token;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $token = $container->get('token');
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $token,
      $entity_type_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function outputCallback($mention, $settings) {
    $entity = $this->entityTypeManager->getStorage($mention['target']['entity_type'])
      ->load($mention['target']['entity_id']);
    $output = [];
    $output['value'] = $this->tokenService->replace($settings['value'], [$mention['target']['entity_type'] => $entity]);
    if ($settings['renderlink']) {
      $output['link'] = $this->tokenService->replace($settings['rendertextbox'], [$mention['target']['entity_type'] => $entity]);
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function targetCallback($value, $settings) {
    $entity_type = $settings['entity_type'];
    $input_value = $settings['value'];
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();
    $result = $query->condition($input_value, $value)->execute();

    if (!empty($result)) {
      $result = reset($result);
      $target = [];
      $target['entity_type'] = $entity_type;
      $target['entity_id'] = $result;

      return $target;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function mentionPresaveCallback(EntityInterface $entity) {

  }

  /**
   * {@inheritdoc}
   */
  public function patternCallback($settings, $regex) {

  }

  /**
   * {@inheritdoc}
   */
  public function settingsCallback(FormInterface $form, FormStateInterface $form_state, $type) {

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSubmitCallback(FormInterface $form, FormStateInterface $form_state, $type) {

  }

}
