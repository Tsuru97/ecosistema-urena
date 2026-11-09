<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSchedule extends Model
{
    protected $fillable = [
        'store_id', 'day_of_week', 'is_open',
        'open_time', 'close_time', 'break_start', 'break_end',
    ];

    protected $casts = [
        'is_open'    => 'boolean',
        'day_of_week' => 'integer',
    ];

    public const DAYS = [
        0 => 'Lunes',
        1 => 'Martes',
        2 => 'Miércoles',
        3 => 'Jueves',
        4 => 'Viernes',
        5 => 'Sábado',
        6 => 'Domingo',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getDayNameAttribute(): string
    {
        return self::DAYS[$this->day_of_week] ?? '';
    }
}
