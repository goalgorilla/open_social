<?php
// phpcs:ignoreFile

namespace Drupal\social_graphql\Plugin\GraphQL\Schema;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\graphql\GraphQL\ResolverRegistryInterface;
use Drupal\graphql\Plugin\GraphQL\Schema\SdlSchemaPluginBase as SdlSchemaPluginBaseBase;
use Drupal\graphql\Plugin\GraphQL\SchemaExtension\SdlSchemaExtensionPluginBase;
use Drupal\graphql\Plugin\SchemaExtensionPluginInterface;
use Drupal\social_graphql\Utils\AST;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\Language\AST\InterfaceTypeDefinitionNode;
use GraphQL\Language\AST\TypeDefinitionNode;
use GraphQL\Language\AST\UnionTypeDefinitionNode;
use GraphQL\Language\Parser;
use GraphQL\Language\Source;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaExtender;

/**
 * Base class that can be used by schema plugins.
 */
abstract class SdlSchemaPluginBase extends SdlSchemaPluginBaseBase {

  /**
   * {@inheritdoc}
   *
   * @throws \GraphQL\Error\SyntaxError
   * @throws \GraphQL\Error\Error
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function getSchema(ResolverRegistryInterface $registry) {
    $extensions = $this->getExtensions();
    $document = $this->getSchemaDocument($extensions);

    foreach ($extensions as $extension) {
      $extension->registerResolvers($registry);
    }

    // The base schema is built with the registry to ensure all resolvers are,
    // registered. SchemaExtender::extend must be used because in the PHP
    // implementation (contrary to the JS implementation) the BuildSchema::build
    // call does not properly process `extend` keywords in the SDL; and the
    // extender itself expects `Query`/`Mutation`/`Subscription` to be declared
    // exactly zero or one times.
    $schema = $this->buildSchema($document, $registry);
    $extensionAst = $this->getExtensionDocument($extensions);
    return $extensionAst
      ? SchemaExtender::extend($schema, $extensionAst)
      : $schema;
  }

  /**
   * Create a GraphQL schema object from the given AST document.
   *
   * This method is private for now as the build/cache approach might change.
   */
  protected function buildSchema(DocumentNode $astDocument, ResolverRegistryInterface $registry): Schema {
    $resolver = [$registry, 'resolveType'];
    // Performance: only validate the schema in development mode, skip it in
    // production on every request.
    $options = empty($this->inDevelopment) ? ['assumeValid' => TRUE] : [];
    $schema = BuildSchema::build($astDocument, function ($config, TypeDefinitionNode $type) use ($resolver) {
      if ($type instanceof InterfaceTypeDefinitionNode || $type instanceof UnionTypeDefinitionNode) {
        $config['resolveType'] = $resolver;
      }

      return $config;
    }, $options);
    return $schema;
  }

  /**
   * @return \Drupal\graphql\Plugin\SchemaExtensionPluginInterface[]
   */
  protected function getExtensions() {
    return $this->extensionManager->getExtensions($this->getPluginId());
  }

