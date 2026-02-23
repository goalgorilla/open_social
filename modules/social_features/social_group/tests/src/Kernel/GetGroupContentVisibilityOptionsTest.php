<?php

namespace Drupal\Tests\social_group\Kernel;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the functions for allowed group visibility.
 *
 * Covers social_group_is_content_visibility_allowed_for_groups and
 * social_group_get_allowed_content_visibility_options_for_multiple_groups.
 *
 * This class makes quite specific use of mocking for the implementation, given
 * that we can't easily inject services into the methods. The main idea is that
 * we keep the business logic stable while allowing refactoring.
 *
 * @group social_group
 */
class GetGroupContentVisibilityOptionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'flag',
    'flexible_permissions',
    'group',
    'social_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
  }

  /**
   * Test the intersection business logic for group content visibility.
   *
   * @param list<list<string>> $group_visibilities
   *   A list of lists with allowed content visibilities ('public', 'community',
   *   and/or 'group'). Each list will be turned into a single group to test
   *   with.
   * @param array{public: bool, community: bool, group: bool} $expected
   *   The array of expected visibility values.
   *
   * @dataProvider provideVisibilityTestCases
   */
  public function testAllowedVisibilitiesAreIntersection(array $group_visibilities, array $expected) : void {
    $groups = array_map(
      [$this, "mockGroupWithVisibilities"],
      $group_visibilities,
    );

    $this->assertEquals(
      $expected,
      social_group_get_allowed_content_visibility_options_for_multiple_groups($groups)
    );
  }

  /**
   * Test data for testAllowedVisibilitiesAreIntersection().
   *
   * @return \Generator<string, array{list<list<string>>, array{public: bool, community: bool, group: bool}}>
   *   The test data.
   */
  public function provideVisibilityTestCases() : \Generator {
    yield "single group matches itself" => [
      [
        ['public', 'group'],
      ],
      ['public' => TRUE, 'community' => FALSE, 'group' => TRUE],
    ];

    yield "two groups matches only shared" => [
      [
        ['community', 'group'],
        ['public', 'community'],
      ],
      ['public' => FALSE, 'community' => TRUE, 'group' => FALSE],
    ];

    yield "three groups without shared disallow everything" => [
      [
        ['community', 'group'],
        ['public', 'community'],
        ['public', 'group'],
      ],
      ['public' => FALSE, 'community' => FALSE, 'group' => FALSE],
    ];

    yield "all groups allowing everything" => [
      [
        ['public', 'community', 'group'],
        ['public', 'community', 'group'],
        ['public', 'community', 'group'],
        ['public', 'community', 'group'],
      ],
      ['public' => TRUE, 'community' => TRUE, 'group' => TRUE],
    ];
  }

  /**
   * Test that the method properly checks the entity's visibility.
   *
   * @param list<list<string>> $group_visibilities
   *   A list of lists with allowed content visibilities ('public', 'community',
   *   and/or 'group'). Each list will be turned into a single group to test
   *   with.
   * @param string $visibility
   *   The visibility of the content to test.
   * @param bool $expected
   *   Whether we expect the visibility to be allowed or not.
   *
   * @dataProvider provideContentVisibilityTestCases
   */
  public function testIsContentVisibilityAllowedForGroups(array $group_visibilities, string $visibility, bool $expected) : void {
    $groups = array_map(
      [$this, "mockGroupWithVisibilities"],
      $group_visibilities,
    );
    $entity = $this->mockContentWithVisibility($visibility);

    $this->assertEquals(
      $expected,
      social_group_is_content_visibility_allowed_for_groups($groups, $entity)
    );
  }

  /**
   * Test data for testIsContentVisibilityAllowedForGroups().
   *
   * @return \Generator<string, array{list<list<string>>, string, bool}>
   *   The test cases.
   */
  public function provideContentVisibilityTestCases() : \Generator {
    yield "visibility is allowed in single group" => [
      [
        ['public', 'group'],
      ],
      'public',
      TRUE,
    ];

    yield "visibility is not allowed in single group" => [
      [
        ['public', 'group'],
      ],
      'community',
      FALSE,
    ];

    yield "two groups sharing only community disallow public" => [
      [
        ['community', 'group'],
        ['public', 'community'],
      ],
      'public',
      FALSE,
    ];

    yield "two groups sharing only community allow community" => [
      [
        ['community', 'group'],
        ['public', 'community'],
      ],
      'community',
      TRUE,
    ];

    yield "two groups sharing only community disallow group" => [
      [
        ['community', 'group'],
        ['public', 'community'],
      ],
      'group',
      FALSE,
    ];

    yield "unknown visibility is disallowed" => [
      [],
      'new',
      FALSE,
    ];
  }

  /**
   * Mock a group acting as if it has certain allowed content visibilities.
   *
   * @param list<string> $group_visibility
   *   A list of allowed content visibilities ('public', 'community', and/or
   *   'group').
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   A mocked group.
   */
  private function mockGroupWithVisibilities(array $group_visibility) : GroupInterface {
    $fieldReturn = [];
    foreach ($group_visibility as $visibility) {
      $fieldReturn[] = ['value' => $visibility];
    }

    $fieldValueMock = $this->getMockBuilder(FieldItemList::class)
      ->onlyMethods(['getValue'])
      ->setConstructorArgs([DataDefinition::create('any')])
      ->getMock();

    $fieldValueMock->method('getValue')
      ->willReturn($fieldReturn);

    $groupTypeMock = $this->getMockBuilder(GroupType::class)
      ->onlyMethods(['id'])
      ->setConstructorArgs([[], 'group_type'])
      ->getMock();

    $groupTypeMock->method('id')->willReturn('flexible_group');

    $mock = $this->getMockBuilder(Group::class)
      ->onlyMethods(['hasField', 'get', 'getRevisionId', 'getGroupType'])
      ->setConstructorArgs([[], 'group'])
      ->getMock();

    $mock->method('getRevisionId')
      ->willReturn(0);

    $mock->method('getGroupType')
      ->willReturn($groupTypeMock);

    $mock->method('hasField')
      ->with('field_group_allowed_visibility')
      ->willReturn(TRUE);

    $mock->method('get')
      ->with('field_group_allowed_visibility')
      ->willReturn($fieldValueMock);

    return $mock;
  }

  /**
   * Mock a content entity with a certain visibility.
   *
   * @param string $visibility
   *   The 'field_content_visibility' value.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The mocked entity.
   */
  private function mockContentWithVisibility(string $visibility) : FieldableEntityInterface {
    $reflection = new \ReflectionClass(FieldableEntityInterface::class);

    $methods = [];
    foreach ($reflection->getMethods() as $method) {
      $methods[] = $method->name;
    }

    $mock = $this->getMockBuilder(FieldableEntityInterface::class)
      ->onlyMethods($methods)
      ->getMock();

    $mock->method('hasField')
      ->with('field_content_visibility')

      ->willReturn(TRUE);
    $mock->method('get')
      ->with('field_content_visibility')
      ->willReturn((object) ['value' => $visibility]);

    return $mock;
  }

}
