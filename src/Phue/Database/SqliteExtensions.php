<?php

namespace Phue\Database;


class SqliteExtensions
{
    public static function inList($listString, $value)
    {
        $list = explode(',', $listString);
        return in_array($value, $list);
    }
}
