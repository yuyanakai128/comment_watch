<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goods extends Model
{
    use HasFactory;

    protected $table = 'goods';

    protected $fillable = [
        'notification_id',
        'link',
        'ItemImage',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

}
