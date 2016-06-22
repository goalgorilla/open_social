<?php

namespace Drupal\search_api\Plugin\search_api\processor;

use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Language\Language as CoreLanguage;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Adds the item language to indexed items.
 *
 * @SearchApiProcessor(
 *   id = "language",
 *   label = @Translation("Language"),
 *   description = @Translation("Adds the item language to indexed items."),
 *   stages = {
 *     "pre_index_save" = -10,
 *     "preprocess_index" = -30
 *   },
 *   locked = true,
 *   hidden = true
 * )
 */
class Language extends ProcessorPluginBase {

  // @todo Config form for setting the field containing the langcode if
  //   language() is not available?

  /**
   * {@inheritdoc}
   */
  public function alterPropertyDefinitions(array &$properties, DatasourceInterface $datasource = NULL) {
    if ($datasource) {
      return;
    }
    $definition = array(
      'type' => 'string',
      'label' => $this->t('Item language'),
      'description' => $this->t('The language code of the item'),
    );
    $properties['search_api_language'] = new DataDefinition($definition);
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    $this->ensureField(NULL, 'search_api_language', 'string');
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item) {
      $object = $item->getOriginalObject();
      // Workaround for recognizing entities.
      if ($object instanceof EntityAdapter) {
        $object = $object->getValue();
      }

      if ($object instanceof TranslatableInterface) {
        $langcode = $object->language()->getId();
      }
      else {
        $langcode = CoreLanguage::LANGCODE_NOT_SPECIFIED;
      }

      foreach ($this->filterForPropertyPath($item->getFields(), 'search_api_language') as $field) {
        $field->addValue($langcode);
      }
    }
  }

}
