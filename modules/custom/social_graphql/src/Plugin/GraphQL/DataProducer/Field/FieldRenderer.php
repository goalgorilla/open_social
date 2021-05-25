<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer\Field;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders a field.
 *
 * @DataProducer(
 *   id = "field_renderer",
 *   name = @Translation("Render Field"),
 *   description = @Translation("Renders a field."),
 *   produces = @ContextDefinition("string",
 *     label = @Translation("HTML")
 *   ),
 *   consumes = {
 *     "field" = @ContextDefinition("mixed",
 *       label = @Translation("Field")
 *     )
 *   }
 * )
 */
class FieldRenderer extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Drupal renderer service.
   */
  protected RendererInterface $renderer;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * EntityLoad constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The Drupal renderer service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->renderer = $renderer;
  }

  /**
   * Renders the formatted text field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   The formatted text field to render.
   * @param \Drupal\Core\Cache\RefinableCacheableDependencyInterface $metadata
   *   Cacheability metadata for this request.
   *
   * @return string|null
   *   The rendered text or null if there was an error.
   */
  public function resolve(FieldItemListInterface $field, RefinableCacheableDependencyInterface $metadata): ?string {
    $render_array = $field->view([
      'label' => 'hidden',
    ]);
    if (empty($render_array)) {
      return NULL;
    }

    $render_context = new RenderContext();
    /** @var \Drupal\Component\Render\MarkupInterface $markup */
    $markup = $this->renderer->executeInRenderContext($render_context, function () use (&$render_array) {
      return $this->renderer->render($render_array);
    });

    if (!$render_context->isEmpty()) {
      // executeInContext asserts that everything bubbled into a single object.
      /** @var \Drupal\Core\Render\BubbleableMetadata $render_metadata */
      $render_metadata = $render_context->offsetGet(0);
      $metadata->addCacheableDependency($render_metadata);
    }

    return trim($markup->__toString());
  }

}
