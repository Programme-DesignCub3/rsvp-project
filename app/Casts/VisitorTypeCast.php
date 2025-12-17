<?php

namespace App\Casts;

use App\Enums\VisitorType;

class VisitorTypeCast
{
    public function get($value)
    {
        if (is_null($value)) {
            return null;
        }

        return array_map(function ($item) {
            return VisitorType::from($item);
        }, $value);
    }

    public function set($value)
    {
        if (is_null($value)) {
            return null;
        }

        return array_map(function ($item) {
            return $item->value;
        }, $value);
    }
}
