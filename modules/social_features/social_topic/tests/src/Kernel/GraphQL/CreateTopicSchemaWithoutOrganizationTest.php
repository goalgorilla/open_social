<?php

declare(strict_types=1);

namespace Drupal\Tests\social_topic\Kernel\GraphQL;

use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\social_topic\Kernel\SocialTopicGraphQLKernelTestBase;

/**
 * Tests CreateTopicInput schema lacks organizations when module is disabled.
 *
 * When social_organization is not enabled, TopicSchemaExtension does not load
 * the organization schema extension, so CreateTopicInput has no "organizations"
 * field. A request including it gets a GraphQL validation error.
 *
 * @group social_topic
 */
class CreateTopicSchemaWithoutOrganizationTest extends SocialTopicGraphQLKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function defaultCacheContexts(): array {
    return [...parent::defaultCacheContexts(), 'languages:language_interface'];
  }

  /**
   * Minimal valid Rich Text JSON body.
   */
  private static function minimalRichTextBody(): array {
    return [
      'root' => [
        'type' => 'root',
        'version' => 1,
        'children' => [
          [
            'type' => 'paragraph',
            'version' => 1,
            'children' => [
              ['type' => 'text', 'version' => 1, 'text' => 'Hello'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Test: Schema has no organizations field when social_organization disabled.
   *
   * When organizations is sent, GraphQL rejects it as unknown field.
   */
  public function testCreateTopicInputHasNoOrganizationsFieldWhenModuleDisabled(): void {
    $topicType = Term::create(['vid' => 'topic_types', 'name' => 'Article']);
    $topicType->save();

    $this->actAsClientCredentialsWithScopes(['topic:write']);
    $this->assertErrors(
      <<<GQL
      mutation CreateTopic(\$input: CreateTopicInput!) {
        createTopic(input: \$input) {
          errors
          topic { id }
        }
      }
      GQL,
      [
        'input' => [
          'type' => $topicType->uuid(),
          'title' => 'Topic',
          'visibility' => 'PUBLIC',
          'body' => self::minimalRichTextBody(),
          'organizations' => [
            'organization' => '00000000-0000-0000-0000-000000000000',
          ],
        ],
      ],
      [
        // Require both: unknown-field semantics AND the organizations token.
        '#(?=.*(?:Unknown field|doesn\'t exist|not defined))(?=.*(?:/organizations|organizations))#is',
      ],
      $this->defaultMutationCacheMetaData()
    );
  }

}
