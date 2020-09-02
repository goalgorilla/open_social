<?php

namespace Drupal\social_activity;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\ViewExecutableFactory;

/**
 * Class SocialActivityLazyBuilder.
 *
 * @package Drupal\social_activity
 */
class SocialActivityLazyBuilder {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The views executable factory.
   *
   * @var \Drupal\views\ViewExecutableFactory
   */
  protected $viewExecutable;

  /**
   * SocialActivityLazyBuilder constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\views\ViewExecutableFactory $view_executable
   *   The views executable factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ViewExecutableFactory $view_executable) {
    $this->entityTypeManager = $entity_type_manager;
    $this->viewExecutable = $view_executable;
  }

  /**
   * Returns views render for lazy builder.
   *
   * @param string $view_id
   *   The views ID.
   * @param string $display_id
   *   The views display ID.
   * @param string $node_type
   *   Node bundle.
   * @param int $item_per_page
   *   Items to display.
   *
   * @return array|null
   *   Render array.
   */
  public function viewsLazyBuild($view_id, $display_id, $node_type, $item_per_page) {
    // Get view.
    $view_entity = $this->entityTypeManager->getStorage('view')->load($view_id);
    $view = $this->viewExecutable->get($view_entity);
    $view->setDisplay($display_id);
    $view->setItemsPerPage($item_per_page);
    $view->preExecute();
    $view->execute($display_id);

    // Change entity display and add attachments if views block in dashboard.
    if ($view->id() == "activity_stream" && $node_type === 'dashboard') {
      $view->rowPlugin->options['view_mode'] = 'featured';
      $view->element['#attached']['library'][] = 'social_featured_content/paragraph.featured';
    }

    // Get views content.
    $content = $view->render($display_id);
    return $content;
  }

}
