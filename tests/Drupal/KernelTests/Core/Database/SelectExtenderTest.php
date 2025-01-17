<?php

namespace Drupal\KernelTests\Core\Database;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Database\Query\SelectExtender;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\Core\Database\Stub\StubConnection;
use Drupal\Tests\Core\Database\Stub\StubPDO;

/**
 * Tests the Select query extender classes.
 *
 * @coversDefaultClass \Drupal\Core\Database\Query\Select
 * @group Database
 */
class SelectExtenderTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['database_test', 'search'];

  /**
   * Data provider for testExtendLegacy().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected namespaced class name.
   *   - The database driver namespace.
   *   - The namespaced class name for which to extend.
   */
  public function providerExtendLegacy(): array {
    return [
      [
        'Drupal\Core\Database\Query\PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefake',
        'Drupal\Core\Database\Query\PagerSelectExtender',
      ],
      [
        'Drupal\Core\Database\Query\PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefake',
        '\Drupal\Core\Database\Query\PagerSelectExtender',
      ],
      [
        'Drupal\Core\Database\Query\TableSortExtender',
        'Drupal\corefake\Driver\Database\corefake',
        'Drupal\Core\Database\Query\TableSortExtender',
      ],
      [
        'Drupal\Core\Database\Query\TableSortExtender',
        'Drupal\corefake\Driver\Database\corefake',
        '\Drupal\Core\Database\Query\TableSortExtender',
      ],
      [
        'Drupal\search\SearchQuery',
        'Drupal\corefake\Driver\Database\corefake',
        'Drupal\search\SearchQuery',
      ],
      [
        'Drupal\search\SearchQuery',
        'Drupal\corefake\Driver\Database\corefake',
        '\Drupal\search\SearchQuery',
      ],
      [
        'Drupal\search\ViewsSearchQuery',
        'Drupal\corefake\Driver\Database\corefake',
        'Drupal\search\ViewsSearchQuery',
      ],
      [
        'Drupal\search\ViewsSearchQuery',
        'Drupal\corefake\Driver\Database\corefake',
        '\Drupal\search\ViewsSearchQuery',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Drupal\Core\Database\Query\PagerSelectExtender',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        '\Drupal\Core\Database\Query\PagerSelectExtender',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\TableSortExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Drupal\Core\Database\Query\TableSortExtender',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\TableSortExtender',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        '\Drupal\Core\Database\Query\TableSortExtender',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\SearchQuery',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Drupal\search\SearchQuery',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\SearchQuery',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        '\Drupal\search\SearchQuery',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\ViewsSearchQuery',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        'Drupal\search\ViewsSearchQuery',
      ],
      [
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses\ViewsSearchQuery',
        'Drupal\corefake\Driver\Database\corefakeWithAllCustomClasses',
        '\Drupal\search\ViewsSearchQuery',
      ],
    ];
  }

  /**
   * @covers ::extend
   * @covers \Drupal\Core\Database\Query\SelectExtender::extend
   * @dataProvider providerExtendLegacy
   * @group legacy
   */
  public function testExtendLegacy(string $expected, string $namespace, string $extend): void {
    $this->expectDeprecation("Passing '%A' as a fully qualified class name to %A is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Pass the appropriate suffix for a 'select_extender_factory' service instead. See https://www.drupal.org/node/3218001", E_USER_DEPRECATED);
    $this->expectDeprecation("Passing '%A' as a fully qualified class name to %A is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Pass the appropriate suffix for a 'select_extender_factory' service instead. See https://www.drupal.org/node/3218001", E_USER_DEPRECATED);
    $this->expectDeprecation("Passing '%A' as a fully qualified class name to %A is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Pass the appropriate suffix for a 'select_extender_factory' service instead. See https://www.drupal.org/node/3218001", E_USER_DEPRECATED);

    $additional_class_loader = new ClassLoader();
    $additional_class_loader->addPsr4("Drupal\\corefake\\Driver\\Database\\corefake\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/module/corefake/src/Driver/Database/corefake");
    $additional_class_loader->addPsr4("Drupal\\corefake\\Driver\\Database\\corefakeWithAllCustomClasses\\", __DIR__ . "/../../../../../tests/fixtures/database_drivers/module/corefake/src/Driver/Database/corefakeWithAllCustomClasses");
    $additional_class_loader->register(TRUE);

    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, ['namespace' => $namespace]);

    // Tests the method \Drupal\Core\Database\Query\Select::extend().
    $select = $connection->select('test')->extend($extend);
    $this->assertEquals($expected, get_class($select));

    // Get an instance of the class \Drupal\Core\Database\Query\SelectExtender.
    $select_extender = $connection->select('test')->extend(SelectExtender::class);
    $this->assertEquals(SelectExtender::class, get_class($select_extender));

    // Tests the method \Drupal\Core\Database\Query\SelectExtender::extend().
    $select_extender_extended = $select_extender->extend($extend);
    $this->assertEquals($expected, get_class($select_extender_extended));
  }

  /**
   * Data provider for testExtend().
   *
   * @return array
   *   Array of arrays with the following elements:
   *   - Expected namespaced class name.
   *   - The database driver namespace.
   *   - The suffix of the select_extender_factory.[suffix] service.
   */
  public function providerExtend(): array {
    return [
      [
        'Drupal\Core\Database\Query\PagerSelectExtender',
        'Drupal\corefake\Driver\Database\corefake',
        'pager',
      ],
      [
        'Drupal\Core\Database\Query\TableSortExtender',
        'Drupal\corefake\Driver\Database\corefake',
        'table_sort',
      ],
      [
        'Drupal\search\SearchQuery',
        'Drupal\corefake\Driver\Database\corefake',
        'search_query',
      ],
      [
        'Drupal\search\ViewsSearchQuery',
        'Drupal\corefake\Driver\Database\corefake',
        'views_search_query',
      ],
    ];
  }

  /**
   * @covers ::extend
   * @covers \Drupal\Core\Database\Query\SelectExtender::extend
   * @dataProvider providerExtend
   */
  public function testExtend(string $expected, string $namespace, string $extend): void {
    $mock_pdo = $this->createMock(StubPDO::class);
    $connection = new StubConnection($mock_pdo, ['namespace' => $namespace]);

    // Tests the method \Drupal\Core\Database\Query\Select::extend().
    $select = $connection->select('test')->extend($extend);
    $this->assertInstanceOf($expected, $select);

    // Get an instance of the class \Drupal\Core\Database\Query\SelectExtender.
    $select_extender = $connection->select('test')->extend('test_extender');
    $this->assertInstanceOf(SelectExtender::class, $select_extender);

    // Tests the method \Drupal\Core\Database\Query\SelectExtender::extend().
    $select_extender_extended = $select_extender->extend($extend);
    $this->assertInstanceOf($expected, $select_extender_extended);
  }

}
