<?php

namespace Drupal\social_course\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteObjectInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Template\Attribute;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;
use Drupal\social_course\CourseWrapperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'CourseSectionNavigationBlock' block.
 *
 * @Block(
 *   id = "course_section_navigation",
 *   admin_label = @Translation("Course section navigation block"),
 *   context_definitions = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class CourseSectionNavigationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The course wrapper.
   *
   * @var \Drupal\social_course\CourseWrapperInterface
   */
  protected $courseWrapper;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The title resolver.
   *
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Creates a CourseSectionNavigationBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\social_course\CourseWrapperInterface $course_wrapper
   *   The course wrapper.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityRepositoryInterface $entity_repository,
    CourseWrapperInterface $course_wrapper,
    AccountProxyInterface $current_user,
    RequestStack $request_stack,
    TitleResolverInterface $title_resolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityRepository = $entity_repository;
    $this->courseWrapper = $course_wrapper;
    $this->currentUser = $current_user;
    $this->request = $request_stack->getCurrentRequest();
    $this->titleResolver = $title_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.repository'),
      $container->get('social_course.course_wrapper'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('title_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $node = $this->getContextValue('node');

    if ($node instanceof NodeInterface && $node->id()) {
      $translation = $this->entityRepository->getTranslationFromContext($node);

      if (!empty($translation)) {
        $node->setTitle($translation->getTitle());
      }

      $parent_section = $this->courseWrapper->setCourseFromMaterial($node)
        ->getSectionFromMaterial($node);

      $items = [];

      foreach ($this->courseWrapper->getSections() as $section) {
        $item = [
          'attributes' => new Attribute(),
          'label' => $section->label(),
        ];

        $access = $this->courseWrapper->sectionAccess($section, $this->currentUser, 'view');

        if ($access->isAllowed()) {
          $item['label'] = $section->toLink()->toRenderable();
          // Mark the current section link as active.
          if ($section->id() === $parent_section->id()) {
            $item['attributes']->addClass('active');
          }
        }
        else {
          $item['label'] = $section->label();
          $item['attributes']->addClass('not-allowed');
        }

        $items[] = $item;
      }

      return [
        '#theme' => 'course_section_navigation',
        '#items' => $items,
      ];
    }

    $route = $this->request->attributes
      ->get(RouteObjectInterface::ROUTE_OBJECT);

    if ($route) {
      $title = $this->titleResolver->getTitle($this->request, $route);
    }
    else {
      $title = '';
    }

    return [
      '#type' => 'page_title',
      '#title' => $title,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $tags = parent::getCacheTags();
    $node = $this->getContextValue('node');

    if ($node instanceof NodeInterface && $node->id()) {
      $this->courseWrapper->setCourseFromMaterial($node);
      $tags = Cache::mergeTags($tags, $this->courseWrapper->getCourse()->getCacheTags());
      $tags = Cache::mergeTags($tags, $this->courseWrapper->getSectionFromMaterial($node)->getCacheTags());
    }

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    $node = $this->getContextValue('node');

    if ($node instanceof NodeInterface && $node->id()) {
      $this->courseWrapper->setCourseFromMaterial($node);

      if ($this->courseWrapper->getSections()) {
        $group = $this->courseWrapper->getCourse();

        return AccessResult::allowedIf($group instanceof GroupInterface);
      }
    }

    return AccessResult::forbidden();
  }

}
