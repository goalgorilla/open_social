<?php

namespace Drupal\search_api\Processor;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Manages processor plugins.
 *
 * @see \Drupal\search_api\Annotation\SearchApiProcessor
 * @see \Drupal\search_api\Processor\ProcessorInterface
 * @see \Drupal\search_api\Processor\ProcessorPluginBase
 * @see plugin_api
 */
class ProcessorPluginManager extends DefaultPluginManager {

  use StringTranslationTrait;

  /**
   * Constructs a ProcessorPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The string translation manager.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, TranslationInterface $translation) {
    parent::__construct('Plugin/search_api/processor', $namespaces, $module_handler, 'Drupal\search_api\Processor\ProcessorInterface', 'Drupal\search_api\Annotation\SearchApiProcessor');
    $this->setCacheBackend($cache_backend, 'search_api_processors');
    $this->alterInfo('search_api_processor_info');
    $this->setStringTranslation($translation);
  }

  /**
   * Retrieves information about the available processing stages.
   *
   * These are then used by processors in their "stages" definition to specify
   * in which stages they will run.
   *
   * @return array
   *   An associative array mapping stage identifiers to information about that
   *   stage. The information itself is an associative array with the following
   *   keys:
   *   - label: The translated label for this stage.
   */
  public function getProcessingStages() {
    return array(
      ProcessorInterface::STAGE_PREPROCESS_INDEX => array(
        'label' => $this->t('Preprocess index'),
      ),
      ProcessorInterface::STAGE_PREPROCESS_QUERY => array(
        'label' => $this->t('Preprocess query'),
      ),
      ProcessorInterface::STAGE_POSTPROCESS_QUERY => array(
        'label' => $this->t('Postprocess query'),
      ),
    );
  }

}
