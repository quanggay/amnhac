<?php

namespace App\Modules\Group\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupBlog extends Model
{
    use HasFactory;

    protected $fillable = ['group_id', 'user_id'];

    public function group()
    {
        return $this->belongsToMany(Group::class, 'groups');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}