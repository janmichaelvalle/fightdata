<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Set;


class SimplePlayerController extends Controller
{
   public function getPlayerSets($polarisId){
    $sets = Set::where(function ($query) use ($polarisId) {
        $query->where('p1_polaris_id', $polarisId)
              ->orWhere('p2_polaris_id', $polarisId);
    })
    ->whereIn('set_winner', [1, 2])
    ->orderBy('set_start', 'DESC')
    ->take(100)
    ->get();
   //  dd($sets->toArray());

   $winRate = $this->calculateWinRate($sets, $polarisId);

   return view('set.show', compact('sets', 'winRate'));
    # compact('sets') - function that takes the string 'sets' and creates an array:
   }

   /** Calculate the player's win rate over the last 100 sets.
      * Logic:
      * 1. Get the last 100 sets for the player.
      * 2. If set_winner is 1 and p1 matches player => win; if set_winner is 2 and p2 matches player => win.
      * 3. Calculate win rate as (number of wins / total sets) * 100.

      * @param \Illuminate\Support\Collection $sets  The sets collection.
      * @param string $polarisId  The player's Polaris ID.
      * @return float  Win rate as a percentage.
   */
   private function calculateWinRate($sets, $polarisId) {
     $wins = 0;

     foreach ($sets as $set) {
      if ($set['p1_polaris_id'] === $polarisId) & (set_winner === 1) {
         $wins += 1;
      } elseif ($set['p2_polaris'] === $polarisId) {
           $wins += 1; 
         }
      }

   
      $win_rate = $wins/100;

      dd($win_rate);

   }

}
