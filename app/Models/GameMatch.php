<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameMatch extends Model
{
    protected $table = 'game_matches';

    protected $fillable = [
        'battle_id',
        'battle_at',
        'battle_type',
        'game_version',
        'stage_id',
        'winner',
        'p1_name',
        'p1_user_id',
        'p1_polaris_id',
        'p1_chara_id',
        'p1_area_id',
        'p1_region_id',
        'p1_lang',
        'p1_power',
        'p1_rank',
        'p1_rating_before',
        'p1_rating_change',
        'p1_rounds',
        'p2_name',
        'p2_user_id',
        'p2_polaris_id',
        'p2_chara_id',
        'p2_area_id',
        'p2_region_id',
        'p2_lang',
        'p2_power',
        'p2_rank',
        'p2_rating_before',
        'p2_rating_change',
        'p2_rounds'
    ];
}
