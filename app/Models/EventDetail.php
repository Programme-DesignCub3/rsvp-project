<?php

namespace App\Models;

use App\Casts\TimeCast;
use App\Enums\FoodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EventDetail extends Model implements HasMedia
{
    use HasFactory,
        InteractsWithMedia;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'offline_foods' => 'array',
        'online_visitor_type_list' => 'array',
        'offline_visitor_type_list' => 'array',
        'excluded_payment_list' => 'array',
        'registration_payment_prices' => 'array',
        'show_invoice_upload' => 'boolean',
        'food_type' => FoodType::class,
        // 'online_time' => TimeCast::class,
        // 'offline_time' => TimeCast::class
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'description',
        'clean_description',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the online time in no seconds format.
     *
     * @return string
     */
    public function getOnlineTimeNoSecondsAttribute()
    {
        $onlinetime = $this->online_time;

        // Make the time in no seconds format & add PM/AM
        $time = date('H:i', strtotime($onlinetime));

        return $time;
    }

    /**
     * Get the offline time in no seconds format.
     *
     * @return string
     */
    public function getOfflineTimeNoSecondsAttribute()
    {
        $offlinetime = $this->offline_time;
        $time = date('H:i', strtotime($offlinetime));

        return $time;
    }

    /**
     * Get the offline food price in currency format, with K or M suffix.
     */
    public function getOfflineFoodPriceCurrencyAttribute()
    {
        $price = $this->offline_food_price;

        if ($price >= 1000000) {
            $price = $price / 1000000;
            $price = number_format($price, 0).'M';
        } else {
            $price = $price / 1000;
            $price = number_format($price, 0).'K';
        }

        return $price;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('video')
            ->useFallbackUrl(asset('videos/BNI Video low.mp4'))
            ->singleFile();
    }

    /**
     * Get Event Description.
     * Only if the override_description_1 is not null
     * Then return the description_1
     */
    protected function getDescriptionAttribute()
    {

        if ($this->override_description_1) {
            return $this->description_1;
        }

        return 'You are invited to join our BNI Altitude & BNI Magnitude event. Register now!';
    }

    /**
     * Get clean html tags from the description attribute.
     */
    protected function getCleanDescriptionAttribute()
    {
        if ($this->override_description_1) {
            return strip_tags($this->description);
        }

        return 'You are invited to join our BNI Altitude & BNI Magnitude event. Register now!';
    }
}
