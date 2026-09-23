<?php

declare(strict_types=1);

namespace Drupal\Tests\social_album\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\pager\SqlBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Tests the album overviews of the albums view.
 *
 * An album can be cross-posted to several groups. The group query access adds a
 * join on the group relationships of the content to any view that lists nodes,
 * which returns a cross-posted album once per group it belongs to. The
 * overviews aggregate their results so every album is listed, counted and
 * paged once.
 *
 * @group social_album
 */
class AlbumsViewTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity',
    'flexible_permissions',
    'gnode',
    'group',
    'node',
    'options',
    'views',
  ];

  /**
   * The album that is published in every group of the test.
   */
  protected NodeInterface $crossPostedAlbum;

  /**
   * The group the albums are listed for.
   */
  protected GroupInterface $group;

  /**
   * The albums of the group, including the cross-posted one.
   *
   * @var \Drupal\node\NodeInterface[]
   */
  protected array $albums = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['node']);

    NodeType::create(['type' => 'album', 'name' => 'Album'])->save();

    $group_type = $this->createGroupType(['id' => 'example']);
    $relationship_type_storage = $this->entityTypeManager->getStorage('group_content_type');
    assert($relationship_type_storage instanceof GroupRelationshipTypeStorageInterface);
    $relationship_type_storage->createFromPlugin($group_type, 'group_node:album')->save();

    // Everybody may see the albums of the groups, so the group query access
    // does not hide them from the overviews.
    foreach (['outsider', 'insider'] as $scope) {
      $this->createGroupRole([
        'id' => 'example-' . $scope,
        'group_type' => 'example',
        'scope' => $scope,
        'global_role' => 'authenticated',
        'permissions' => ['view group_node:album entity'],
      ]);
    }

    $this->group = $this->createGroup(['type' => 'example', 'label' => 'Albums group']);

    foreach (['First album', 'Second album', 'Third album', 'Cross-posted album'] as $title) {
      $album = $this->createAlbum($title);
      $this->group->addRelationship($album, 'group_node:album');
      $this->albums[] = $album;
    }

    // The last album is also published in two other groups, so the group query
    // access returns it three times.
    $this->crossPostedAlbum = end($this->albums);
    foreach (['Second group', 'Third group'] as $label) {
      $this->createGroup(['type' => 'example', 'label' => $label])
        ->addRelationship($this->crossPostedAlbum, 'group_node:album');
    }

    $this->installAlbumsView();
  }

  /**
   * Tests that the group overview lists and counts every album once.
   */
  public function testGroupAlbumsAreListedOnce(): void {
    $view = $this->executeOverview('page_group_albums_overview', [$this->group->id()]);

    $this->assertAlbumsAreListedOnce($view);
  }

  /**
   * Tests that the group overview pages the albums instead of their groups.
   *
   * The pager of an unaggregated overview counts the cross-posted album once
   * per group it belongs to, which adds pages without any albums on them.
   */
  public function testGroupAlbumsArePagedOnce(): void {
    $view = $this->executeOverview('page_group_albums_overview', [$this->group->id()], 4);

    $this->assertCount(count($this->albums), $view->result);
    $this->assertSame(count($this->albums), (int) $view->pager->getTotalItems());
    $pager = $view->pager;
    if (!$pager instanceof SqlBase) {
      $this->fail('The albums overview uses a SQL pager.');
    }
    else {
      $this->assertSame(1, (int) $pager->getPagerTotal());
    }
  }

  /**
   * Tests that the overview of a user lists and counts every album once.
   */
  public function testUserAlbumsAreListedOnce(): void {
    $view = $this->executeOverview('page_albums_overview', [$this->getCurrentUser()->id()]);

    $this->assertAlbumsAreListedOnce($view);
  }

  /**
   * Asserts that the view lists and counts every album of the test once.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The executed view.
   */
  protected function assertAlbumsAreListedOnce(ViewExecutable $view): void {
    $listed = [];
    foreach ($view->result as $row) {
      $listed[] = (int) $row->_entity->id();
    }

    $expected = [];
    foreach ($this->albums as $album) {
      $expected[] = (int) $album->id();
    }

    sort($listed);
    sort($expected);

    $this->assertSame($expected, $listed);
    $this->assertSame(count($expected), (int) $view->total_rows);
  }

  /**
   * Executes an overview of the albums view.
   *
   * @param string $display_id
   *   The display to execute.
   * @param array $arguments
   *   The contextual arguments of the display.
   * @param int|null $items_per_page
   *   (optional) The number of items to show per page. Defaults to NULL, which
   *   shows every album on a single page.
   *
   * @return \Drupal\views\ViewExecutable
   *   The executed view.
   */
  protected function executeOverview(string $display_id, array $arguments, ?int $items_per_page = NULL): ViewExecutable {
    $view = Views::getView('albums');
    $this->assertNotNull($view);
    $view->setDisplay($display_id);
    $view->setArguments($arguments);
    $view->setItemsPerPage($items_per_page ?? 0);
    $view->execute();

    return $view;
  }

  /**
   * Creates a published album.
   *
   * @param string $title
   *   The title of the album.
   *
   * @return \Drupal\node\NodeInterface
   *   The created album.
   */
  protected function createAlbum(string $title): NodeInterface {
    $album = Node::create([
      'type' => 'album',
      'title' => $title,
      'status' => NodeInterface::PUBLISHED,
      'uid' => $this->getCurrentUser()->id(),
    ]);
    $album->save();

    return $album;
  }

  /**
   * Installs the albums view that social_album ships.
   *
   * The module itself is a feature that can not be enabled in a kernel test,
   * so its view is installed from the configuration it ships. The displays
   * that embed the images of an album are left out because they list posts,
   * and the access of the overviews is opened up because both of their access
   * plugins live in the feature as well.
   */
  protected function installAlbumsView(): void {
    $path = $this->container->get('extension.list.module')->getPath('social_album');
    $configuration = Yaml::decode((string) file_get_contents($path . '/config/install/views.view.albums.yml'));

    $displays = ['default', 'page_albums_overview', 'page_group_albums_overview'];
    $configuration['display'] = array_intersect_key($configuration['display'], array_flip($displays));
    $configuration['dependencies'] = [];

    foreach (['page_albums_overview', 'page_group_albums_overview'] as $display_id) {
      $configuration['display'][$display_id]['display_options']['access'] = [
        'type' => 'none',
        'options' => [],
      ];
      // The shipped displays carry a form_blocks option that no module
      // provides a configuration schema for.
      unset($configuration['display'][$display_id]['display_options']['form_blocks']);
    }

    View::create($configuration)->save();
  }

}
