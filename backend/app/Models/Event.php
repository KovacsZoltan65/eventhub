<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Override;

class Event extends Model
{
    use HasFactory,
        LogsActivity;

    protected $fillable = [
        'organizer_id','title','description',
        'starts_at','location','capacity',
        'category','status'
    ];

    protected $casts = [
        'starts_at'  => 'immutable_datetime',
        'created_at' => 'immutable_datetime',
        'updated_at' => 'immutable_datetime',
    ];

    /*
     * ==============================================================
     * LOGOLÁS
     * ==============================================================
     */

    // Ha szeretnéd, hogy minden mezőt automatikusan naplózzon:
    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true; // Csak a változásokat naplózza
    protected static $logName = 'events';

    protected static $recordEvents = [
        'created',
        'updated',
        'deleted',
    ];

    public function getLogNameToUse(string $eventName = ''): string
    {
        return static::$logName ?? 'default';
    }

    /*
     * ==============================================================
     */

    public static function getTag(): string
    {
        return self::$logName;
    }

    public static function getToSelect()
    {
        return static::active()
            ->select(['id', 'name'])
            ->orderBy('name', 'asc')
            ->get()->toArray();
    }

    public function scopeActive($query)
    {
        return $query->where( 'active', '=', APP_ACTIVE);
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class,'organizer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getRemainingSeatsAttribute(): int
    {
        // ha a controller withSum-mal előkészítette, innen olvassuk:
        $confirmed = $this->getAttribute('confirmed_quantity');
        if ($confirmed === null) {
            $confirmed = (int) $this->bookings()->where('status','confirmed')->sum('quantity');
        }
        return max(0, (int)$this->capacity - (int)$confirmed);
    }
    
    #[Override]
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable();
    }

    public function getCreatedAtAttribute()
    {
        return date('Y-m-d H:i', strtotime($this->attributes['created_at']));
    }

    public function getUpdatedAtAttribute()
    {
        return date('Y-m-d H:i', strtotime($this->attributes['updated_at']));
    }
}
