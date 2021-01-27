<?php

namespace Drupal\social_graphql_devel\Controller;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql\Entity\ServerInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\SchemaPluginManager;
use GraphQL\Error\InvariantViolation;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for the GraphiQL resolver validation.
 */
class ValidateResolversController implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The schema plugin manager.
   *
   * @var \Drupal\graphql\Plugin\SchemaPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.graphql.schema'),
    );
  }

  /**
   * ValidateResolverController constructor.
   *
   * @param \Drupal\graphql\Plugin\SchemaPluginManager $pluginManager
   *   The schema plugin manager.
   */
  public function __construct(SchemaPluginManager $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * Controller for the GraphiQL query builder IDE.
   *
   * @param \Drupal\graphql\Entity\ServerInterface $graphql_server
   *   The server.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return array
   *   The render array.
   */
  public function validateResolvers(ServerInterface $graphql_server, Request $request) {
    $schema_name = $graphql_server->get('schema');
    /** @var \Drupal\graphql\Plugin\SchemaPluginInterface $plugin */
    $plugin = $this->pluginManager->createInstance($schema_name);
    if ($plugin instanceof ConfigurableInterface && $config = $graphql_server->get('schema_configuration')) {
      $plugin->setConfiguration($config[$schema_name] ?? []);
    }
    $resolver_registry = $plugin->getResolverRegistry();

    $schema = $plugin->getSchema($resolver_registry);
    if ($schema === NULL) {
      return [
        '#type' => 'inline_template',
        '#template' => "<p>{{ server_name }} has no schema.</p>",
        '#context' => [
          'server_name' => $graphql_server->id(),
        ],
      ];
    }

    if (!method_exists($resolver_registry, "getAllFieldResolvers")) {
      return [
        '#type' => 'inline_template',
        '#template' => "<p>{{ server_name }}'s resolver registry ({{ klass }}) doesn't implement getAllFieldResolvers.</p>",
        '#context' => [
          'server_name' => $graphql_server->id(),
          'klass' => get_class($resolver_registry),
        ],
      ];
    }

    $missing_resolvers = [];
    foreach ($schema->getTypeMap() as $type) {
      // We only care about concrete fieldable types. Resolvers may be defined
      // for interfaces to be available for all implementing types, but only the
      // actual resolved types need resolvers for their fields.
      if (!$type instanceof ObjectType && !$type instanceof InputObjectType) {
        continue;
      }
      foreach ($type->getFields() as $field) {
        if (!$this->hasResolver($resolver_registry, $type, $field)) {
          if (!isset($missing_resolvers[$type->name])) {
            $missing_resolvers[$type->name] = [];
          }
          $missing_resolvers[$type->name][] = $field->name;
        }
      }
    }

    $orphaned_resolvers = [];
    foreach ($resolver_registry->getAllFieldResolvers() as $type_name => $fields) {
      $type = $schema->getType($type_name);
      // If the type can't have any fields then our resolvers don't make sense.
      if (!$type instanceof InterfaceType &&
          !$type instanceof ObjectType &&
          !$type instanceof InputObjectType) {
        $orphaned_resolvers[$type_name] = $fields;
        continue;
      }

      foreach ($fields as $field_name => $resolver) {
        try {
          $type->getField($field_name);
        }
        catch (InvariantViolation $_) {
          if (!isset($orphaned_resolvers[$type_name])) {
            $orphaned_resolvers[$type_name] = [];
          }
          $orphaned_resolvers[$type_name][] = $field_name;
        }
      }
    }

    $build = [
      'orphaned' => [
        '#type' => 'table',
        '#header' => [$this->t('Type'), $this->t('Fields')],
        '#caption' => $this->t("Resolvers without schema"),
        '#empty' => $this->t("No orphaned resolvers."),
      ],
      'missing' => [
        '#type' => 'table',
        '#header' => [$this->t('Type'), $this->t('Fields')],
        '#caption' => $this->t("Fields without resolvers"),
        '#empty' => $this->t("No missing resolvers."),
      ],
    ];

    $metrics = [
      'orphaned' => $orphaned_resolvers,
      'missing' => $missing_resolvers,
    ];

    foreach ($metrics as $metric_type => $data) {
      foreach ($data as $type => $fields) {
        $build[$metric_type][$type] = [
          'type' => ['#plain_text' => $type],
          'fields' => [
            '#theme' => 'item_list',
            '#items' => $fields,
          ],
        ];
      }
    }

    return $build;

  }

  /**
   * Try to find a resolver for the type or one of its implemented interfaces.
   *
   * @param \Drupal\graphql\GraphQL\ResolverRegistryInterface $registry
   *   The registry to find a resolver in.
   * @param \GraphQL\Type\Definition\Type $type
   *   The type definition to find a resolver for.
   * @param \GraphQL\Type\Definition\FieldDefinition|\GraphQL\Type\Definition\InputObjectType $field
   *   The field on the type to find a resolver for.
   *
   * @return bool
   *   Whether the registry has a registered resolver.
   */
  protected function hasResolver(ResolverRegistryInterface $registry, Type $type, $field) : bool {
    // Skip hidden/internal/introspection types since they're handled by GraphQL
    // itself.
    if (strpos($type->name, "__") === 0) {
      return TRUE;
    }

    if ($this->isStructuredDataType($type->name)) {
      return TRUE;
    }

    if ($registry->getFieldResolver($type->name, $field->name) !== NULL) {
      return TRUE;
    }

    // If the type doesn't support interfaces we're done.
    if (!method_exists($type, "getInterfaces")) {
      return FALSE;
    }

    /** @var \GraphQL\Type\Definition\Type $interface */
    foreach ($type->getInterfaces() as $interface) {
      if ($registry->getFieldResolver($interface->name, $field->name) !== NULL) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Check whether the type is covered by structured data.
   *
   * When a type can be resolved using structured data then it needs no
   * individual field resolvers.
   *
   * @param string $type
   *   The name of the type.
   *
   * @return bool
   *   Whether the type is covered by a structured data type definition.
   */
  protected function isStructuredDataType(string $type) : bool {
    // There doesn't seem to be an easy way to automate this check so for now we
    // just keep track of a static list that we know about.
    return in_array($type, [
      'PageInfo',
    ]);
  }

}
