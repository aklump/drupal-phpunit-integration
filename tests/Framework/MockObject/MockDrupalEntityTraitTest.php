<?php

namespace AKlump\Drupal\PHPUnit\Integration\Framework\MockObject;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\NodeInterface;
use PHPUnit\Framework\TestCase;

final class MockDrupalEntityTraitTest extends TestCase {

  use MockDrupalEntityTrait;

  public function testGetTitle() {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->createEntityMock('node', 'page', [
      'title' => [
        0 => ['value' => 'Somewhere Over the Rainbow'],
      ],
    ], '', NodeInterface::class);
    $this->assertSame('Somewhere Over the Rainbow', $node->getTitle());
  }

  public function dataForTestHasFieldProvider() {
    $tests = [];
    $tests[] = [
      $this->createEntityMock('node', 'page', [
        'field_main' => [
          ['field_tag' => 'lorem'],
          ['field_tag' => 'ipsum'],
        ],
      ]),
    ];
    $tests[] = [
      $this->createEntityMock('node', 'page', [
        'field_main' => [],
      ]),
    ];

    return $tests;
  }

  /**
   * @dataProvider dataForTestHasFieldProvider
   */
  public function testHasField(\Drupal\Core\Entity\EntityInterface $mock_entity) {
    $this->assertTrue($mock_entity->hasField('field_main'));
    $this->assertFalse($mock_entity->hasField('field_secondary'));
  }

  public function testEntityTypeAndBundle() {
    $this->assertSame('node', $this->mockEntity->getEntityTypeId());
    $this->assertSame('page', $this->mockEntity->bundle());
    $this->assertInstanceOf(\Drupal\Core\Entity\EntityTypeInterface::class, $this->mockEntity->getEntityType());
  }

  public function testEntityTypeGetLabel() {
    $this->assertSame('Content', $this->mockEntity->getEntityType()
      ->getLabel());
    $mock = $this->createEntityMock('user', 'user', [], 'User');
    $this->assertSame('User', $mock->getEntityType()->getLabel());
  }

  public function testGetFields() {
    $fields = $this->mockEntity->getFields();
    $this->assertArrayHasKey('field_main', $fields);
    $this->assertSame($fields['field_main'], $this->mockEntity->field_main);
  }

  public function testFieldItemListGetName() {
    $this->assertSame('field_main', $this->mockEntity->get('field_main')
      ->getName());
  }

  public function testFieldItemsWithGet() {
    $field_item_list = $this->mockEntity->get('field_main');
    $this->assertInstanceOf(FieldItemListInterface::class, $field_item_list);
    $this->assertSame($field_item_list, $this->mockEntity->field_main);

    $field_item0 = $field_item_list->get(0);
    $this->assertInstanceOf(FieldItemInterface::class, $field_item0);
    $this->assertSame('lorem', $field_item0->get('field_tag'));

    $field_item1 = $field_item_list->get(1);
    $this->assertInstanceOf(FieldItemInterface::class, $field_item1);
    $this->assertSame('ipsum', $field_item1->get('field_tag'));
  }

  public function testFieldItem() {
    $field_item_list = $this->mockEntity->get('field_main');
    $field_item = $field_item_list->get(0);
    $this->assertInstanceOf(FieldItemInterface::class, $field_item);
    $this->assertSame('lorem', $field_item->get('field_tag'));
    $this->assertSame($field_item->get('field_tag'), $field_item->field_tag);
  }

  public function testFieldItemListFirst() {
    $field_item_list = $this->mockEntity->get('field_first_name');
    $field_item = $field_item_list->first();
    $this->assertInstanceOf(FieldItemInterface::class, $field_item);
    $this->assertSame('Charlie', $field_item->get('value'));
    $this->assertSame($field_item->get('value'), $field_item->value);
  }

  public function testFieldItemGetParentGetEntity() {
    $field_item_list = $this->mockEntity->get('field_main');
    $this->assertSame($this->mockEntity, $field_item_list->getParent()
      ->getEntity());
  }

  public function testFieldItemGetParent() {
    $field_item_list = $this->mockEntity->get('field_main');
    $this->assertSame($field_item_list, $field_item_list->get(0)->getParent());
  }

  public function testFieldValue() {
    $this->assertSame('Charlie', $this->mockEntity->field_first_name->value);
    $this->assertSame('Charlie', $this->mockEntity->get('field_first_name')->value);
  }

  public function testCanIterateOnFieldItemsList() {
    $count = 0;
    foreach ($this->mockEntity->get('field_main') as $item) {
      ++$count;
    }
    $this->assertSame(2, $count);

    $count = 0;
    foreach ($this->mockEntity->field_main as $item) {
      ++$count;
    }
    $this->assertSame(2, $count);
  }

  protected function setUp(): void {
    $this->mockEntity = $this->createEntityMock('node', 'page', [
      'field_first_name' => [
        ['value' => 'Charlie'],
        ['value' => 'Susan'],
      ],
      'field_main' => [
        ['field_tag' => 'lorem'],
        ['field_tag' => 'ipsum'],
      ],
    ], 'Content');
  }

}
