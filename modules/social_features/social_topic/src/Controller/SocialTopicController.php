<?php

namespace Drupal\social_topic\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Social Topic routes.
 */
class SocialTopicController extends ControllerBase {

  /**
   * The request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * SocialTopicController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   */
  public function __construct(RequestStack $requestStack, EntityTypeManagerInterface $entityTypeManager, ModuleHandlerInterface $moduleHandler) {
    $this->requestStack = $requestStack;
    $this->entityTypeManager = $entityTypeManager;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('entity_type.manager'),
      $container->get('module_handler')
    );
  }

  /**
   * The _title_callback for the latest topics view.
   *
   * @return string
   *   The page title.
   */
  public function latestTopicsPageTitle() {
    $title = $this->t('All topics');

    // TODO This might change depending on the view exposed filter settings.
    $topic_type_id = $this->requestStack->getCurrentRequest()->get('field_topic_type_target_id');
    $term = NULL;
    if ($topic_type_id !== NULL) {
      // Topic type can be "All" will crash overview on /newest-topics.
      if (is_numeric($topic_type_id)) {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($topic_type_id);

        if ($term->access('view') && $term->getVocabularyId() === 'topic_types') {
          $term_title = $term->getName();
          $title = $this->t('Topics of type @type', ['@type' => $term_title]);
        }
      }
    }
    // Call hook_topic_type_title_alter().
    $this->moduleHandler->alter('topic_type_title', $title, $term);

    return $title;
  }

}
