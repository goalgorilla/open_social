<?php

namespace Drupal\facets\Plugin\facets\widget;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\facets\FacetInterface;
use Drupal\facets\Result\ResultInterface;
use Drupal\facets\Widget\WidgetInterface;

/**
 * The links widget.
 *
 * @FacetsWidget(
 *   id = "links",
 *   label = @Translation("List of links"),
 *   description = @Translation("A simple widget that shows a list of links"),
 * )
 */
class LinksWidget implements WidgetInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function build(FacetInterface $facet) {
    /** @var \Drupal\facets\Result\Result[] $results */
    $results = $facet->getResults();
    $items = [];

    $configuration = $facet->getWidgetConfigs();
    $show_numbers = empty($configuration['show_numbers']) ? FALSE : (bool) $configuration['show_numbers'];

    foreach ($results as $result) {
      // Get the link.
      $text = $result->getDisplayValue();
      if ($show_numbers) {
        $text .= ' (' . $result->getCount() . ')';
      }
      if ($result->isActive()) {
        $text = '(-) ' . $text;
      }

      if (is_null($result->getUrl())) {
        $items[] = $text;
      }
      else {
        $items[] = $this->buildListItems($result, $show_numbers);
      }
    }

    $build = [
      '#theme' => 'item_list',
      '#items' => $items,
      '#cache' => [
        'contexts' => [
          'url.path',
          'url.query_args',
        ],
      ],
    ];
    return $build;
  }

  /**
   * Builds a renderable array of result items.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   A result item.
   * @param bool $show_numbers
   *   A boolean that's true when the numbers should be shown.
   *
   * @return array|Link|string
   *   A renderable array of the result or a link when the result has no
   *   children.
   */
  protected function buildListItems(ResultInterface $result, $show_numbers) {
    if ($children = $result->getChildren()) {
      $link = $this->prepareLink($result, $show_numbers);

      $children_markup = [];
      foreach ($children as $child) {
        $children_markup[] = $this->buildChildren($child, $show_numbers);
      }

      if ($link instanceof Link) {
        $items = $link->toRenderable();
        $items['#wrapper_attibutes'] = ['class' => ['expanded']];
        $items['children'] = [$children_markup];
      }
      else {
        $items = [
          '#markup' => $link,
          '#wrapper_attributes' => [
            'class' => ['expanded'],
          ],
          'children' => [$children_markup],
        ];
      }
    }
    else {
      $items = $this->prepareLink($result, $show_numbers);
    }

    return $items;
  }

  /**
   * Returns the text or link for an item.
   *
   * @param \Drupal\facets\Result\ResultInterface $result
   *   A result item.
   * @param bool $show_numbers
   *   A boolean that's true when the numbers should be shown.
   *
   * @return Link|string
   *   The item, can be a link or just the text.
   */
  protected function prepareLink(ResultInterface $result, $show_numbers) {
    $text = $result->getDisplayValue();

    if ($show_numbers && $result->getCount()) {
      $text .= ' (' . $result->getCount() . ')';
    }
    if ($result->isActive()) {
      $text = '(-) ' . $text;
    }

    if (is_null($result->getUrl())) {
      $link = $text;
    }
    else {
      $link = new Link($text, $result->getUrl());
    }

    return $link;
  }

  /**
   * Builds a renderable array of a result.
   *
   * @param \Drupal\facets\Result\ResultInterface $child
   *   A result item.
   * @param bool $show_numbers
   *   A boolean that's true when the numbers should be shown.
   *
   * @return array|Link|string
   *   A renderable array of the result.
   */
  protected function buildChildren(ResultInterface $child, $show_numbers) {
    $text = $child->getDisplayValue();
    if ($show_numbers && $child->getCount()) {
      $text .= ' (' . $child->getCount() . ')';
    }
    if ($child->isActive()) {
      $text = '(-) ' . $text;
    }

    if (!is_null($child->getUrl())) {
      $link = new Link($text, $child->getUrl());
      $link = $link->toRenderable();
      $link['#wrapper_attributes'] = ['class' => ['leaf']];
    }
    else {
      $link = [
        '#markup' => $text,
        '#wrapper_attributes' => [
          'class' => ['leaf'],
        ],
      ];
    }

    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state, $config) {

    $form['show_numbers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the amount of results'),
    ];

    if (!is_null($config)) {
      $widget_configs = $config->get('widget_configs');
      if (isset($widget_configs['show_numbers'])) {
        $form['show_numbers']['#default_value'] = $widget_configs['show_numbers'];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryType($query_types) {
    return $query_types['string'];
  }

}
