<?php

namespace Drupal\social_core\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that renders the current entity in a given view mode.
 *
 * @Block(
 *   id = "entity_view_block",
 *   admin_label = @Translation("Entity view block"),
 *   context_definitions = {
 *     "entity" = @ContextDefinition("entity"),
 *   }
 * )
 */
class EntityViewBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  public $entityTypeManager;

  /**
   * {@inheritdoc}
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
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['view_mode'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('View mode'),
      '#default_value' => $this->configuration['view_mode'] ?? NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) : void {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->getContext("entity")->getContextValue();
    assert($entity instanceof ContentEntityInterface, "The entity block context should be required and should only be used with content entities.");
    assert(is_string($this->configuration['view_mode']) && $this->configuration['view_mode'] !== "", "The view_mode is a required configuration for the entity_view_block instance.");

    // If the view display is not configured for this entity then there's
    // nothing for us to render. We don't want to fall back to the default view
    // mode because that's what we may be attached to.
    $view_display = $this->entityTypeManager
      ->getStorage("entity_view_display")
      ->load($entity->getEntityTypeId() . "." . $entity->bundle() . "." . $this->configuration['view_mode']);
    if ($view_display === NULL) {
      return [];
    }

    $render_controller = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
    assert($render_controller !== NULL);

    return $render_controller->view($entity, $this->configuration['view_mode']);
  }

}
