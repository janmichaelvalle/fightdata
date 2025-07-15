<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Set;
use App\Models\GameMatch;
use Mockery\CountValidator\AtMost;
use PDO;

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

   $winRate = $this->calculateWinRate($sets, $polarisId);
   $matchupMetrics = $this->analyzeMatchupPerformance($sets, $polarisId);

   // Unpack the 2-element array returned by each function.
   // Each function returns: [$arrayOfCharacterIds, $count]
   // Example: [$findMostDefeatsByIds, $maxLosses]
   list($findMostDefeatsByIds, $maxLosses) = $this->findMostDefeatsBy($matchupMetrics);
   list($mostFrequentOpponentIds, $maxEncounters) = $this->findMostFrequentOpponent($matchupMetrics);
   list($hardestMatchupIds, $maxSweptLosses) = $this->findHardestMatchup($matchupMetrics);


   $getWorstSetLosses = $this->getWorstSetLosses($sets, $polarisId);

   // Load all character names once
   $characterMap = \App\Models\Character::pluck('name', 'chara_id');

   // Helper function to convert an array of character IDs to character names.
   // Uses $characterMap to look up each ID.
   // If an ID is not found in the map, defaults to 'Unknown'.
   // Example: [5, 7, 99] => ['Kazuya', 'Lili', 'Unknown']
   $mapCharacterNames = fn($ids) => collect($ids)->map(fn($id) => $characterMap[$id] ?? 'Unknown');

    // Use it for all metrics
   $mostDefeats = $mapCharacterNames($findMostDefeatsByIds);
   $mostFrequentOpponent = $mapCharacterNames($mostFrequentOpponentIds);
   $hardestMatchups = $mapCharacterNames($hardestMatchupIds);
   $getWorstSetLosses = collect($getWorstSetLosses)->map(function($loss) use ($characterMap) {
    $loss['opponent_character'] = $characterMap[$loss['opponent_character']] ?? 'Unknown';
    $loss['player_character'] = $characterMap[$loss['player_character']] ?? 'Unknown';
    return $loss;
   });   

   $recommendation = $this->generateRecommendation($matchupMetrics, $characterMap);

   return view('set.show', compact(
    'sets', 
    'winRate', 
    'mostDefeats', 
    'matchupMetrics', 
    'hardestMatchups',
    'maxSweptLosses',
    'maxLosses', 
    'getWorstSetLosses', 
    'mostFrequentOpponent', 
    'maxEncounters',
    'characterMap',
    'recommendation',
   ));
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
                  'win_rate' => 0,
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
         $matchupMetrics[$opponentCharaId]['win_rate'] = $matchupMetrics[$opponentCharaId]['wins'] / $matchupMetrics[$opponentCharaId]['total_matches'];
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

      // dd($hardestMatchup, $maxSweptLosses);
      return [$hardestMatchup, $maxSweptLosses];
  
   }

   private function getWorstSetLosses($sets, $polarisId) {
      $worstSetLosses = [];

      foreach ($sets as $set) {
         if ($set['p1_polaris_id'] === $polarisId && ($set['set_winner'] === 2) && ($set['match3_id'] === null)) {
          
           $match1 = GameMatch::select('p1_rounds', 'p2_name')->where('battle_id', $set['match1_id'])->first();
           $match2 = GameMatch::select('p1_rounds', 'p2_name')->where('battle_id', $set['match2_id'])->first();

           if (($match1['p1_rounds'] <= 1) && ($match2['p1_rounds'] <= 1)) {
             $worstSetLosses[] = [
               'set_id' => $set->id,
               'set_start' => $set->set_start,
               'player_character' => $set['p1_chara_id'],
               'opponent_character' => $set['p2_chara_id'],
               'opponent_name' => $match1->p2_name,
               'match1_rounds_player_won' => $match1->p1_rounds,
               'match2_rounds_player_won' => $match2->p1_rounds,
            ];   

           }
          
         }
         if ($set['p2_polaris_id'] === $polarisId && ($set['set_winner'] === 1) && ($set['match3_id'] === null)) {
         
           $match1 = GameMatch::select('p2_rounds', 'p1_name')->where('battle_id', $set['match1_id'])->first();
           $match2 = GameMatch::select('p2_rounds', 'p1_name')->where('battle_id', $set['match2_id'])->first();

           if (($match1['p2_rounds'] <= 1) && ($match2['p2_rounds'] <= 1)) {
             $worstSetLosses[] = [
               'set_id' => $set->id,
               'set_start' => $set->set_start,
               'player_character' => $set['p2_chara_id'],
               'opponent_character' => $set['p1_chara_id'],
               'opponent_name' => $match1->p1_name,
               'match1_rounds_player_won' => $match1->p2_rounds,
               'match2_rounds_player_won' => $match2->p2_rounds,
            ];   

           }
         }  
         
      }
      // dd(collect($worstSetLosses)->toArray());

      return $worstSetLosses;

  
   }

   private function generateRecommendation($matchupMetrics, $characterMap){
      /*
      1. Most frequent opponent with at least 3 total matches
      2. Lowest win rate
   
      */
      $recommendedCharacter = null;
      $lowestWinRate = null;
      $totalMatches = 0;
      foreach ($matchupMetrics as $charaId => $metrics) {
         if ($metrics['total_matches'] > 3) {
            if ($recommendedCharacter === null || 
                $metrics['win_rate'] < $lowestWinRate ||
                ($metrics['win_rate'] == $lowestWinRate && $metrics['total_matches'] > $totalMatches)) {
               $recommendedCharacter  = $charaId;
               $lowestWinRate = $metrics['win_rate'];
               $totalMatches= $metrics['total_matches'];
            }
            
         }
      }

      if ($recommendedCharacter !== null) {
      return "Focus on practicing against " . ($characterMap[$recommendedCharacter] ?? 'Unknown') . 
            ". This is your most impactful matchup to rank up, with a win rate of " . 
            round($lowestWinRate * 100, 2) . "% across " . $totalMatches . " matches.";
      } else {
      return "Keep going! No critical weaknesses found in your most frequent matchups.";
      }


   
}  
   
}   
   
   
