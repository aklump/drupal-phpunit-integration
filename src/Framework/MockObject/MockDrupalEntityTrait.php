<?php

namespace AKlump\Drupal\PHPUnit\Integration\Framework\MockObject;


use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityAdapter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\user\UserInterface;

trait MockDrupalEntityTrait {

  /**
   * Created a mocked UserInterface object
   *
   * @param array[] $fields
   *    Keyed by the field name. Each value is an array representing the field
   *    items.  Each of those arrays is an array representing the item data.
   *
   * @return (\Drupal\user\UserInterface&\PHPUnit\Framework\MockObject\MockObject)
   */
  public function createUserMock(array $fields = []) {
    return $this->createEntityMock('user', 'user', $fields, 'User', UserInterface::class);
  }

  /**
   * Create a mocked Drupal entity with fields.
   *
   * @param string $entity_type_id
   * @param string $bundle
   * @param array[] $fields
   *   Keyed by the field name. Each value is an array representing the field
   *   items.  Each of those arrays is an array representing the item data.
   * @param string $entity_type_label
   *   Optional, otherwise $entity_type_id will be used.
   *
   * @return (\Drupal\Core\Entity\EntityInterface&\PHPUnit\Framework\MockObject\MockObject)
   */
  public function createEntityMock(string $entity_type_id, string $bundle, array $fields = [], string $entity_type_label = '', string $class_to_mock = NULL) {
    $class_to_mock = $class_to_mock ?? FieldableEntityInterface::class;

    $mock_entity = $this->createConfiguredMock($class_to_mock, [
      'getEntityTypeId' => $entity_type_id,
      'bundle' => $bundle,
      'getEntityType' => $this->createConfiguredMock(EntityTypeInterface::class, [
        'getLabel' => $entity_type_label ?: $entity_type_id,
      ]),
    ]);

    foreach ($fields as $field_name => $field) {
      $fields[$field_name] = $this->createFieldItemListMock($field);
      $fields[$field_name]->method('getName')->willReturn($field_name);
      $fields[$field_name]->method('getEntity')->willReturn($mock_entity);
      $fields[$field_name]->method('getParent')->willReturn(
        $this->createConfiguredMock(EntityAdapter::class, [
          'getEntity' => $mock_entity,
        ])
      );

      $field_definition = $this->createMock(FieldDefinitionInterface::class);
      $field_definition->method('getName')->willReturn($field_name);
      $fields[$field_name]->method('getFieldDefinition')
        ->willReturn($field_definition);
    }

    // Make the field known to hasField.
    $mock_entity
      ->method('hasField')
      ->willReturnCallback(function (string $field_name) use ($fields) {
        return array_key_exists($field_name, $fields);
      });

    /**
     * Get a field item list by field name.
     *
     * @param string $field_name
     *
     * @return \Drupal\Core\Field\FieldItemListInterface
     */
    $get_field = function (string $field_name) use ($fields): FieldItemListInterface {
      return $fields[$field_name] ?? $this->createFieldItemListMock([]);
    };

    // Make the field iterable.
    $mock_entity
      ->method('get')
      ->willReturnCallback($get_field);
    foreach (array_keys($fields) as $field_name) {
      $mock_entity->{$field_name} = $get_field($field_name);
    }

    $mock_entity
      ->method('getFields')
      ->willReturn($fields);

    return $mock_entity;
  }

  /**
   * @param array[] $field_item_list_value
   *   An array of item value arrays, e.g. "[['value' => 'bar'],['value' => 'baz']]"
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *
   * IT'S USUALLY BETTER to do the following than to use this method directly,
   * that way you get a more complete mock having parent and field definition
   * methods available.
   *
   * @code
   * $node = $this->createEntityMock('node', 'foo', [
   *   'field_foo' => [
   *     ['value' => 'bar'],
   *     ['value' => 'baz'],
   *   ],
   * ]);
   * $item_list = $node->get('field_foo');
   * @endcode
   *
   * @see self::createEntityMock()
   */
  public function createFieldItemListMock(array $field_item_list_value) {
    $field_item_list = $this->createMock(FieldItemListInterface::class);
    $field_item_list->method('getValue')->willReturn($field_item_list_value);

    foreach ($field_item_list_value as $index => $field_item) {
      if (is_object($field_item)) {
        continue;
      }
      $field_item_list_value[$index] = $this->_getFieldItemMock($field_item);
      $field_item_list_value[$index]->method('getName')->willReturn($index);
      $field_item_list_value[$index]->method('getParent')
        ->willReturn($field_item_list);
    }
    $field_item_list->method('first')
      ->willReturnCallback(function () use ($field_item_list_value) {
        return $field_item_list_value[0];
      });

    $callback = function ($index) use ($field_item_list_value) {
      if (!is_numeric($index)) {
        if (empty($field_item_list_value[0])) {
          return NULL;
        }

        return $field_item_list_value[0]->{$index};
      }

      return $field_item_list_value[$index] ?? NULL;
    };
    $field_item_list->method('__get')->willReturnCallback($callback);
    $field_item_list->method('get')->willReturnCallback($callback);
    $field_item_list->method('count')
      ->willReturn(count($field_item_list_value));

    (new MakeMockIterable())($field_item_list, $field_item_list_value);

    return $field_item_list;
  }

  private function _getFieldItemMock(array $field_item_value) {
    $callback = function ($key) use ($field_item_value) {
      return $field_item_value[$key] ?? NULL;
    };
    $field_item = $this->createMock(FieldItemInterface::class);
    $field_item->method('getValue')->willReturn($field_item_value);
    $field_item->method('__get')->willReturnCallback($callback);
    $field_item->method('get')->willReturnCallback($callback);

    (new MakeMockIterable())($field_item, $field_item_value);

    return $field_item;
  }

}
