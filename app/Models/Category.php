<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $table = 'categories';

    protected $fillable = [
        'name',
        'level',
        'displayOrder',
        'tabOrder',
        'parentId',
    ];

    public function notification()
    {
        return $this->belongsTo(Notification::class);
    }

    public function parent()
    {
        return $this->hasOne(Category::class,'id','parentId');
    }

    public function childrens()
    {
        return $this->hasMany(Category::class,'parentId','id');
    }

    public function goods()
    {
        return $this->hasMany(Goods::class);
    }
}
