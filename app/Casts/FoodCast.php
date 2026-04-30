<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class FoodCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * If the value is a string that starts with a '[' or a '{', we assume it's a JSON string
     * and we decode it. Otherwise, we just return the value as is.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        // If the value is a string that starts with a '[' or a '{', we assume it's a JSON string
        if (is_string($value) && (strpos($value, '[') === 0 || strpos($value, '{') === 0)) {
            // Decode the JSON string
            return json_decode($value, true);
        }

        // Otherwise, just return the value as is
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }
}
