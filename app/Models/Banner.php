<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Banner extends Model implements HasMedia, Sortable
{
    use InteractsWithMedia, SortableTrait;

    protected $casts = [
        'enable' => 'boolean',
        'has_mobile_asset' => 'boolean',
        'embed' => 'array', // or 'json'
    ];

    protected $fillable = [
        'title',
        'link',
        'type',
        'embed',
        'enable',
        'has_mobile_asset',
        'order_column',
    ];

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('banner_desktop')
            ->singleFile();

        $this
            ->addMediaCollection('banner_mobile')
            ->singleFile();
    }
}
