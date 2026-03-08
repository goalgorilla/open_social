<?php

declare(strict_types=1);

namespace Drupal\Tests\social_event\Kernel\GraphQL;

use Drupal\Tests\social_event\Kernel\SocialEventGraphQLKernelTestBase;

/**
 * Tests CreateEventInput schema lacks organizations when module is disabled.
 *
 * When social_organization is not enabled, EventSchemaExtension does not load
 * the organization schema extension, so CreateEventInput has no "organizations"
 * field. A request including it gets a GraphQL validation error.
 *
 * @group social_event
 */
class CreateEventSchemaWithoutOrganizationTest extends SocialEventGraphQLKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts(): array {
    return [...parent::defaultCacheContexts(), 'languages:language_interface'];
  }

  /**
   * Test: Schema has no organizations field when social_organization disabled.
   *
   * When organizations is sent, GraphQL rejects it as unknown field.
   */
  public function testCreateEventInputHasNoOrganizationsFieldWhenModuleDisabled(): void {
    // Skip test if social_organization is not in the codebase.
    if (!static::socialOrganizationExists()) {
      $this->markTestSkipped('social_organization is not in the codebase.');
    }
    $this->actAsClientCredentialsWithScopes(['event:write']);

    $start = (new \DateTimeImmutable('2026-06-15T10:00:00Z'))->getTimestamp();
    $end = (new \DateTimeImmutable('2026-06-15T18:00:00Z'))->getTimestamp();

    $this->assertErrors(
      <<<GQL
      mutation CreateEvent(\$input: CreateEventInput!) {
        createEvent(input: \$input) {
          errors
          event { id }
        }
      }
      GQL,
      [
        'input' => [
          'title' => 'Event',
          'visibility' => 'PUBLIC',
          'body' => $this->minimalRichTextBody(),
          'startDate' => $start,
          'endDate' => $end,
          'organizations' => [
            'organization' => '00000000-0000-0000-0000-000000000000',
            'crosspostedOrganizations' => [],
          ],
        ],
      ],
      [
        // Require both: unknown-field semantics AND the organizations token.
        '#(?=.*(?:Unknown field|doesn\'t exist|not defined))(?=.*(?:/organizations|organizations))#is',
      ],
      $this->defaultMutationCacheMetaData()
        ->addCacheContexts(['languages:language_interface'])
    );
  }

}
