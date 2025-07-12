<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Set;
use App\Models\GameMatch;
use Mockery\CountValidator\AtMost;

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
   $matchupMetrics = $this->analyzeMatchupPerformance($sets, $polarisId);
   // dd($matchupMetrics);
   $findFindMostDefeatsBy = $this->findMostDefeatsBy($matchupMetrics);
   $findMostFrequentOpponent = $this->findMostFrequentOpponent($matchupMetrics);
   $findHardestMatchup = $this->findHardestMatchup($matchupMetrics);

   return view('set.show', compact('sets', 'winRate', 'findMostFrequentOpponent', 'matchupMetrics'));
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
      
      $winRate = $wins/100;

      return $winRate;

      // dd($wins, $winRate);

   }

   /** [DEPRECATED] Calculate the most frequent opponents faced by the player over the last 100 sets.
    * Logic:
    * 1. Extract match1_ids from each set to identify representative matches.
    * 2. Query game_matches table to get details about these matches.
    * 3. Determine opponent character IDs relative to the player's Polaris ID.
    * 4. Count how many times each opponent character appears.
    * 5. Identify the opponent(s) with the highest frequency (ties included).
    * @param \Illuminate\Support\Collection $sets  Collection of the player's sets retrieved from getPlayerSets().
    * @param string $polarisId  The player's Polaris ID.
    * @return \Illuminate\Support\Collection  Collection of the most frequent opponent character IDs with their frequencies.
    */

   
    private function calculateMostFrequentOpponentOld($sets, $polarisId) {
      $matchIds = $sets->pluck('match1_id')->filter(); #gets match1 IDs from all sets

      $matches = GameMatch::whereIn('battle_id', $matchIds)->get();

      $opponents = [];
      foreach ($matches as $match) {
    
         if ($match['p1_polaris_id'] === $polarisId) {
            $opponents[] = $match['p2_chara_id'];
         } else {
            $opponents[] = $match['p1_chara_id'];
         }
      }
      $opponentsCollection = collect($opponents);

      $opponentCounts = $opponentsCollection->countBy()->sortDesc();
      $maxOpponentCount = $opponentCounts->max();
      $mostFrequentOpponent = $opponentCounts->filter(function ($count, $chara_id) use ($maxOpponentCount) {
         if ($count === $maxOpponentCount) {
            return $chara_id;
         };
      });

      return $mostFrequentOpponent;
      
      /*
      dd(
         ['matchIds' => $matchIds->toArray()],
         ['matches' => $matches->toArray()],
         ['opponentsCollection' => $opponentsCollection->toArray()],
         ['opponentCounts' => $opponentCounts->toArray()],
         ['maxOpponentCount' => $maxOpponentCount],
         ['mostFrequentOpponent' => $mostFrequentOpponent->toArray()],
      );
      */
   }

   /** Analyze matchup performance metrics against all opponents over the last 100 sets.
    *
    * Logic:
    * - Loop through each set to determine the opponent's character and the outcome (win/loss).
    * - Track total matches, wins, losses, and swept losses (losses where match3_id is null).
    * - The result is an associative array indexed by opponent character ID.
    *
    * @param \Illuminate\Support\Collection $sets  Collection of the player's sets retrieved from getPlayerSets().
    * @param string $polarisId  The player's Polaris ID.
    * @return array  An associative array with opponent character IDs as keys and values containing:
    *                - 'wins' => number of wins against this opponent
    *                - 'losses' => number of losses against this opponent
    *                - 'swept_losses' => number of 2-0 losses against this opponent
    *                - 'total_matches' => total number of sets played against this opponent
    */
   private function analyzeMatchupPerformance($sets, $polarisId){
      $matchupMetrics = [];

      foreach ($sets as $set) {
         if($set['p1_polaris_id'] === $polarisId) {
            $opponentCharaId = $set['p2_chara_id'];
            $didWin = ($set['set_winner'] === 1);
         } elseif ($set['p2_polaris_id'] === $polarisId) {
            $opponentCharaId = $set['p1_chara_id'];
            $didWin = ($set['set_winner'] === 2);
            } else {
               continue;
            }
         if (!isset($matchupMetrics[$opponentCharaId])) {
               $matchupMetrics[$opponentCharaId] = [
                  'wins' => 0,
                  'losses' => 0,
                  'total_matches' => 0,
                  'swept_losses' => 0
               ];
            }
         if ($didWin) {
            $matchupMetrics[$opponentCharaId]['wins'] += 1;
         } else {
            $matchupMetrics[$opponentCharaId]['losses'] += 1;
            if ($set['match3_id'] === null) {
               $matchupMetrics[$opponentCharaId]['swept_losses'] += 1;
            }
         }
         $matchupMetrics[$opponentCharaId]['total_matches'] +=1;
         }
      // dd($matchupMetrics);
      return $matchupMetrics;  
   } 

   /** Identify the character with highest losses.
    * Logic:
    * - Loop through the matchup metrics array (character IDs with wins and losses).
    * - Track the character(s) with the maximum number of losses.
    * - If a new highest loss count is found, reset the findMostDefeatsBy list.
    * - If a character has the same losses as the current maximum, add it to the list.
    *
    * @param array $matchupMetrics  Associative array with charaId as key and ['wins' => int, 'losses' => int] as value.
    * @return array  An array containing:
    *                - findMostDefeatsBy: array of character IDs with max losses.
    *                - maxLosses: integer value of the highest number of losses.
    */

   private function findMostDefeatsBy($matchupMetrics) {
      $findMostDefeatsBy = [];
      $maxLosses = null;
      foreach ($matchupMetrics as $charaId => $metrics) {
         if ((empty($findMostDefeatsBy)) || ($maxLosses < $metrics['losses'])) {
            $findMostDefeatsBy = [];
            $findMostDefeatsBy[] = $charaId;
            $maxLosses = $metrics['losses'];
         } elseif ($maxLosses === $metrics['losses']) {
            $findMostDefeatsBy[] = $charaId;
         }
      }
      // dd($findMostDefeatsBy, $maxLosses);
      return [$findMostDefeatsBy, $maxLosses];
      }

   /** Identify the most frequently encountered opponent character(s) based on total sets.
    *
    * Logic:
    * - Loop through the matchup metrics array (character IDs with wins, losses, and total encounters).
    * - Track the character(s) with the highest number of total encounters.
    * - If a new highest total is found, reset the mostFrequentOpponent list.
    * - If multiple characters have the same maximum total, include them all.
    *
    * @param array $matchupMetrics  Associative array with charaId as key and ['wins' => int, 'losses' => int, 'total' => int] as value.
    * @return array  An array containing:
    *                - mostFrequentOpponent: array of character IDs tied for the highest total encounters.
    *                - maxEncounters: integer value of the highest encounter count.
    */  
   private function findMostFrequentOpponent($matchupMetrics) {
      $mostFrequentOpponent = [];
      $maxEncounters = null;
      foreach ($matchupMetrics as $charaId => $metrics) {
         if ((empty($mostFrequentOpponent)) || ($maxEncounters < $metrics['total_matches'])) {
            $mostFrequentOpponent  = [];
            $mostFrequentOpponent[] = $charaId;
            $maxEncounters = $metrics['total_matches'];
         } elseif ($maxEncounters === $metrics['total_matches']) {
            $mostFrequentOpponent[] = $charaId;
         }
      }
      // dd($mostFrequentOpponent, $maxEncounters);
      return [$mostFrequentOpponent, $maxEncounters];
      }


   private function findHardestMatchup($matchupMetrics) {
   $hardestMatchup = [];
   $maxSweptLosses = null;
   foreach ($matchupMetrics as $charaId => $metrics) {
      if ((empty($hardestMatchup)) || ($maxSweptLosses < $metrics['swept_losses'])) {
         $hardestMatchup  = [];
         $hardestMatchup[] = $charaId;
         $maxSweptLosses = $metrics['swept_losses'];
      } elseif ($maxSweptLosses === $metrics['swept_losses']) {
         $hardestMatchup[] = $charaId;
      }
   }

   dd($hardestMatchup, $maxSweptLosses);
   return [$hardestMatchup, $maxSweptLosses];
   
  
   }
}
      
   
   
