<?php

declare(strict_types=1);

namespace Drupal\social_core\Plugin\Block;

use Drupal\Core\Block\Plugin\Block\PageTitleBlock;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with a title and sorts functionality.
 *
 * This block extends the PageTitleBlock and modifies its build method
 * to include additional CSS classes and a library for handling title
 * and sort features. It is primarily used to render a page title with
 * sorting controls included.
 */
#[Block(
  id: "title_with_sorts_block",
  admin_label: new TranslatableMarkup("Title with sorts")
)]
class TitleWithSortsBlock extends PageTitleBlock implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected RouteMatchInterface $routeMatch,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $title = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      'title' => parent::build(),
    ];

    $build = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['title-with-sorts'],
      ],
      '#attached' => [
        'library' => [
          'social_core/title-with-sorts',
          'socialbase/sort-filter',
        ],
      ],
    ];

    // We don't want to show title on "Group Members" page.
    if ($this->routeMatch->getRouteName() === 'view.group_members.page_group_members') {
      $title['#attributes']['class'][] = 'visually-hidden';
    }

    $build[] = $title;
    $build[] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'class' => ['extra-area'],
      ],
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return array_values(Cache::mergeContexts(parent::getCacheContexts(), ['route']));
  }

}
