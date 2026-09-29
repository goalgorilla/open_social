<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event_invite\Kernel;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\node\NodeInterface;
use Drupal\social_event\EventEnrollmentInterface;
use Drupal\social_event_invite\Form\EventInviteEnrollActionForm;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;
use Symfony\Component\Routing\Route;

/**
 * Tests the invite enroll form for logged-out visitors.
 *
 * @group social_event_invite
 */
class EventInviteEnrollActionFormTest extends SocialEventGraphQLKernelTestBase {

  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'address',
    'datetime',
    'subgroup',
    'paragraphs',
    'image',
    'options',
    'file',
    'link',
    'entity_reference_revisions',
    'media',
    'node',
    'grequest',
    'state_machine',
    'social_group',
    'activity_logger',
    'activity_creator',
    'message',
    'dynamic_entity_reference',
    'social_group_flexible_group',
    'social_group_request',
    'social_media_system',
    'select2',
    'text',
    'pathauto',
    'smart_trim',
    'path',
    'path_alias',
    'token',
    'inline_entity_form',
    'workflows',
    'content_moderation',
    'better_exposed_filters',
    'filter',
    'views_bulk_operations',
    'gnode',
    'social_event',
    'social_event_type',
    'social_event_managers',
    'social_event_invite',
    'social_topic',
    'datetime_range_timezone',
    'key',
    'meeting_api',
    'meeting_api_bbb',
    'meeting_api_manual',
    'profile',
    'social_profile',
    'views',
    'group_core_comments',
    'menu_ui',
    'comment',
    'editor',
    'ckeditor5',
    'responsive_table_filter',
    'social_editor',
    'social_node',
    'social_core',
    'field_group',
    'file_mdm',
    'image_effects',
    'image_widget_crop',
    'crop',
    'block',
    'block_content',
    'entity_access_by_field',
    'entity',
    'entity_test',
    'telephone',
    'lazy',
    'serialization',
    'group',
    'social_user',
    'consumers',
    'simple_oauth',
    'simple_oauth_static_scope',
    'social_oauth',
    'social_graphql',
    'graphql_oauth',
    'social_comment',
    'taxonomy',
    'role_delegation',
    'variationcache',
    'menu_link_content',
    'flag',
    'field',
    'social_group_invite',
    'ginvite',
    'layout_builder',
    'layout_discovery',
    'flag_count',
    'hux',
    'taxonomy_access_fix',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpCommonSchemaConfig(): void {
    // social_event_invite looks up enrollments when a user is created.
    $this->installEntitySchema('event_enrollment');
    parent::setUpCommonSchemaConfig();
  }

  /**
   * A logged-out visitor gets no enroll button when enrollment is off.
   *
   * The route event is used, not the node passed into the form.
   */
  public function testAnonymousFormIsEmptyWhenEnrollmentIsDisabled(): void {
    $route_event = $this->createInviteEvent(FALSE);
    $other_event = $this->createInviteEvent(TRUE);
    $this->setCurrentUser(new AnonymousUserSession());
    $this->setRouteNode($route_event);

    $form = $this->container->get('form_builder')->getForm(EventInviteEnrollActionForm::class, $other_event);

    $this->assertArrayNotHasKey('enroll_wrapper', $form);
    $this->assertArrayNotHasKey('enroll_for_this_event', $form);
  }

  /**
   * A logged-out visitor does not see the button on an invite-only event.
   */
  public function testAnonymousInviteFormHidesEnrollWrapper(): void {
    $event = $this->createInviteEvent(TRUE);
    $this->setCurrentUser(new AnonymousUserSession());
    $this->setRouteNode($event);

    $form = $this->container->get('form_builder')->getForm(EventInviteEnrollActionForm::class);

    $this->assertFalse($form['enroll_wrapper']['#access']);
  }

  /**
   * Creates a public invite-only event.
   */
  protected function createInviteEvent(bool $enrollment_enabled): NodeInterface {
    return $this->createNode([
      'type' => 'event',
      'title' => $this->randomMachineName(),
      'field_content_visibility' => 'public',
      'field_event_enroll' => $enrollment_enabled ? 1 : 0,
      'field_enroll_method' => EventEnrollmentInterface::ENROLL_METHOD_INVITE,
      'status' => 1,
    ]);
  }

  /**
   * Puts an event on the current route, the way the parent form reads it.
   */
  protected function setRouteNode(NodeInterface $node): void {
    $route_match = new RouteMatch(
      'entity.node.canonical',
      new Route('/node/{node}'),
      ['node' => $node],
      ['node' => $node->id()],
    );
    $this->container->set('current_route_match', $route_match);
  }

}
