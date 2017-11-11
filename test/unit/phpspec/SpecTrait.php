<?php

namespace phpspec;

/**
 * Trait for PHP specs
 */
trait SpecTrait
{
    public function fixturesPath()
    {
        return PHUE_APP_DIR . 'test/fixtures/';
    }
}
