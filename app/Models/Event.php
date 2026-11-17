<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Event extends Model implements HasMedia
{
    use HasFactory,
        HasSlug,
        InteractsWithMedia;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(['name'])
            ->doNotGenerateSlugsOnUpdate()
            ->saveSlugsTo('slug');
    }

    public function detail()
    {
        return $this->hasOne(EventDetail::class, 'event_id', 'id');
    }

    public function visitors()
    {
        return $this->hasMany(Visitor::class, 'event_id', 'id');
    }

    /**
     * Scope a query to only include slug.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSlug($query, $slug)
    {
        return $query->where('slug', $slug);
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'url',
        'is_offline_event',
        'is_offline_event_only',
        'is_online_event',
        'is_online_event_only',
        'is_both_event',

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'session' => 'array',
        'hide' => 'boolean',
        'registration_counter' => 'integer',
    ];

    /**
     * Check if the event is offline only.
     */
    protected function getIsOfflineEventAttribute()
    {
        return in_array('offline', $this->session ?? []);
    }

    /**
     * Check if the event is online only.
     */
    protected function getIsOnlineEventAttribute()
    {
        return in_array('online', $this->session ?? []);
    }

    /**
     * Check if the event is offline only.
     */
    protected function getIsOfflineEventOnlyAttribute()
    {
        return in_array('offline', $this->session ?? []) && ! in_array('online', $this->session ?? []);
    }

    protected function getIsOnlineEventOnlyAttribute()
    {
        return in_array('online', $this->session ?? []) && ! in_array('offline', $this->session ?? []);
    }

    /**
     * Check if the event is both session.
     */
    protected function getIsBothEventAttribute()
    {
        return $this->is_offline_event && $this->is_online_event;
    }

    /**
     * Get the event's url.
     *
     * @return string
     */
    public function getUrlAttribute()
    {
        return $this->detail()->exists() ? route('event.show', $this->slug) : '#';
    }

    /**
     * Get the event start date.
     * Format the date to human readable.
     * 05 November 2021
     *
     * @return string
     */
    public function getStartDateFormattedAttribute($value)
    {
        $date = Carbon::parse($this->start_date);

        return $date->translatedFormat('d F Y');
    }

    /**
     * Get the event start date.
     * Format the date to human readable.
     * Monday, 05 November 2021
     *
     * @return string
     */
    public function getStartDateFullFormattedAttribute($value)
    {
        $date = Carbon::parse($this->start_date);

        return $date->translatedFormat('l, d F Y');
    }

    /**
     * Check if registration has ended.
     *
     * @return bool
     */
    public function isRegistrationEnded()
    {
        if (is_null($this->registration_end)) {
            return true;
        }

        $registrationEnd = Carbon::parse($this->registration_end);

        return now()->gt($registrationEnd);
    }

    /**
     * The event is ended.
     */
    public function isEnded()
    {
        $isDisabled = false;

        $now = now();
        // Set the end date
        $endDate = $this->start_date.' '.'20:00:00';

        // Parse the end date
        $endDate = \Carbon\Carbon::parse($endDate);

        // Check if the event is already ended / Now Greater than the end date
        if ($now->gt($endDate)) {
            $isDisabled = true;
        }

        return $isDisabled;
    }

    /**
     * The event has started.
     */
    public function hasStarted()
    {
        $hasStarted = false;

        $now = now();
        // Set the start date
        $startDate = \Carbon\Carbon::parse($this->registration_date);

        // Check if the event is already started / Now Greater than the start date
        if ($now->gte($startDate)) {
            $hasStarted = true;
        }

        return $hasStarted;
    }

    /**
     * Get incoming event where start_date is greater than now.
     */
    public function scopeIncoming($query)
    {
        return $query->whereDate('start_date', '>=', now()->format('Y-m-d'));
    }

    public function scopePast($query)
    {
        return $query->where('start_date', '<', now());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')
            ->useFallbackUrl(asset('img/banner/webbanner.jpg'))
            ->singleFile();
    }
}
