<?php

namespace App\Models;

use App\Casts\FoodCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Visitor extends Model implements HasMedia
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
     * @var array<string, string>
     */
    protected $casts = [
        'meta' => 'array',
        'is_offline' => 'boolean',
        'is_online' => 'boolean',
        'food' => FoodCast::class,
        'registration_sequence' => 'integer',
        'payment_base_amount' => 'integer',
        'payment_amount' => 'integer',
        'payment_verified' => 'boolean',
        'payment_verified_at' => 'datetime',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('payment_proof')
            ->singleFile();
    }

    /**
     * Get the event that owns the visitor.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function paymentVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payment_verified_by');
    }

    public function getPaymentUrlAttribute(): ?string
    {
        if ($this->registration_sequence === null || $this->event === null) {
            return null;
        }

        return URL::signedRoute('event.payment', [
            'event' => $this->event->slug,
            'visitor' => $this->id,
        ]);
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['package', 'payment_path_url'];

    public function getPackageAttribute(): ?string
    {
        if (isset($this->meta['offline_food'])) {
            return $this->meta['offline_food'];
        }

        if (blank($this->food)) {
            return null;
        }

        if (is_array($this->food)) {
            return $this->foodPackageLabel($this->food);
        }

        return $this->food;
    }

    public function getPaymentPathUrlAttribute(): ?string
    {
        if (! $this->is_offline) {
            return null;
        }

        if (isset($this->meta['payment_path'])) {
            $path = $this->meta['payment_path'];

            return $path ? Storage::disk('payments')->url($path) : null;
        }

        return null;
    }

    /**
     * @param  array<int|string, mixed>  $food
     */
    protected function foodPackageLabel(array $food): ?string
    {
        $selectedItems = array_filter([
            $food['food'] ?? null,
            $food['drink'] ?? null,
        ]);

        if ($selectedItems !== []) {
            return implode(' + ', $selectedItems);
        }

        $flatItems = array_filter($food, fn (mixed $item): bool => is_string($item) && filled($item));

        return $flatItems === [] ? null : implode(', ', $flatItems);
    }
}
