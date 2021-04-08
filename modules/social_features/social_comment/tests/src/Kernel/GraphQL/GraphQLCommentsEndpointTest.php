<?php

namespace Drupal\Tests\social_comment\Kernel\GraphQL;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\Tests\social_graphql\Kernel\SocialGraphQLTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests the comments endpoint added to the Open Social schema by this module.
 *
 * This uses the GraphQLTestBase which extends KernelTestBase since this class
 * is interested in testing the implementation of the GraphQL schema that's a
 * part of this module. We're not interested in the HTTP functionality since
 * that is covered by the graphql module itself. Thus BrowserTestBase is not
 * needed.
 *
 * @group social_graphql
 */
class GraphQLCommentsEndpointTest extends SocialGraphQLTestBase {

  use CommentTestTrait;
  use NodeCreationTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'social_comment',
    'comment',
    'field',
    'filter',
    'node',
    'text',
    'user',
    'file',
  ];

  /**
   * The list of comments.
   *
   * @var \Drupal\comment\CommentInterface[]
   */
  private $comments = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('comment');
    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installConfig(['filter', 'comment']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);

    \Drupal::currentUser()->setAccount(User::load(1));

    FieldStorageConfig::create([
      'entity_type' => 'node',
      'type' => 'comment',
      'field_name' => 'comments',
      'settings' => [
        'comment_type' => 'comment',
      ],
    ])->save();

    FieldStorageConfig::create([
      'type' => 'text_long',
      'entity_type' => 'comment',
      'field_name' => 'field_comment_body',
    ])->save();

    FieldStorageConfig::create([
      'type' => 'file',
      'entity_type' => 'comment',
      'field_name' => 'field_files',
      'cardinality' => '-1',
    ])->save();

    NodeType::create(['type' => 'page'])->save();

    CommentType::create([
      'id' => 'comment',
      'label' => 'comment',
      'target_entity_type_id' => 'node',
    ])->save();

    FieldConfig::create([
      'field_name' => 'comments',
      'entity_type' => 'node',
      'bundle' => 'page',
      'label' => 'Comments',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_comment_body',
      'entity_type' => 'comment',
      'bundle' => 'comment',
      'label' => 'Comments',
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_files',
      'entity_type' => 'comment',
      'bundle' => 'comment',
      'label' => 'Attachments',
      'settings' => [
        'file_extensions' => 'txt pdf doc docx xls xlsx ppt pptx csv',
      ],
    ])->save();

    $this->addDefaultCommentField('node', 'page');
    $account = $this->createUser();

    $node_commented_by_account = $this->createNode([
      'type' => 'page',
      'title' => "commented by {$account->id()}",
    ]);

    for ($i = 0; $i < 10; ++$i) {
      $this->comments[] = $this->createComment($account, $node_commented_by_account);
    }
  }

  /**
   * Test the filter for the comments query.
   */
  public function testCommentsQueryFilter(): void {
    $this->assertEndpointSupportsPagination(
      'comments',
      array_map(static fn ($comment) => $comment->uuid(), $this->comments)
    );
  }

  /**
   * Ensure that the fields for the comment endpoint properly resolve.
   *
   * This test does not test the validity of the resolved data but merely that
   * the API contract is adhered to.
   */
  public function testCommentFieldsPresence() : void {
    $account = $this->createUser();
    $node_commented_by_account = $this->createNode([
      'title' => "commented by {$account->id()}",
    ]);

    $this->setCurrentUser(User::load(1));
    $test_comment = $this->createComment($account, $node_commented_by_account);

    $query = '
      query ($id: ID!) {
        comment(id: $id) {
          id
          body
          created {
            timestamp
          }
          attachments(first: 10) {
            nodes {
              id
              url
              filename
              filemime
              filesize
              created {
                timestamp
              }
            }
          }
        }
      }
    ';
    $expected_data = [
      'comment' => [
        'id' => $test_comment->uuid(),
        'body' => $test_comment->field_comment_body->value,
        'created' => [
          'timestamp' => $test_comment->getCreatedTime(),
        ],
      ],
    ];

    /** @var \Drupal\file\Plugin\Field\FieldType\FileFieldItemList $field_files */
    $field_files = $test_comment->field_files;
    /** @var \Drupal\file\Entity\File[] $files */
    $files = $field_files->referencedEntities();

    $metadata = $this->defaultCacheMetaData()
      ->setCacheMaxAge(0)
      ->addCacheableDependency($test_comment)
      // @todo It's unclear why this cache context is added.
      ->addCacheContexts(['languages:language_interface']);

    foreach ($files as $id => $file) {
      $metadata->addCacheableDependency($file);

      $expected_data['comment']['attachments']['nodes'][] = [
        'id' => $file->uuid(),
        'url' => $file->createFileUrl(FALSE),
        'filename' => $file->getFilename(),
        'filemime' => $file->filemime->value,
        'filesize' => $file->filesize->value,
        'created' => [
          'timestamp' => $file->getCreatedTime(),
        ],
      ];
    }

    $this->assertResults(
      $query,
      ['id' => $test_comment->uuid()],
      $expected_data,
      $metadata
    );
  }

  /**
   * Create the comment entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Account object to get notifications for.
   * @param \Drupal\node\NodeInterface $node_commented_by_account
   *   The node object.
   *
   * @return \Drupal\comment\CommentInterface
   *   Created comment entity.
   */
  private function createComment(AccountInterface $account, NodeInterface $node_commented_by_account) {
    $comment = Comment::create([
      'uid' => $account->id(),
      'entity_id' => $node_commented_by_account->id(),
      'entity_type' => 'node',
      'comment_type' => 'comment',
      'field_name' => 'comment',
      'field_comment_body' => $this->randomString(32),
      'field_files' => [$this->createFile()->id()],
    ]);

    $comment->save();

    return $comment;
  }

  /**
   * Creates and saves a test file.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A file entity.
   */
  protected function createFile() {
    // Create a new file entity.
    $file = File::create([
      'uid' => 1,
      'filename' => 'druplicon.txt',
      'uri' => 'public://druplicon.txt',
      'filemime' => 'text/plain',
      'created' => 1,
      'changed' => 1,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), 'hello world');

    // Save it, inserting a new record.
    $file->save();

    return $file;
  }

}
