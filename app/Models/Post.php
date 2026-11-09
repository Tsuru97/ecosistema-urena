<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Post extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'user_id', 'store_id', 'title', 'summary', 'body', 'gjs_data',
        'priority', 'type', 'pinned', 'published_at',
    ];

    protected $casts = [
        'pinned' => 'boolean',
        'published_at' => 'datetime',
    ];

    // ── Relaciones ──

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function reads()
    {
        return $this->hasMany(PostRead::class);
    }

    public function reactions()
    {
        return $this->hasMany(PostReaction::class);
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function comments()
    {
        return $this->hasMany(PostComment::class);
    }

    // ── Helpers ──

    public function isReadBy(?User $user = null): bool
    {
        $user = $user ?? auth()->user();
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function markAsRead(?User $user = null): void
    {
        $user = $user ?? auth()->user();
        $this->reads()->firstOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()]
        );
    }

    public function readPercentage(): int
    {
        $totalUsers = User::where('status', true)->count();
        if ($totalUsers === 0) return 0;
        return (int) round(($this->reads()->count() / $totalUsers) * 100);
    }

    // ── Scopes ──

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
                     ->where('published_at', '<=', now());
    }

    public function scopePinned($query)
    {
        return $query->where('pinned', true);
    }
}
