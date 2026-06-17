<?php

namespace App\Models;

use App\Casts\TimeCast;
use App\Enums\FoodType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class EventDetail extends Model implements HasMedia
{
    use HasFactory,
        InteractsWithMedia;

    public const DEFAULT_REGISTRATION_PRICE_TYPE = '__default__';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public function isFillable($key): bool
    {
        return $key === 'default_registration_fee' || parent::isFillable($key);
    }

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
        'default_registration_fee',
    ];

    /**
     * Return visitor-type overrides while hiding the internal default fee entry.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRegistrationPaymentPricesAttribute(mixed $value): array
    {
        return array_values(array_filter(
            $this->decodeRegistrationPaymentPrices($value),
            fn (array $price): bool => ($price['visitor_type'] ?? null) !== self::DEFAULT_REGISTRATION_PRICE_TYPE
        ));
    }

    /**
     * Store visitor-type overrides without losing the internal default fee entry.
     *
     * @param  array<int, array<string, mixed>>|string|null  $value
     */
    public function setRegistrationPaymentPricesAttribute(array|string|null $value): void
    {
        $prices = array_values(array_filter(
            $this->decodeRegistrationPaymentPrices($value),
            fn (array $price): bool => ($price['visitor_type'] ?? null) !== self::DEFAULT_REGISTRATION_PRICE_TYPE
        ));
        $defaultFee = $this->defaultRegistrationFeeFromRawPrices();

        if ($defaultFee !== null) {
            $prices[] = $this->defaultRegistrationFeeEntry($defaultFee);
        }

        $this->attributes['registration_payment_prices'] = json_encode($prices);
    }

    /**
     * Store food configuration without duplicate ala carte food or drink choices.
     *
     * @param  array<int, array<string, mixed>>|string|null  $value
     */
    public function setOfflineFoodsAttribute(array|string|null $value): void
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value)) {
            $this->attributes['offline_foods'] = null;

            return;
        }

        $this->attributes['offline_foods'] = json_encode(array_map(
            fn (mixed $foodGroup): mixed => is_array($foodGroup)
                ? $this->normalizeAlaCarteFoodGroup($foodGroup)
                : $foodGroup,
            $value
        ));
    }

    /**
     * Return the default registration fee stored inside registration payment prices.
     */
    protected function defaultRegistrationFee(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->defaultRegistrationFeeFromRawPrices(),
            set: function (mixed $value): array {
                $prices = array_values(array_filter(
                    $this->rawRegistrationPaymentPrices(),
                    fn (array $price): bool => ($price['visitor_type'] ?? null) !== self::DEFAULT_REGISTRATION_PRICE_TYPE
                ));

                if ($value !== null && $value !== '') {
                    $prices[] = $this->defaultRegistrationFeeEntry((int) $value);
                }

                return ['registration_payment_prices' => json_encode($prices)];
            }
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function rawRegistrationPaymentPrices(): array
    {
        return $this->decodeRegistrationPaymentPrices(
            $this->attributes['registration_payment_prices'] ?? null
        );
    }

    protected function defaultRegistrationFeeFromRawPrices(): ?int
    {
        foreach ($this->rawRegistrationPaymentPrices() as $price) {
            if (($price['visitor_type'] ?? null) === self::DEFAULT_REGISTRATION_PRICE_TYPE) {
                return isset($price['price']) ? (int) $price['price'] : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $foodGroup
     * @return array<string, mixed>
     */
    protected function normalizeAlaCarteFoodGroup(array $foodGroup): array
    {
        foreach (['food', 'drink'] as $key) {
            if (isset($foodGroup[$key]) && is_array($foodGroup[$key])) {
                $foodGroup[$key] = $this->uniqueAlaCarteOptions($foodGroup[$key]);
            }
        }

        return $foodGroup;
    }

    /**
     * @param  array<int, mixed>  $options
     * @return array<int, mixed>
     */
    protected function uniqueAlaCarteOptions(array $options): array
    {
        $uniqueOptions = [];

        foreach ($options as $option) {
            $name = $this->alaCarteOptionName($option);

            if ($name === null) {
                continue;
            }

            $uniqueOptions[mb_strtolower(trim($name))] = $option;
        }

        return array_values($uniqueOptions);
    }

    protected function alaCarteOptionName(mixed $option): ?string
    {
        if (is_string($option)) {
            return filled($option) ? $option : null;
        }

        if (! is_array($option)) {
            return null;
        }

        $name = $option['name'] ?? $option['food'] ?? $option['drink'] ?? null;

        return is_string($name) && filled($name) ? $name : null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function decodeRegistrationPaymentPrices(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decodedValue = json_decode($value, true);

        return is_array($decodedValue) ? $decodedValue : [];
    }

    /**
     * @return array{visitor_type: string, price: int, label: null}
     */
    protected function defaultRegistrationFeeEntry(int $fee): array
    {
        return [
            'visitor_type' => self::DEFAULT_REGISTRATION_PRICE_TYPE,
            'price' => $fee,
            'label' => null,
        ];
    }

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
