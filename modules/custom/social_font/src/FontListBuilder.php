<?php

namespace Drupal\social_font;

use Drupal\Core\Link;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Font entities.
 *
 * @ingroup social_font
 */
class FontListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Font ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\social_font\Entity\Font */
    $row['id'] = $entity->id();
    // TODO: Drupal Rector Notice: Please delete the following comment after you've made any necessary changes.
    // Please manually remove the `use LinkGeneratorTrait;` statement from this class.
    $row['name'] = Link::fromTextAndUrl($entity->label(), new Url(
      'entity.font.edit_form', [
        'font' => $entity->id(),
      ]
    ));
    return $row + parent::buildRow($entity);
  }

}
