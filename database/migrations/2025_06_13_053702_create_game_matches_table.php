<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('game_matches', function (Blueprint $table) {
            $table->id();
            $table->string('battle_id');
            $table->bigInteger('battle_at');
            $table->integer('battle_type');
            $table->integer('game_version');
            $table->integer('stage_id');
            $table->tinyInteger('winner');

            $table->string('p1_name');
            $table->unsignedBigInteger('p1_user_id');
            $table->string('p1_polaris_id');
            $table->integer('p1_chara_id');
            $table->integer('p1_area_id')->nullable();
            $table->integer('p1_region_id')->nullable();
            $table->string('p1_lang')->nullable();
            $table->integer('p1_power');
            $table->integer('p1_rank');
            $table->integer('p1_rating_before')->nullable();
            $table->integer('p1_rating_change')->nullable();
            $table->integer('p1_rounds');

            $table->string('p2_name');
            $table->unsignedBigInteger('p2_user_id');
            $table->string('p2_polaris_id');
            $table->integer('p2_chara_id');
            $table->integer('p2_area_id')->nullable();
            $table->integer('p2_region_id')->nullable();
            $table->string('p2_lang')->nullable();
            $table->integer('p2_power');
            $table->integer('p2_rank');
            $table->integer('p2_rating_before')->nullable();
            $table->integer('p2_rating_change')->nullable();
            $table->integer('p2_rounds');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
