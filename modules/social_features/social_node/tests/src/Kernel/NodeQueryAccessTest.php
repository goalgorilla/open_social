<?php

declare(strict_types=1);

namespace Drupal\Tests\social_node\Kernel;

/**
 * Testing query access to nodes with "public" and "community" visibilities.
 *
 * This class contains tests for access control when querying nodes.
 * It verifies node visibility and access for different user roles, ensuring
 * that users can only access content based on their assigned permissions.
 *
 * @group social_node
 * @group social_node_query_access
 */
class NodeQueryAccessTest extends NodeQueryAccessTestBase {

  /**
   * Tests node query access based on user permissions and content visibility.
   *
   *   This method verifies whether users with different roles and permissions
   *   have appropriate access to content nodes categorized
   *   by visibility settings such as "public", "community", or no visibility.
   *
   * @covers \Drupal\social_node\EventSubscriber\NodeQueryAccessAlterSubscriber::alterQueryAccess()
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testNodeAlterQueryAccess(): void {
    // Check access to content without appropriate permissions.
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertNotContains($this->publicNode->id(), $query_results, 'Anonymous does not have access to content with "public" visibility.');
    $this->assertNotContains($this->communityNode->id(), $query_results, 'Anonymous does not have access to content with "community" visibility.');
    $this->assertNotContains($this->nodeWithoutVisibility->id(), $query_results, 'Anonymous does not have access to content without visibility.');

    // Check access to "public" content.
    $this->setCurrentUser($this->publicVisibilityUser);
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertContains($this->publicNode->id(), $query_results, 'PublicUser has access to content with "public" visibility.');
    $this->assertNotContains($this->communityNode->id(), $query_results, 'PublicUser does not have access to content with "community" visibility.');
    $this->assertNotContains($this->nodeWithoutVisibility->id(), $query_results, 'PublicUser does not have access to content without visibility.');

    // Check access to "community" content.
    $this->setCurrentUser($this->communityVisibilityUser);
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertNotContains($this->publicNode->id(), $query_results, 'CommunityUser does not have access to content with "public" visibility.');
    $this->assertContains($this->communityNode->id(), $query_results, 'CommunityUser has access to content with "community" visibility.');
    $this->assertNotContains($this->nodeWithoutVisibility->id(), $query_results, 'CommunityUser does not have access to content without visibility.');

    // Check access to all content for site managers.
    $this->setCurrentUser($this->privilegedUser);
    $query_results = $this->entityTypeManager->getStorage('node')
      ->getQuery()
      ->accessCheck()
      ->execute();
    $this->assertContains($this->publicNode->id(), array_values($query_results), 'PrivilegedUser can see "public" content.');
    $this->assertContains($this->communityNode->id(), array_values($query_results), 'PrivilegedUser can see "community" content.');
    $this->assertContains($this->nodeWithoutVisibility->id(), array_values($query_results), 'PrivilegedUser can see content without visibility.');
  }

}
