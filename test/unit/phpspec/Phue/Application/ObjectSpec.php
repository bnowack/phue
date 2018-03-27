<?php

namespace phpspec\Phue\Application;

use Exception;
use PhpSpec\ObjectBehavior;
use PHPUnit\Framework\Assert;
use Phue\Application\ApplicationObject;

class ApplicationObjectSpec extends ObjectBehavior
{
    protected function buildObject($classDefinition, $data = null)
    {
        $className = 'Object' . uniqid();
        eval("class $className extends Phue\Application\ApplicationObject { $classDefinition }");
        return new $className($data);
    }

    public function it_is_initializable()
    {
        $this->beConstructedWith((object)[]);
        $this->shouldHaveType(ApplicationObject::class);
    }

    public function it_is_initializable_with_arbitrary_data()
    {
        $this->beConstructedWith((object)[
            'undefinedProperty' => 'bar'
        ]);
        $this->shouldHaveType(ApplicationObject::class);
    }

    public function it_imports_known_properties_from_constructor_data()
    {
        $classDefinition = '
            protected $foo;
        ';
        $data = ['foo' => 'bar'];
        $test = $this->buildObject($classDefinition, (object)$data);

        Assert::assertEquals('bar', $test->foo);
    }

    public function it_has_magic_getters_for_defined_properties()
    {
        $classDefinition = '
            protected $foo;
        ';
        $data = ['foo' => 'bar'];
        $test = $this->buildObject($classDefinition, (object)$data);

        Assert::assertEquals('bar', $test->foo);
        Assert::assertEquals('bar', $test->getFoo());
    }

    public function it_has_magic_setters_for_defined_properties()
    {
        $classDefinition = '
            protected $foo;
        ';
        $data = [];
        $test = $this->buildObject($classDefinition, (object)$data);

        Assert::assertEquals(null, $test->foo);
        $test->foo = 'bar';
        Assert::assertEquals('bar', $test->foo);
        $test->setFoo('baz');
        Assert::assertEquals('baz', $test->foo);
    }

    public function it_throws_an_exception_when_undefined_properties_get_accessed()
    {
        $this->beConstructedWith((object)[]);
        $this->shouldThrow(Exception::class)->during('setFoo', ['bar']);
        $this->shouldThrow(Exception::class)->during('getFoo');
        try {
            $this->foo;
            Assert::assertTrue(false);
        } catch (\Exception $exception) {
            Assert::assertTrue(true);
        }
    }

    public function it_throws_an_exception_when_undefined_methods_get_called()
    {
        $this->beConstructedWith((object)[]);
        $this->shouldThrow(Exception::class)->during('callUndefined');
    }

    public function it_prefers_defined_getters()
    {
        $classDefinition = '
            protected $foo;
            
            public function getFoo() {
                return strtoupper($this->foo);
            }
        ';
        $data = ['foo' => 'bar'];
        $test = $this->buildObject($classDefinition, (object)$data);

        Assert::assertEquals('BAR', $test->foo);
        Assert::assertEquals('BAR', $test->getFoo());
    }

    public function it_prefers_defined_setters()
    {
        $classDefinition = '
            protected $foo;

            public function setFoo($foo) {
                $this->foo = strtoupper($foo);
            }
        ';
        $data = [];
        $test = $this->buildObject($classDefinition, (object)$data);

        $test->foo = 'bar';
        Assert::assertEquals('BAR', $test->foo);

        $test->setFoo('baz');
        Assert::assertEquals('BAZ', $test->foo);
    }

    public function it_is_serializable_to_json()
    {
        $classDefinition = '
            protected $foo;
        ';
        $data = ["foo" => "bar"];
        $test = $this->buildObject($classDefinition, (object)$data);

        Assert::assertEquals('{"foo":"bar"}', json_encode($test));
    }

    public function it_does_not_serialize_hidden_properties_to_json()
    {
        $classDefinition = '
            protected $foo;
        ';
        $data = ["foo" => "bar", "hiddenProperties" => ["foo"]];
        $test = $this->buildObject($classDefinition, (object)$data);

        Assert::assertEquals('{}', json_encode($test));
    }

    public function it_serializes_referenced_objects_to_json()
    {
        $classDefinition1 = '
            protected $foo;
        ';
        $data1 = ["foo" => "bar"];
        $test1 = $this->buildObject($classDefinition1, (object)$data1);

        $classDefinition2 = '
            protected $ref;
        ';
        $data2 = ["ref" => $test1];
        $test2 = $this->buildObject($classDefinition2, (object)$data2);

        Assert::assertEquals('{"ref":{"foo":"bar"}}', json_encode($test2));
    }
}
