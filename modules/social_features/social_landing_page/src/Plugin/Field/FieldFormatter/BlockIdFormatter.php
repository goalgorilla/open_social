<?php

namespace Drupal\social_landing_page\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'block_field' formatter.
 *
 * @FieldFormatter(
 *   id = "block_label",
 *   label = @Translation("Block label with id"),
 *   field_types = {
 *     "block_field"
 *   }
 * )
 */
class BlockIdFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Construct a BlockIdFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current logged in user.
   *
   * @internal param $ |Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, RendererInterface $renderer, AccountProxyInterface $currentUser) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->renderer = $renderer;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('renderer'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      /** @var \Drupal\block_field\BlockFieldItemInterface $item */
      $block_instance = $item->getBlock();
      // Make sure the block exists and is accessible.
      if (!$block_instance || !$block_instance->access($this->currentUser)) {
        continue;
      }

      $elements[$delta] = ['#plain_text' => $block_instance->label() . ' (' . $block_instance->getPluginId() . ')'];

      /** @var \Drupal\Core\Render\RendererInterface $renderer */
      $this->renderer->addCacheableDependency($elements[$delta], $block_instance);
    }
    return $elements;
  }

}
