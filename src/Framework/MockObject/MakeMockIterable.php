<?php

namespace AKlump\Drupal\PHPUnit\Integration\Framework\MockObject;

/**
 * Given a mock object make it iterable using $data.
 *
 * @url https://stackoverflow.com/a/32422586
 *
 * @code
 * $mock = $this->createMock(\SomeClass::class);
 * (new MakeMockIterable())($mock, [
 *   'lorem',
 *   'ipsum',
 * ]);
 * $values = [];
 * foreach ($mock as $value) {
 *   $values[] = $value;
 * }
 * $this->assertSame('lorem', $values[0]);
 * $this->assertSame('ipsum', $values[1]);
 * @endcode
 */
class MakeMockIterable {

  public function __invoke(\PHPUnit\Framework\MockObject\MockObject $mock, array $data) {
    $iterator = new \ArrayIterator($data);
    $mock
      ->method('rewind')
      ->willReturnCallback(function () use ($iterator) {
        return $iterator->rewind();
      });
    $mock
      ->method('current')
      ->willReturnCallback(function () use ($iterator) {
        return $iterator->current();
      });
    $mock
      ->method('key')
      ->willReturnCallback(function () use ($iterator) {
        return $iterator->key();
      });
    $mock
      ->method('next')
      ->willReturnCallback(function () use ($iterator) {
        return $iterator->next();
      });
    $mock
      ->method('valid')
      ->willReturnCallback(function () use ($iterator) {
        return $iterator->valid();
      });
  }

}
