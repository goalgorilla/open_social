<?php

namespace Drupal\facets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\Entity\FacetSource;
use Drupal\facets\UrlProcessor\UrlProcessorPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for editing facet sources.
 *
 * Configuration saved trough this form is specific for a facet source and can
 * be used by all facets on this facet source.
 */
class FacetSourceEditForm extends EntityForm {

  /**
   * The plugin manager for URL Processors.
   *
   * @var \Drupal\facets\UrlProcessor\UrlProcessorPluginManager
   */
  protected $urlProcessorPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    /** @var \Drupal\facets\UrlProcessor\UrlProcessorPluginManager $url_processor_plugin_manager */
    $url_processor_plugin_manager = $container->get('plugin.manager.facets.url_processor');

    return new static($entity_type_manager, $url_processor_plugin_manager);
  }

  /**
   * Constructs a FacetSourceEditForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\facets\UrlProcessor\UrlProcessorPluginManager $url_processor_plugin_manager
   *   The url processor plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UrlProcessorPluginManager $url_processor_plugin_manager) {
    $facet_source_storage = $entity_type_manager->getStorage('facets_facet_source');

    $this->urlProcessorPluginManager = $url_processor_plugin_manager;

    // Make sure we remove colons from the source id, those are disallowed in
    // the entity id.
    $source_id = $this->getRequest()->get('source_id');
    $source_id = str_replace(':', '__', $source_id);

    $facet_source = $facet_source_storage->load($source_id);

    if ($facet_source instanceof FacetSource) {
      $this->setEntity($facet_source);
    }
    else {
      // We didn't have a facet source config entity yet for this facet source
      // plugin, so we create it on the fly.
      $facet_source = new FacetSource(
        [
          'id' => $source_id,
          'name' => $this->getRequest()->get('source_id'),
        ],
        'facets_facet_source'
      );
      $facet_source->save();
      $this->setEntity($facet_source);
    }

    $this->setModuleHandler(\Drupal::moduleHandler());
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'facet_source_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\facets\FacetSourceInterface $facet_source */
    $facet_source = $this->getEntity();

    $form['filter_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Filter key'),
      '#size' => 20,
      '#maxlength' => 255,
      '#default_value' => $facet_source->getFilterKey(),
      '#description' => $this->t(
        'The key used in the url to identify the facet source.
        When using multiple facet sources you should make sure each facet source has a different filter key.'
      ),
    ];

    $url_processors = array();
    $url_processors_description = array();
    foreach ($this->urlProcessorPluginManager->getDefinitions() as $definition) {
      $url_processors[$definition['id']] = $definition['label'];
      $url_processors_description[] = $definition['description'];
    }
    $form['url_processor'] = [
      '#type' => 'radios',
      '#title' => $this->t('URL Processor'),
      '#options' => $url_processors,
      '#default_value' => $facet_source->getUrlProcessorName(),
      '#description' => $this->t(
        'The URL Processor defines the url structure used for this facet source.') . '<br />- ' . implode('<br>- ', $url_processors_description),
    ];

    // The parent's form build method will add a save button.
    return parent::buildForm($form, $form_state);
  }

}