  /**
   * Retrieves the parsed AST of the schema definition.
   *
   * @param array $extensions
   *
   * @return \GraphQL\Language\AST\DocumentNode
   *   The parsed schema document.
   *
   * @throws \GraphQL\Error\SyntaxError
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getSchemaDocument(array $extensions = []) {
    // Only use caching of the parsed document if we aren't in development mode.
    $cid = $this->getCacheId('schema');
    if (empty($this->inDevelopment) && $cache = $this->astCache->get($cid)) {
      return $cache->data;
    }

    $baseSchemaDocument = $this->getSchemaDefinition();
    if (!$baseSchemaDocument instanceof Source) {
      @trigger_error('Returning a ' . get_debug_type($baseSchemaDocument) . ' from `getSchemaDefinition` is deprecated in graphql:4.6 and is disallowed from graphql:5.0.0. Return \GraphQL\Language\Source instead. See https://www.drupal.org/node/', E_USER_DEPRECATED);
      $baseSchemaDocument = new Source($baseSchemaDocument ?? "");
    }
    // For caching and parsing big schemas we need to disable the creation of
    // location nodes in the AST object to prevent serialization and memory
    // errors. See https://github.com/webonyx/graphql-php/issues/1164. In
    // development, we don't cache and can provide location info for debugging.
    $ast = Parser::parse($baseSchemaDocument, ['noLocation' => !$this->inDevelopment]);

    $extensions = array_filter(array_map(function (SchemaExtensionPluginInterface $extension) {
      /** @var \GraphQL\Language\Source|string|null $schema */
      $schema = $extension->getBaseDefinition();
      if ($schema === NULL) {
        return NULL;
      }
      if (!$schema instanceof Source) {
        @trigger_error('Returning a ' . get_debug_type($schema) . ' from `' . get_class($extension) . '::getBaseDefinition` is deprecated in graphql:4.6 and is disallowed from graphql:5.0.0. Return \GraphQL\Language\Source instead. See https://www.drupal.org/node/', E_USER_DEPRECATED);
        $schema = new Source($schema);
      }
      return Parser::parse($schema, ['noLocation' => !$this->inDevelopment]);
    }, $extensions), function ($definition) {
      return !empty($definition);
    });

    $ast = AST::concatAST([$ast, ...$extensions]);

    if (empty($this->inDevelopment)) {
      $this->astCache->set($cid, $ast, CacheBackendInterface::CACHE_PERMANENT, ['graphql']);
    }

    return $ast;
  }

  /**
   * Retrieves the parsed AST of the schema extension definitions.
   *
   * @param array $extensions
   *
   * @return \GraphQL\Language\AST\DocumentNode|null
   *   The parsed schema document.
   *
   * @throws \GraphQL\Error\SyntaxError
   */
  protected function getExtensionDocument(array $extensions = []) {
    // Only use caching of the parsed document if we aren't in development mode.
    $cid = $this->getCacheId("extension");
    if (empty($this->inDevelopment) && $cache = $this->astCache->get($cid)) {
      return $cache->data;
    }


    $extensions = array_filter(array_map(function (SchemaExtensionPluginInterface $extension) {
      /** @var \GraphQL\Language\Source|string|null $schema */
      $schema = $extension->getExtensionDefinition();
      if ($schema === NULL) {
        return NULL;
      }
      if (!$schema instanceof Source) {
        @trigger_error('Returning a ' . get_debug_type($schema) . ' from `' . get_class($extension) . '::getExtensionDefinition` is deprecated in graphql:4.6 and is disallowed from graphql:5.0.0. Return \GraphQL\Language\Source instead. See https://www.drupal.org/node/', E_USER_DEPRECATED);
        $schema = new Source($schema);
      }
      return Parser::parse($schema, ['noLocation' => !$this->inDevelopment]);
    }, $extensions), function ($definition) {
      return !empty($definition);
    });

    $ast = $extensions !== [] ? AST::concatAST($extensions) : NULL;

    if (empty($this->inDevelopment)) {
      $this->astCache->set($cid, $ast, CacheBackendInterface::CACHE_PERMANENT, ['graphql']);
    }

    return $ast;
  }

  /**
   * Retrieves the raw schema definition string.
   *
   * @return \GraphQL\Language\Source|string|null
   *   The schema definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getSchemaDefinition() {
    $id = $this->getPluginId();
    $definition = $this->getPluginDefinition();
    assert(is_array($definition));
    $module = $this->moduleHandler->getModule($definition['provider']);
    $path = 'graphql/' . $id . '.graphqls';
    $file = $module->getPath() . '/' . $path;

    if (!file_exists($file)) {
      throw new InvalidPluginDefinitionException(
        $id,
        sprintf(
          'The module "%s" needs to have a schema definition "%s" in its folder for "%s" to be valid.',
          $module->getName(), $path, $definition['class']));
    }

    return file_get_contents($file) ?: NULL;
  }

  /**
   * Returns a cache ID for the given type.
   *
   * @param string $type
   *   The cache type, e.g. 'schema' or 'full'.
   *
   * @return string
   *   The cache ID.
   */
  protected function getCacheId(string $type): string {
    // Configurable schema plugins should be cached per server since the schema
    // depends on the server configuration.
    if ($this instanceof ConfigurableInterface) {
      $server_id = $this->getConfiguration()['server_id'] ?? NULL;
      if ($server_id) {
        return "{$type}:{$this->getPluginId()}:{$server_id}";
      }
      @trigger_error('Retrieving a GraphQL schema from a configurable schema plugin instance without setting the "server_id" in the plugin configuration is deprecated in graphql:4.11.0 and will cause an InvalidPluginDefinitionException to be thrown from graphql:5.0.0. Ensure to always pass this configuration value so that the schema can be properly cached per server. See https://www.drupal.org/project/graphql/issues/3491736', E_USER_DEPRECATED);
    }
    return "{$type}:{$this->getPluginId()}";
  }

}
