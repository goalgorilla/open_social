<?php

declare(strict_types=1);

namespace Drupal\Tests\social_profile\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\social_profile\GraphQL\ProfileAffiliatableTypeMapperInterface;
use Drupal\social_profile\GraphQL\ProfileAffiliatableTypeResolver;
use Drupal\social_profile\GraphQL\ProfileExternalAffiliationProfileAffiliatableTypeMapper;

/**
 * Tests ProfileAffiliatable type mapping.
 *
 * @group social_profile
 */
class ProfileAffiliatableTypeResolverTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'profile',
    'entity',
    'flexible_permissions',
    'group',
    'file',
    'image',
    'address',
    'telephone',
    'taxonomy',
    'field_group',
    'crop',
    'image_widget_crop',
    'hux',
    'paragraphs',
    'entity_reference_revisions',
    'field',
    'text',
    'options',
    'smart_trim',
    'social_profile',
    'config_update',
    'update_helper',
    'config_modify',
    'social_profile_fields',
  ];

  /**
   * Tagged mappers resolve other_affiliations paragraphs.
   */
  public function testCollectorResolvesExternalAffiliationParagraph(): void {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('bundle')->willReturn('other_affiliations');

    $resolver = $this->container->get('social_profile.profile_affiliatable_type_resolver');
    $this->assertSame('ExternalProfileAffiliation', $resolver($paragraph));
  }

  /**
   * The first matching mapper wins.
   */
  public function testFirstMatchingMapperWins(): void {
    $resolver = new ProfileAffiliatableTypeResolver([
      new class implements ProfileAffiliatableTypeMapperInterface {

        /**
         * {@inheritdoc}
         */
        public function resolve(mixed $object): ?string {
          return $object === 'value' ? 'FirstType' : NULL;
        }

      },
      new class implements ProfileAffiliatableTypeMapperInterface {

        /**
         * {@inheritdoc}
         */
        public function resolve(mixed $object): ?string {
          return $object === 'value' ? 'SecondType' : NULL;
        }

      },
    ]);

    $this->assertSame('FirstType', $resolver->resolveType('value'));
    $this->assertSame('FirstType', $resolver('value'));
  }

  /**
   * Unmapped values are omitted by resolveType().
   */
  public function testCollectorReturnsNullForUnknownValue(): void {
    $resolver = $this->container->get('social_profile.profile_affiliatable_type_resolver');
    $this->assertNull($resolver->resolveType(new \stdClass()));
  }

  /**
   * The GraphQL type resolver still rejects values no mapper handles.
   */
  public function testCollectorThrowsForUnknownValue(): void {
    $resolver = $this->container->get('social_profile.profile_affiliatable_type_resolver');

    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Can not map instance of 'stdClass' to ProfileAffiliatable.");
    $resolver(new \stdClass());
  }

  /**
   * The external-affiliation mapper ignores other paragraph bundles.
   */
  public function testExternalAffiliationMapperIgnoresOtherBundles(): void {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('bundle')->willReturn('text');

    $this->assertNull((new ProfileExternalAffiliationProfileAffiliatableTypeMapper())->resolve($paragraph));
  }

  /**
   * The paragraph mapper is collected from the tagged iterator.
   */
  public function testExternalAffiliationMapperIsTaggedInContainer(): void {
    $tagged = $this->container->findTaggedServiceIds('social_profile.profile_affiliatable_type');
    $this->assertArrayHasKey('social_profile.profile_external_affiliation_type_mapper', $tagged);
  }

}
