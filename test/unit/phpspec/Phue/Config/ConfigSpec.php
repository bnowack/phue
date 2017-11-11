<?php

namespace phpspec\Phue\Config;

use Phue\Config\Config;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use phpspec\SpecTrait;
use Phue\Exception\InvalidJsonException;

class ConfigSpec extends ObjectBehavior
{
    use SpecTrait;

    public function it_is_initializable()
    {
        $this->shouldHaveType(Config::class);
    }

    public function it_sets_and_gets_an_option()
    {
        $this->set('foo', 'bar')->shouldReturn($this);
        $this->get('foo')->shouldReturn('bar');
        $this->get('baz')->shouldReturn(null);
    }

    public function it_returns_a_default_value()
    {
        $this->get('undefined', 'test')->shouldReturn('test');
    }

    public function it_replaces_placeholders()
    {
        $this->set('var1', 'test');
        $this->set('var2', '{{var1}}');
        $this->get('var2')->shouldReturn('test');
    }

    public function it_replaces_constants()
    {
        define('CONFIG_TEST', 'test');

        $this->set('var1', '{CONFIG_TEST}');
        $this->get('var1')->shouldReturn('test');
    }

    public function it_merges_options()
    {
        $this->set('foo', ['bar']);
        $this->merge('foo', ['baz']);
        $this->get('foo')->shouldReturn(['bar', 'baz']);
    }

    public function it_loads_options_from_json()
    {
        $this->loadFile($this->fixturesPath() . 'Config/config-1.json')->shouldReturn($this);
        $this->get('foo')->shouldReturn('bar');
        $this->get('baz')->shouldReturn(null);
    }

    public function it_loads_options_as_object()
    {
        $this->loadFile($this->fixturesPath() . 'Config/config-1.json')->shouldReturn($this);
        $this->get('object')->shouldHaveType('\stdClass');
        $this->get('object')->foo->shouldReturn('bar');
    }

    public function it_ignores_non_existing_config_files()
    {
        $this->loadFile($this->fixturesPath() . 'does-not-exist.json')->shouldReturn($this);
    }

    public function it_reports_erroneous_config_files()
    {
        $this->shouldThrow(InvalidJsonException::class)
            ->during('loadFile', [$this->fixturesPath() . 'Config/invalid-json.txt']);
    }

    public function it_combines_loaded_config_options()
    {
        $mergeFields = ['object'];
        $this->loadFile($this->fixturesPath() . 'Config/config-1.json');
        $this->loadFile($this->fixturesPath() . 'Config/config-2.json', $mergeFields);
        $this->get('foo')->shouldReturn('bar2');
        $this->get('bar')->shouldReturn('baz2');
        $this->get('object')->foo->shouldReturn('bar');// from config 1
        $this->get('object')->bar->shouldReturn('baz2');// from config 2
    }
}
