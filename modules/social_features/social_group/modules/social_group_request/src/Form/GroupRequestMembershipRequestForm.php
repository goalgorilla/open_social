<?php

namespace Drupal\social_group_request\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grequest\Entity\Form\GroupMembershipRequestForm;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\grequest\MembershipRequestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to request group membership.
 */
class GroupRequestMembershipRequestForm extends GroupMembershipRequestForm {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a new GroupRequestMembershipRequestForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\grequest\MembershipRequestManager $membership_request_manager
   *   Membership request manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, MembershipRequestManager $membership_request_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time, $membership_request_manager);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('grequest.membership_request_manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Tweak the text shown depending on whether a message can be added.
    $form_description = isset($form['field_grequest_message']) && ($form['field_grequest_message']['#access'] ?? TRUE)
      ? t("You can leave a message in your request. Only when your request is approved, you will receive a notification via email and notification center.")
      : t("Only when your request is approved, you will receive a notification via email and notification center.");

    // Add a description to the top of the form.
    $form['description'] = [
      '#type' => 'inline_template',
      '#template' => '<p>{{ description }}</p>',
      '#context' => [
        'description' => $form_description,
      ],
      '#weight' => ($form['field_grequest_message']['#weight'] ?? 0) - 10,
    ];

    // Remove the cancel link since there's a cross to close the dialog.
    unset($form['actions']['cancel']);

    // Override the request form submit action which we expect to always exist.
    assert(isset($form['actions']['submit']), "The grequest module has removed the 'submit' action from its form, this form alter needs updating.");
    $form['actions']['submit']['#value'] = t('Send request');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $return = parent::save($form, $form_state);

    /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
    $group_relationship = $this->getEntity();
    $group = $group_relationship->getGroup();

    // Add cache tags for group views.
    $tags = [
      'group_list',
      'group_content_list',
      'group_view',
      'group_content_view',
    ];
    // Add cache tags that are based on group id.
    $tags[] = 'group_hero:' . $group->id();
    $tags[] = 'group_block:' . $group->id();
    // Add cache tags that are based on group content.
    $tags[] = 'group_content:' . $this->getEntity()->id();
    $this->cacheTagsInvalidator->invalidateTags($tags);

    return $return;
  }

}
