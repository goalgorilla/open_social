<?php

namespace Drupal\address\Entity;

use Drupal\address\Plugin\ZoneMember\ZoneMemberInterface;
use Drupal\address\ZoneMemberPluginCollection;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use CommerceGuys\Addressing\Model\AddressInterface;
use CommerceGuys\Zone\Exception\UnexpectedTypeException;

/**
 * Defines the Zone configuration entity.
 *
 * @ConfigEntityType(
 *   id = "zone",
 *   label = @Translation("Zone"),
 *   handlers = {
 *     "list_builder" = "Drupal\address\ZoneListBuilder",
 *     "form" = {
 *       "add" = "Drupal\address\Form\ZoneForm",
 *       "edit" = "Drupal\address\Form\ZoneForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer zones",
 *   config_prefix = "zone",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "scope",
 *     "priority",
 *     "members",
 *   },
 *   links = {
 *     "collection" = "/admin/config/regional/zones",
 *     "edit-form" = "/admin/config/regional/zones/manage/{zone}",
 *     "delete-form" = "/admin/config/regional/zones/manage/{zone}/delete"
 *   }
 * )
 */
class Zone extends ConfigEntityBase implements ZoneInterface {

  /**
   * Zone id.
   *
   * @var string
   */
  protected $id;

  /**
   * Zone name.
   *
   * @var string
   */
  protected $name;

  /**
   * Zone scope.
   *
   * @var string
   */
  protected $scope;

  /**
   * Zone priority.
   *
   * @var int
   */
  protected $priority;

  /**
   * Zone members.
   *
   * @var array
   */
  protected $members = [];

  /**
   * Zone members collection.
   *
   * @var \Drupal\address\ZoneMemberPluginCollection
   */
  protected $membersCollection;

  /**
   * {@inheritdoc}
   */
  public function label() {
    $label = $this->name;
    if ($this->scope) {
      $label .= ' (' . $this->scope . ')';
    }

    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->id = $id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->name = $name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getScope() {
    return $this->scope;
  }

  /**
   * {@inheritdoc}
   */
  public function setScope($scope) {
    $this->scope = $scope;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPriority() {
    return $this->priority;
  }

  /**
   * {@inheritdoc}
   */
  public function setPriority($priority) {
    $this->priority = $priority;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembers() {
    if (!$this->membersCollection) {
      $plugin_manager = $this->getZoneMemberPluginManager();
      $this->membersCollection = new ZoneMemberPluginCollection($plugin_manager, $this->members, $this);
      $this->membersCollection->sort();
    }
    return $this->membersCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function setMembers($members) {
    if (!($members instanceof ZoneMemberPluginCollection)) {
      throw new UnexpectedTypeException($members, 'ZoneMemberPluginCollection');
    }
    $this->membersCollection = $members;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMembers() {
    return $this->getMembers()->count() !== 0;
  }

  /**
   * {@inheritdoc}
   */
  public function addMember(ZoneMemberInterface $member) {
    $this->getMembers()->set($member->getId(), $member);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removeMember(ZoneMemberInterface $member) {
    $this->getMembers()->removeInstanceId($member->getId());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMember(ZoneMemberInterface $member) {
    return $this->getMembers()->has($member->getId());
  }

  /**
   * {@inheritdoc}
   */
  public function match(AddressInterface $address) {
    foreach ($this->getMembers() as $member) {
      if ($member->match($address)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginCollections() {
    return ['members' => $this->getMembers()];
  }

  /**
   * Gets the zone member plugin manager.
   *
   * @return \Drupal\address\ZoneMemberManager
   *   The zone member plugin manager.
   */
  protected function getZoneMemberPluginManager() {
    return \Drupal::service('plugin.manager.address.zone_member');
  }

}
