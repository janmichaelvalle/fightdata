<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Set extends Model
{
    use HasFactory;

    protected $fillable = [
        'p1_polaris_id',
        'p2_polaris_id',
        'p1_chara_id',
        'p2_chara_id',
        'match1_id',
        'match2_id',
        'match3_id',
        'set_start',
        'set_winner',
    ];
}
