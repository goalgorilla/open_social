<?php

namespace Drupal\dynamic_entity_reference;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for Dynamic Entity Reference.
 */
class DynamicEntityReferenceServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['rest']) && isset($modules['serialization']) && isset($modules['hal'])) {

      // Add a normalizer service for dynamic_entity_reference fields.
      $service_definition = new Definition('Drupal\dynamic_entity_reference\Normalizer\DynamicEntityReferenceItemNormalizer', array(
        new Reference('rest.link_manager'),
        new Reference('serializer.entity_resolver'),
        new Reference('module_handler'),
      ));
      // The priority must be higher than that of
      // serializer.normalizer.entity_reference.hal in hal.services.yml.
      $service_definition->addTag('normalizer', array('priority' => 20));
      $container->setDefinition('serializer.normalizer.entity.dynamic_entity_reference_item.hal', $service_definition);

    }
  }

}
