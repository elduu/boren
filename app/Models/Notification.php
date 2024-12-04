<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'notifiable_id',
        'notifiable_type',
        'data',
        'read_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array', // Automatically cast JSON data to an array
        'read_at' => 'datetime', // Automatically cast read_at to a datetime object
    ];

    /**
     * Relationship: Notification belongs to a notifiable entity.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function notifiable()
    {
        return $this->morphTo();
    }

    /**
     * Scope to filter unread notifications.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Mark the notification as read.
     *
     * @return bool
     */
    public function markAsRead()
    {
        $this->read_at = now();
        return $this->save();
    }

    /**
     * Mark the notification as unread.
     *
     * @return bool
     */
    public function markAsUnread()
    {
        $this->read_at = null;
        return $this->save();
    }
}