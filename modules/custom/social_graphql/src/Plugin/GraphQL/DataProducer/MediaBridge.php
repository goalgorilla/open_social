<?php

namespace Drupal\social_graphql\Plugin\GraphQL\DataProducer;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;
use Drupal\graphql\GraphQL\Buffers\EntityBuffer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\media\MediaInterface;
use GraphQL\Deferred;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The media bridge provides a way to get data from File fields and Media.
 *
 * It acts similar to the property_path data producers by picking specific data
 * off an entity. However, it accepts both a
 * \Drupal\media\Plugin\media\Source\File implementation as well as a
 * \Drupal\file\Plugin\Field\FieldType\FileItem instance.
 *
 * This allows the same resolver to be used regardless of implementation which
 * should make it easier for Open Social to switch out to a Media based
 * implementation behind the scenes.
 *
 * @todo This solution is implemented to allow relatively transparent resolving
 *   of Media related typs. It serves as a way to allow us to move towards the
 *   Media library while being stuck with traditional File fields. This is not
 *   necessarily an elegant solution. It should not be used as examples for
 *   other data producers (it tries to do do too much). We should strive to
 *   remove it as soon as we have transitioned fully to the Media module.
 *
 * @DataProducer(
 *   id = "media_bridge",
 *   name = @Translation("Media bridge"),
 *   description = @Translation("Provides data about image fields and media sources."),
 *   produces = @ContextDefinition("mixed",
 *     label = @Translation("Value")
 *   ),
 *   consumes = {
 *     "value" = @ContextDefinition("mixed",
 *       label = @Translation("Value (Media source or FileItem)")
 *     ),
 *     "field" = @ContextDefinition("string",
 *       label = @Translation("Field name")
 *     )
 *   }
 * )
 */
class MediaBridge extends DataProducerPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity buffer service.
   *
   * @var \Drupal\graphql\GraphQL\Buffers\EntityBuffer
   */
  protected $entityBuffer;

  /**
   * {@inheritdoc}
   *
   * @codeCoverageIgnore
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('graphql.buffer.entity')
    );
  }

  /**
   * MediaBridge constructor.
   *
   * @param array $configuration
   *   The plugin configuration array.
   * @param string $pluginId
   *   The plugin id.
   * @param array $pluginDefinition
   *   The plugin definition array.
   * @param \Drupal\graphql\GraphQL\Buffers\EntityBuffer $entityBuffer
   *   The entity buffer service.
   *
   * @codeCoverageIgnore
   */
  public function __construct(
    array $configuration,
    $pluginId,
    array $pluginDefinition,
    EntityBuffer $entityBuffer
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->entityBuffer = $entityBuffer;
  }

  /**
   * Resolve the value for this data producer.
   *
   * @param mixed $value
   *   The media source or file item.
   * @param string $field
   *   The name of the data to return.
   *
   * @return \GraphQL\Deferred|null
   *   The resolved value for a File Item.
   */
  public function resolve($value, $field) {
    // To make consuming fields easier we convert field item lists to field
    // items.
    if ($value instanceof FieldItemListInterface) {
      $value = $value->first();
    }

    if ($value instanceof FileItem) {
      return $this->resolveFileItem($value, $field);
    }

    if ($value instanceof MediaInterface) {
      return $this->resolveMediaEntity($value, $field);
    }

    throw new \RuntimeException("MediaBridge data producer called with unsupported input type. Must be FileItem field instance or Media entity.");
  }

  /**
   * Resolve field for FileItem.
   *
   * @param \Drupal\file\Plugin\Field\FieldType\FileItem $file
   *   The media source or file item.
   * @param string $field
   *   The name of the data to return.
   *
   * @return mixed
   *   The resolved value for a File Item.
   */
  protected function resolveFileItem(FileItem $file, $field) {
    switch ($field) {
      case 'id':
        if ($file->isEmpty()) {
          return NULL;
        }
        $entity_id = $file->get('target_id')->getValue();
        $target_type = $file->getFieldDefinition()->getSetting('target_type');
        $resolver = $this->entityBuffer->add($target_type, $entity_id);
        return new Deferred(function () use ($resolver) {
          /** @var \Drupal\file\Entity\File $file_entity */
          $file_entity = $resolver();
          return $file_entity->uuid();
        });

      case 'url':
        if ($file->isEmpty()) {
          return NULL;
        }
        $entity_id = $file->get('target_id')->getValue();
        $target_type = $file->getFieldDefinition()->getSetting('target_type');
        $resolver = $this->entityBuffer->add($target_type, $entity_id);
        return new Deferred(function () use ($resolver) {
          /** @var \Drupal\file\Entity\File $file_entity */
          $file_entity = $resolver();
          return $file_entity->createFileUrl(FALSE);
        });

      case 'title':
        return $file->get('title')->getString();

      case 'alt':
        return $file->get('alt')->getString();

      default:
        throw new \RuntimeException("Unsupported field for FileItem: '${field}'.");
    }
  }

  /**
   * Resolve field for media entity.
   *
   * @param \Drupal\media\MediaInterface $value
   *   The media source or file item.
   * @param string $field
   *   The name of the data to return.
   *
   * @return mixed
   *   The resolved value for a Media entity.
   *
   * @todo https://www.drupal.org/project/social/issues/3191642
   */
  protected function resolveMediaEntity(MediaInterface $value, $field) {
    switch ($field) {
      case 'id':
        return $value->uuid();

      case 'url':
      case 'title':
      case 'alt':
        // Fetch the source field from the media entity which allows us to treat
        // it like a regular file item.
        $source_field_name = $value->getSource()->getConfiguration()['source_field'];
        $source_field = $value->{$source_field_name}->first();
        return $this->resolveFileItem($source_field, $field);

      default:
        throw new \RuntimeException("Unsupported field for Media entity: '${field}'.");
    }
  }

}
