<?php

namespace Drupal\social_topic\Plugin\GraphQL\SchemaExtension;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;
use GraphQL\Language\Source;

/**
 * Adds topic data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_topic_schema_extension",
 *   name = "Open Social - Topic Schema Extension",
 *   description = "GraphQL schema extension for Open Social topic data.",
 *   schema = "open_social"
 * )
 */
class TopicSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $this->addQueryFields($registry, $builder);
    $this->addTopicFields($registry, $builder);

    // Register createTopic mutation using the trait method.
    $this->registerMutationResolver($registry, $builder, 'createTopic');
    $this->registerMutationResolver($registry, $builder, 'updateTopic');
    $this->registerMutationResolver($registry, $builder, 'deleteTopic');

    // Register CreateTopicPayload resolvers.
    $registry->addFieldResolver('CreateTopicPayload', 'clientMutationId',
      $builder->produce('payload_client_mutation_id')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('CreateTopicPayload', 'errors',
      $builder->produce('payload_violations')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('CreateTopicPayload', 'topic',
      $builder->produce('payload_topic')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('UpdateTopicPayload', 'clientMutationId',
      $builder->produce('payload_client_mutation_id')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('UpdateTopicPayload', 'errors',
      $builder->produce('payload_violations')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('UpdateTopicPayload', 'topic',
      $builder->produce('payload_topic')
        ->map('payload', $builder->fromParent())
    );

    // Register DeleteTopicPayload resolvers.
    $registry->addFieldResolver('DeleteTopicPayload', 'clientMutationId',
      $builder->produce('payload_client_mutation_id')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('DeleteTopicPayload', 'errors',
      $builder->produce('payload_violations')
        ->map('payload', $builder->fromParent())
    );
  }

  /**
   * Registers type and field resolvers in the shared registry.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addTopicFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Topic', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Topic', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Topic', 'author',
      $builder->produce('entity_owner')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Topic', 'bodyHtml',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('body')),
        $builder->produce('field_renderer')
          ->map('field', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Topic', 'heroImage',
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_topic_image'))
    );

    $registry->addFieldResolver('Topic', 'visibility',
      $builder->compose(
        $builder->fromPath('entity:node', 'field_content_visibility.0.value'),
        $builder->produce('content_visibility_mapper')
          ->map('value', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Topic', 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent())
        ->map('format', $builder->fromValue('U'))
    );

    $registry->addFieldResolver('Topic', 'comments',
      $builder->produce('query_comments')
        ->map('parent', $builder->fromParent())
        ->map('bundle', $builder->fromValue('comment'))
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Topic', 'url',
      $builder->compose(
        $builder->produce('social_entity_url')
          ->map('entity', $builder->fromParent())
          ->map('options', $builder->fromValue(['absolute' => TRUE])),
        $builder->produce('url_path')
          ->map('url', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Topic', 'type',
      $builder->compose(
        $builder->produce('entity_reference')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_topic_type')),
        $builder->produce('seek')
          ->map('input', $builder->fromParent())
          ->map('position', $builder->fromValue(0))
      )
    );

    $registry->addFieldResolver('TopicType', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('TopicType', 'label',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );
  }

  /**
   * Registers type and field resolvers in the query type.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The resolver registry.
   * @param \Drupal\graphql\GraphQL\ResolverBuilder $builder
   *   The resolver builder.
   */
  protected function addQueryFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Query', 'topics',
      $builder->produce('query_topic')
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Query', 'topicsByType',
      $builder->produce('query_topic_by_type')
        ->map('type', $builder->fromArgument('type'))
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Query', 'topic',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['topic']))
        ->map('uuid', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'topicTypes',
      $builder->produce('taxonomy_load_tree')
        ->map('vid', $builder->fromValue('topic_types'))
        ->map('parent', $builder->fromValue(0))
    );

    $registry->addFieldResolver('Query', 'topicTagCategories',
      $builder->produce('tag_categories_by_content_type')
        ->map('placement', $builder->fromValue('TOPIC'))
    );

    // Add topicsCreated field to the user object.
    $registry->addFieldResolver('User', 'topicsCreated',
      $builder->produce('social_topics_created')
        ->map('entity', $builder->fromParent())
    );
  }

  /**
   * {@inheritdoc}
   *
   * Loads the base extension schema, then appends additional schemas from
   * enabled modules (e.g. social_group_flexible_group, social_organization)
   * that extend Topic and CreateTopicInput.
   */
  public function getExtensionDefinition(): ?Source {
    $definition = parent::getExtensionDefinition();
    assert($definition !== NULL, "SdlSchemaExtensionPluginBase never returns null.");

    $source = $definition->body;
    $name = $definition->name;

    // Then, load additional extension schemas. These files extend the base
    // Topic type with groups field (flexible_group) and organizations field
    // (organization). Only load extensions if their corresponding modules are
    // enabled.
    $extension_modules = [
      'social_group_flexible_group' => 'social_topic_flexible_group_schema_extension',
      'social_organization' => 'social_topic_organization_schema_extension',
    ];

    foreach ($extension_modules as $module_name => $basename) {
      // Check if the module is enabled before loading its extension.
      if (!$this->moduleHandler->moduleExists($module_name)) {
        continue;
      }

      $otherSource = $this->loadOtherDefinitionFile($basename, 'extension');
      $source .= "\n$otherSource->body";
      $name .= " concatenated with $otherSource->name";
    }

    return new Source(
      $source,
      $name
    );
  }

  /**
   * Loads a separate definition file.
   *
   * Temporary workaround until we properly use more schema extensions.
   * Do not replicate this and do not try to abstract this into shared code,
   * this function should not exist.
   *
   * @param string $baseName
   *   The base name of the file to load.
   * @param string $type
   *   The type of the definition file to load.
   *
   * @return \GraphQL\Language\Source
   *   The loaded definition file content.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   In case the file does not exist.
   */
  private function loadOtherDefinitionFile(string $baseName, string $type): Source {
    $id = $this->getPluginId();
    $definition = $this->getPluginDefinition();
    assert(is_array($definition));
    $module = $this->moduleHandler->getModule($definition['provider']);
    $path = 'graphql/' . $baseName . '.' . $type . '.graphqls';
    $file = $module->getPath() . '/' . $path;

    if (!file_exists($file)) {
      throw new InvalidPluginDefinitionException(
        $id,
        sprintf('The module "%s" needs to have a schema definition "%s" in its folder for "%s" to be valid.',
          $module->getName(), $path, $definition['class']));
    }

    $contents = file_get_contents($file);
    if (!$contents) {
      throw new InvalidPluginDefinitionException(
        $id,
        sprintf(
          'Failed to read schema file "%s".',
          $file
        )
      );
    }

    return new Source($contents, $file);
  }

}
