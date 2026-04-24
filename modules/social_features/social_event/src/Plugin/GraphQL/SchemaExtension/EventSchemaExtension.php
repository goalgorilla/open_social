<?php

namespace Drupal\social_event\Plugin\GraphQL\SchemaExtension;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\graphql\GraphQL\ResolverBuilder;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\social_graphql\Plugin\GraphQL\SchemaExtension\SchemaExtensionPluginBase;
use GraphQL\Language\Source;

/**
 * Adds event data to the Open Social GraphQL API.
 *
 * @SchemaExtension(
 *   id = "social_event_schema_extension",
 *   name = "Open Social - Event Schema Extension",
 *   description = "GraphQL schema extension for Open Social event data.",
 *   schema = "open_social"
 * )
 */
class EventSchemaExtension extends SchemaExtensionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function registerResolvers(ResolverRegistryInterface $registry): void {
    $builder = new ResolverBuilder();

    $this->addQueryFields($registry, $builder);
    $this->addEventFields($registry, $builder);

    $this->registerMutationResolver($registry, $builder, 'createEvent');

    $registry->addFieldResolver('CreateEventPayload', 'clientMutationId',
      $builder->produce('payload_client_mutation_id')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('CreateEventPayload', 'errors',
      $builder->produce('payload_violations')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('CreateEventPayload', 'event',
      $builder->produce('payload_event')
        ->map('payload', $builder->fromParent())
    );

    $this->registerMutationResolver($registry, $builder, 'updateEvent');

    $registry->addFieldResolver('UpdateEventPayload', 'clientMutationId',
      $builder->produce('payload_client_mutation_id')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('UpdateEventPayload', 'errors',
      $builder->produce('payload_violations')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('UpdateEventPayload', 'event',
      $builder->produce('payload_event')
        ->map('payload', $builder->fromParent())
    );

    $this->registerMutationResolver($registry, $builder, 'deleteEvent');

    $registry->addFieldResolver('DeleteEventPayload', 'clientMutationId',
      $builder->produce('payload_client_mutation_id')
        ->map('payload', $builder->fromParent())
    );

    $registry->addFieldResolver('DeleteEventPayload', 'errors',
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
  protected function addEventFields(ResolverRegistryInterface $registry, ResolverBuilder $builder) {
    $registry->addFieldResolver('Event', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Event', 'title',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Event', 'author',
      $builder->produce('entity_owner')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('Event', 'bodyHtml',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('body')),
        $builder->produce('field_renderer')
          ->map('field', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Event', 'heroImage',
      $builder->produce('field')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_event_image'))
    );

    $registry->addFieldResolver('Event', 'comments',
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

    $registry->addFieldResolver('Event', 'startDate',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_event_date')),
        $builder->produce('date_to_timestamp')
          ->map('field', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Event', 'endDate',
      $builder->compose(
        $builder->produce('field')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_event_date_end')),
        $builder->produce('date_to_timestamp')
          ->map('field', $builder->fromParent())
          ->map('type', $builder->fromValue('end_date'))
      )
    );

    $registry->addFieldResolver('Event', 'location',
      $builder->fromPath('entity:node', 'field_event_location.value')
    );

    $registry->addFieldResolver('Event', 'address',
      $builder->produce('entity_address')
        ->map('entity', $builder->fromParent())
        ->map('field', $builder->fromValue('field_event_address'))
    );

    $registry->addFieldResolver('Event', 'eventType',
      $builder->compose(
        $builder->produce('entity_reference')
          ->map('entity', $builder->fromParent())
          ->map('field', $builder->fromValue('field_event_type')),
        $builder->produce('seek')
          ->map('input', $builder->fromParent())
          ->map('position', $builder->fromValue(0))
      )
    );

    $registry->addFieldResolver('Event', 'url',
      $builder->compose(
        $builder->produce('social_entity_url')
          ->map('entity', $builder->fromParent())
          ->map('options', $builder->fromValue(['absolute' => TRUE])),
        $builder->produce('url_path')
          ->map('url', $builder->fromParent())
      )
    );

    $registry->addFieldResolver('Event', 'created',
      $builder->produce('entity_created')
        ->map('entity', $builder->fromParent())
        ->map('format', $builder->fromValue('U'))
    );

    $registry->addFieldResolver('Event', 'managers',
      $builder->produce('event_managers')
        ->map('event', $builder->fromParent())
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
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
    $registry->addFieldResolver('Query', 'events',
      $builder->produce('query_event')
        ->map('after', $builder->fromArgument('after'))
        ->map('before', $builder->fromArgument('before'))
        ->map('first', $builder->fromArgument('first'))
        ->map('last', $builder->fromArgument('last'))
        ->map('reverse', $builder->fromArgument('reverse'))
        ->map('sortKey', $builder->fromArgument('sortKey'))
    );

    $registry->addFieldResolver('Query', 'event',
      $builder->produce('entity_load_by_uuid')
        ->map('type', $builder->fromValue('node'))
        ->map('bundles', $builder->fromValue(['event']))
        ->map('uuid', $builder->fromArgument('id'))
    );

    $registry->addFieldResolver('Query', 'eventTypes',
      $builder->produce('taxonomy_load_tree')
        ->map('vid', $builder->fromValue('event_types'))
        ->map('parent', $builder->fromValue(0))
        ->map('max_depth', $builder->fromValue(1))
    );

    // Add eventsCreated field to the user object.
    $registry->addFieldResolver('User', 'eventsCreated',
      $builder->produce('social_events_created')
        ->map('entity', $builder->fromParent())
    );

    // Add EventType resolvers.
    $registry->addFieldResolver('EventType', 'id',
      $builder->produce('entity_uuid')
        ->map('entity', $builder->fromParent())
    );

    $registry->addFieldResolver('EventType', 'label',
      $builder->produce('entity_label')
        ->map('entity', $builder->fromParent())
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionDefinition(): ?Source {
    $definition = parent::getExtensionDefinition();
    assert($definition !== NULL, "SdlSchemaExtensionPluginBase never returns null.");

    $source = $definition->body;
    $name = $definition->name;

    // Then, load additional extension schemas. These files extend the base
    // Event type with groups field (flexible_group) and organizations field
    // (organization). Only load extensions if their corresponding modules are
    // enabled.
    $extension_modules = [
      'social_group_flexible_group' => 'social_event_flexible_group_schema_extension',
      'social_organization' => 'social_event_organization_schema_extension',
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
