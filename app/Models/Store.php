<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Cacheable;
use App\Traits\Auditable;

class Store extends Model
{
    use SoftDeletes, Cacheable, Auditable;

    protected $fillable = [
        'name', 'code', 'phone', 'address', 'city',
        'state', 'zip', 'lat', 'lng', 'logo', 'margin_percentage', 'status', 'is_warehouse',
    ];

    protected $casts = [
        'status' => 'boolean',
        'is_warehouse' => 'boolean',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
                    ->withPivot('is_default')
                    ->withTimestamps();
    }

    public function schedules()
    {
        return $this->hasMany(StoreSchedule::class)->orderBy('day_of_week');
    }

    public function cashShifts()
    {
        return $this->hasMany(CashShift::class);
    }
}
