<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Set;
use App\Models\GameMatch;

class PlayerController extends Controller
{
    public function show($polarisId)
    {
        $sets = Set::where('p1_polaris_id', $polarisId)
                    ->orWhere('p2_polaris_id', $polarisId)
                    ->orderBy('set_start', 'desc')
                    ->take(100)
                    ->get();

        $matchIds = $sets->flatMap(function ($set) {
            return [$set->match1_id, $set->match2_id, $set->match3_id];
        })->filter()->unique();

        $matches = GameMatch::whereIn('battle_id', $matchIds)->get()->keyBy('battle_id');

        // Calculate win rate
        $wins = $sets->filter(fn($set) => 
            ($set->set_winner == 1 && $set->p1_polaris_id === $polarisId) || 
            ($set->set_winner == 2 && $set->p2_polaris_id === $polarisId)
        )->count();

        $totalSets = $sets->count();
        $winRate = $totalSets > 0 ? round(($wins / $totalSets) * 100, 2) : 0;
        $winRateDelta = 5.0; // Placeholder for delta

        $matchData = collect();
        foreach ($sets as $set) {
            foreach (['match1_id', 'match2_id', 'match3_id'] as $matchKey) {
                $match = $matches[$set->$matchKey] ?? null;
                if (!$match) continue;

                $isP1 = $match->p1_polaris_id === $polarisId;
                $opponentId = $isP1 ? $match->p2_polaris_id : $match->p1_polaris_id;
                $opponentCharId = $isP1 ? $match->p2_chara_id : $match->p1_chara_id;
                $roundsWon = $isP1 ? $match->p1_rounds : $match->p2_rounds;
                $won = ($isP1 && $match->winner == 1) || (!$isP1 && $match->winner == 2);
                $matchData->push(compact('opponentId', 'opponentCharId', 'roundsWon', 'won'));
            }
        }

        $characterNames = \App\Models\Character::pluck('name', 'chara_id');

        $mostFrequent = $matchData->groupBy('opponentCharId')->sortByDesc(fn($g) => $g->count())->first();
        $mostFrequentData = $mostFrequent ? [
            'character' => $characterNames[$mostFrequent->first()['opponentCharId']] ?? 'Unknown',
            'count' => $mostFrequent->count()
        ] : ['character' => 'N/A', 'count' => 0];

        $lossesByChar = $matchData->where('won', false)->groupBy('opponentCharId');
        $mostDefeats = $lossesByChar->sortByDesc(fn($g) => $g->count())->first();
        $mostDefeatsData = $mostDefeats ? [
            'character' => $characterNames[$mostDefeats->first()['opponentCharId']] ?? 'Unknown',
            'summary' => "{$mostDefeats->count()} losses"
        ] : ['character' => 'N/A', 'summary' => ''];

        $hardest = $matchData->groupBy('opponentCharId')->filter(function ($g) {
            return $g->count() >= 3;
        })->sortBy(fn($g) => $g->where('won', true)->count())->first();
        $hardestData = $hardest ? [
            'character' => $characterNames[$hardest->first()['opponentCharId']] ?? 'Unknown',
            'summary' => $hardest->where('won', false)->count() . " losses / " . $hardest->where('won', true)->count() . " wins"
        ] : ['character' => 'N/A', 'summary' => ''];

        $insight = "Keep it up! No major trends spotted.";
        foreach ($matchData->groupBy('opponentCharId') as $charId => $g) {
            $rate = $g->sum('roundsWon') / ($g->count() * 3);
            if ($g->count() >= 3 && $rate < 0.35) {
                $insight = "You're struggling against character ID {$charId}. Consider reviewing those matches.";
                break;
            }
        }

        $trendData = [];
        $trendLabels = [];
        for ($i = 0; $i < 10; $i++) {
            $chunk = $sets->slice($i * 10, 10);
            $chunkWins = $chunk->filter(fn($set) =>
                ($set->set_winner == 1 && $set->p1_polaris_id === $polarisId) || 
                ($set->set_winner == 2 && $set->p2_polaris_id === $polarisId)
            )->count();
            $trendData[] = round(($chunkWins / 10) * 100, 2);
            $trendLabels[] = ($i + 1) * 10;
        }

        $hardestSets = [];
        foreach ($sets as $set) {
            $rounds = [];
            $matchInfo = [];
            foreach (['match1_id', 'match2_id'] as $matchKey) {
                $match = $matches[$set->$matchKey] ?? null;
                if (!$match) continue;

                $isP1 = $match->p1_polaris_id === $polarisId;
                $rounds[] = $isP1 ? $match->p1_rounds : $match->p2_rounds;
                if (!$matchInfo) {
                    $matchInfo = [
                        'date' => date('Y-m-d', $set->set_start),
                        'opponent' => $isP1 ? $match->p2_name : $match->p1_name,
                        'character' => $isP1 ? $match->p2_chara_id : $match->p1_chara_id,
                    ];
                }
            }
            if (count($rounds) == 2 && max($rounds) <= 1) {
                $hardestSets[] = array_merge($matchInfo, ['rounds' => $rounds]);
            }
        }

        return view('player.show', compact(
            'sets', 'matches', 'polarisId', 'winRate', 'winRateDelta',
            'mostFrequentData', 'mostDefeatsData', 'hardestData',
            'trendLabels', 'trendData', 'hardestSets', 'insight', 'characterNames'
        ));
    }
}