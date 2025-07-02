<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GameMatch;
use App\Models\Set;

class GenerateSets extends Command
{
    protected $signature = 'generate:sets';
    protected $description = 'Generate sets from game_matches';

    public function handle()
    {
        $matches = GameMatch::orderBy('battle_at')->get()->groupBy(function ($match) {
            // Unique key for a session between 2 players (sorted alphabetically to group symmetrically)
            $ids = [$match->p1_polaris_id, $match->p2_polaris_id];
            sort($ids);
            return implode('-', $ids);
        });

        foreach ($matches as $pairMatches) {
            $buffer = [];

            foreach ($pairMatches as $match) {
                $buffer[] = $match;

                if (count($buffer) === 3 || $this->isSetComplete($buffer)) {
                    $this->saveSet($buffer);
                    $buffer = [];
                }
            }

            // Save remaining if not empty
            if (count($buffer)) {
                $this->saveSet($buffer);
            }
        }

        $this->info('Sets generated successfully.');
    }

    private function isSetComplete($matches)
    {
        $p1Wins = 0;
        $p2Wins = 0;

        foreach ($matches as $match) {
            if ($match->winner == 1) {
                $p1Wins++;
            } elseif ($match->winner == 2) {
                $p2Wins++;
            }
        }

        return $p1Wins === 2 || $p2Wins === 2;
    }

    private function saveSet($matches)
    {
        $first = $matches[0];
        $p1Char = $first->p1_chara_id;
        $p2Char = $first->p2_chara_id;
        $p1Id = $first->p1_polaris_id;
        $p2Id = $first->p2_polaris_id;
        $matchIds = array_map(function ($m) {
            return (string) $m->battle_id;
        }, $matches);
        $setStart = $first->battle_at;

        $p1Wins = collect($matches)->where('winner', 1)->count();
        $p2Wins = collect($matches)->where('winner', 2)->count();

        $setWinner = 0;
        if ($p1Wins === 2) $setWinner = 1;
        elseif ($p2Wins === 2) $setWinner = 2;

        Set::create([
            'p1_polaris_id' => $p1Id,
            'p2_polaris_id' => $p2Id,
            'p1_chara_id' => $p1Char,
            'p2_chara_id' => $p2Char,
            'match1_id' => isset($matchIds[0]) ? $matchIds[0] : null,
            'match2_id' => isset($matchIds[1]) ? $matchIds[1] : null,
            'match3_id' => isset($matchIds[2]) ? $matchIds[2] : null,
            'set_start' => $setStart,
            'set_winner' => $setWinner,
        ]);
    }
}