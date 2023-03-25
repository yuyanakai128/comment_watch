<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $table = 'comments';

    protected $fillable = [
        'user_id',
        'goods_id',
        'comment',
        'link',
        'ItemImage',
    ];

    public function goods()
    {
        return $this->belongsTo(Goods::class);
    }

}
