<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Character extends Model
{
    protected $primaryKey = 'chara_id';

    protected $fillable = ['name'];
}