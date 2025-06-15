<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GameMatch;
use League\Csv\Reader;

class ImportMatches extends Command
{
    protected $signature = 'import:matches';
    protected $description = 'Import matches from CSV to game_matches table';

    public function handle()
    {
        $path = storage_path('app/data/matches.csv');

        if (!file_exists($path)) {
            $this->error("CSV file not found at $path");
            return;
        }

        $file = fopen($path, 'r');
        $headers = fgetcsv($file);

        $count = 0;

        while (($row = fgetcsv($file)) !== false) {
            $data = array_combine($headers, $row);

            // Convert integers and nulls if needed
            $data = array_map(function ($value) {
                if ($value === '') return null;
                if (is_numeric($value)) return $value + 0;
                return $value;
            }, $data);

            GameMatch::create($data);
            $count++;
        }

        fclose($file);
        $this->info("Imported $count matches successfully.");
    }
}