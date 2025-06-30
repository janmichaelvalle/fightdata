<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Set;


class SimplePlayerController extends Controller
{
   /** Retrieve the last 100 complete sets (set_winner 1 or 2) for the specified player.
   * Logic:
   * 1. Query sets where p1_polaris_id or p2_polaris_id matches the player's Polaris ID.
   * 2. Include only sets with set_winner 1 or 2 (complete sets).
   * 3. Order by set_start descending and limit to 100 sets.
   * 4. Pass the sets collection to the view with additional calculated metrics.
   *
   * @param string $polarisId  The player's Polaris ID.
   * @return \Illuminate\View\View
   */

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

   /** Calculate the player's win rate over the last 100 complete sets.
   * Logic:
   * 1. Loop through each set in the collection.
   * 2. Count a win if:
   *    - The player is p1 and set_winner is 1, or
   *    - The player is p2 and set_winner is 2.
   * 3. Compute win rate as (number of wins / total sets) * 100.
   *
   * @param \Illuminate\Support\Collection $sets  The sets collection retrieved from getPlayerSets().
   * @param string $polarisId  The player's Polaris ID.
   * @return float  Win rate as a percentage.
   */
   private function calculateWinRate($sets, $polarisId) {
     $wins = 0;

     foreach ($sets as $set) {
      if ($set['p1_polaris_id'] === $polarisId && $set['set_winner'] === 1) {
         $wins += 1;
      } elseif ($set['p2_polaris_id'] === $polarisId && $set['set_winner'] === 2) {
           $wins += 1; 
         }
      }
      
      $win_rate = $wins/100;

      dd($wins, $win_rate);

   }

}
