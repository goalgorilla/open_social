<?php

namespace Drupal\social_search\Plugin\search_api\processor;

use Drupal\search_api\IndexInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\profile\Entity\ProfileInterface;
use Drupal\social_user\Service\SocialUserHelper;
use Drupal\social_user\Service\SocialUserHelperInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ignores not Verified users in index.
 *
 * Makes sure not Verified users are no longer indexed.
 *
 * @SearchApiProcessor(
 *   id = "verified_users",
 *   label = @Translation("Skip not Verified users"),
 *   description = @Translation("Makes sure that only Verified users are added to the index."),
 *   stages = {
 *     "alter_items" = 0,
 *   },
 * )
 */
class VerifiedUsers extends ProcessorPluginBase {

  /**
   * The social user helper.
   *
   * @var \Drupal\social_user\Service\SocialUserHelperInterface
   */
  protected $socialUserHelper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $processor */
    $processor = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $processor->setSocialUserHelper($container->get('social_user.helper'));

    return $processor;
  }

  /**
   * Sets the social user helper.
   *
   * @param \Drupal\social_user\Service\SocialUserHelperInterface $social_user_helper
   *   The social user helper.
   *
   * @return $this
   */
  public function setSocialUserHelper(SocialUserHelperInterface $social_user_helper) {
    $this->socialUserHelper = $social_user_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    $entity_types = ['profile'];
    foreach ($index->getDatasources() as $datasource) {
      if (in_array($datasource->getEntityTypeId(), $entity_types)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      if ($object instanceof ProfileInterface) {
        // Profile owner ID is the user ID.
        if (!SocialUserHelper::isVerifiedUser($object->getOwner())) {
          unset($items[$item_id]);
        }
      }
      elseif ($object instanceof UserInterface) {
        if (!SocialUserHelper::isVerifiedUser($object)) {
          unset($items[$item_id]);
        }
      }
    }
  }

}
