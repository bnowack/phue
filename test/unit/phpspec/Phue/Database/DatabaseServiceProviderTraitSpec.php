<?php

namespace phpspec\Phue\Database;

use PhpSpec\ObjectBehavior;
use Phue\Database\DatabaseServiceProviderTrait;

class DatabaseServiceProviderTraitSpec extends ObjectBehavior
{

    public function let()
    {
        $this->beAnInstanceOf(DatabaseServiceProviderTraitStub::class);
    }

    public function it_encodes_table_values()
    {
        $tableDefinitions = [
            'Test' => [
                'testInt' => 'int',
                'testString' => 'string',
                'testTrue' => 'bool',
                'testFalse' => 'bool',
                'testArray' => 'array',
                'testObject' => 'object',
                'testTypedObject' => DatabaseServiceProviderTraitTypedObjectStub::class
            ]
        ];
        $this->beConstructedWith($tableDefinitions);

        $instance = (object)[
            'testInt' => 10,
            'testString' => 'test',
            'testTrue' => true,
            'testFalse' => false,
            'testArray' => ['one', 'two'],
            'testObject' => (object)[
                'foo' => 'bar'
            ],
            'testTypedObject' => new DatabaseServiceProviderTraitTypedObjectStub([
                'foo' => 'bar'
            ])
        ];
        $this->encodeTableValues($instance, 'Test')->shouldBe([
            'testInt' => 10,
            'testString' => 'test',
            'testTrue' => 1,
            'testFalse' => 0,
            'testArray' => json_encode($instance->testArray),
            'testObject' => json_encode($instance->testObject),
            'testTypedObject' => json_encode($instance->testTypedObject)
        ]);
    }

    public function it_decodes_table_values()
    {
        $tableDefinitions = [
            'Test' => [
                'testInt' => 'int',
                'testString' => 'string',
                'testTrue' => 'bool',
                'testFalse' => 'bool',
                'testArray' => 'array',
                'testObject' => 'object',
                'testTypedObject' => DatabaseServiceProviderTraitTypedObjectStub::class
            ]
        ];
        $this->beConstructedWith($tableDefinitions);

        $row = [
            'testInt' => '10',
            'testString' => 'test',
            'testTrue' => '1',
            'testFalse' => '0',
            'testArray' => json_encode(['one', 'two']),
            'testObject' => json_encode([
                'foo' => 'bar'
            ]),
            'testTypedObject' => json_encode([
                'foo' => 'bar'
            ])
        ];

        $this->decodeTableValues($row, 'Test')['testInt']->shouldBe(10);
        $this->decodeTableValues($row, 'Test')['testString']->shouldBe('test');
        $this->decodeTableValues($row, 'Test')['testTrue']->shouldBe(true);
        $this->decodeTableValues($row, 'Test')['testFalse']->shouldBe(false);
        $this->decodeTableValues($row, 'Test')['testArray']->shouldBe(['one', 'two']);
        $this->decodeTableValues($row, 'Test')['testObject']->shouldBeLike((object)['foo' => 'bar']);
        $this->decodeTableValues($row, 'Test')['testTypedObject']->shouldhaveType(
            DatabaseServiceProviderTraitTypedObjectStub::class
        );
    }
}

class DatabaseServiceProviderTraitStub {

    use DatabaseServiceProviderTrait;

    public function __construct($tableDefinitions)
    {
        $this->tableDefinitions = $tableDefinitions;
    }
}

class DatabaseServiceProviderTraitTypedObjectStub {

    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }
}