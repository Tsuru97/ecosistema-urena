<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountAuthorization extends Model
{
    protected $fillable = [
        'quote_id',
        'requested_by',
        'authorized_by',
        'otp_code',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }
}
