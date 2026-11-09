<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostRead extends Model
{
    public $timestamps = false;

    protected $fillable = ['post_id', 'user_id', 'read_at'];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
