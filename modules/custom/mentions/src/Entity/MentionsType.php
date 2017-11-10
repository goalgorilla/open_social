<?php

namespace Drupal\mentions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Mentions Type entity.
 *
 * @ConfigEntityType(
 *   id = "mentions_type",
 *   label = @Translation("Mentions Type"),
 *   handlers = {
 *     "list_builder" = "Drupal\mentions\MentionsConfigListBuilder",
 *     "form" = {
 *       "add" = "Drupal\mentions\Form\MentionsTypeForm",
 *       "edit" = "Drupal\mentions\Form\MentionsTypeForm",
 *       "delete" = "Drupal\mentions\Form\MentionsTypeDeleteForm"
 *     }
 *   },
 *   config_prefix = "mentions_type",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "name" = "name"
 *   },
 *   links = {
 *     "collection" = "/admin/structure/mentions",
 *     "edit_form" = "/admin/structure/mentions/{mentions_type}/edit",
 *     "delete_form" = "/admin/structure/mentions/{mentions_type}/delete"
 *   },
 *
 *   config_expport = {
 *     "id",
 *     "name"
 *   }
 *
 * )
 */
class MentionsType extends ConfigEntityBase implements MentionsTypeInterface {

  /**
   * The Mentions Type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Name and ID of Mentions Type.
   *
   * @var string
   */
  protected $name;

  /**
   * Description of Mentions Type.
   *
   * @var string
   */
  protected $description;

  /**
   * Where mention type appears.
   *
   * @var string
   */
  protected $mentionType;

  /**
   * What is looked for when mentions are parsed.
   *
   * Keys of array: prefix, entity_type, inputvalue, suffix.
   *
   * @var string
   */
  protected $input = [];

  /**
   * What is looked for when mentions are parsed.
   *
   * Keys of array: outputvalue, renderlink.
   *
   * @var string
   */
  protected $output = [];

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function mentionType() {
    return $this->mentionType;
  }

  /**
   * {@inheritdoc}
   */
  public function getInputSettings() {
    return $this->input;
  }

}
