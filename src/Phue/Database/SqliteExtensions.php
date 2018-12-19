<?php

namespace Phue\Database;

class SqliteExtensions
{
    public static function inList($listString, $value)
    {
        $list = explode(',', $listString);
        $values = explode(',', $value);
        return array_intersect($list, $values) ? true : false;
    }
}
