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
        Schema::create('sets', function (Blueprint $table) {
            $table->id();
            $table->string('p1_polaris_id');
            $table->string('p2_polaris_id');
            $table->integer('p1_char_id');
            $table->integer('p2_char_id');
            $table->string('match1_id', 64)->nullable();
            $table->string('match2_id', 64)->nullable();
            $table->string('match3_id', 64)->nullable();
            $table->bigInteger('set_start'); // battle_at of first match in set
            $table->tinyInteger('set_winner'); // 1 = p1 wins, 2 = p2 wins, 0 = incomplete
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sets');
    }
};
