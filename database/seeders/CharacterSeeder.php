<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use App\Models\Character;

class CharacterSeeder extends Seeder
{
    public function run()
    {
        $characters = [
            0 => 'Paul', 1 => 'Law', 2 => 'King', 3 => 'Yoshimitsu', 4 => 'Hwoarang',
            5 => 'Xiaoyu', 6 => 'Jin', 7 => 'Bryan', 8 => 'Kazuya', 9 => 'Steve',
            10 => 'Jack-8', 11 => 'Asuka', 12 => 'Devil Jin', 13 => 'Feng', 14 => 'Lili',
            15 => 'Dragunov', 16 => 'Leo', 17 => 'Lars', 18 => 'Alisa', 19 => 'Claudio',
            20 => 'Shaheen', 21 => 'Nina', 22 => 'Lee', 23 => 'Kuma', 24 => 'Panda',
            25 => 'Unknown', 26 => 'Unknown', 27 => 'Unknown', 28 => 'Zafina', 29 => 'Leroy',
            30 => 'Unknown', 31 => 'Unknown', 32 => 'Jun', 33 => 'Reina', 34 => 'Azucena',
            35 => 'Victor', 36 => 'Raven', 37 => 'Unknown', 38 => 'Eddy', 39 => 'Lidia',
            40 => 'Heihachi', 41 => 'Clive', 42 => 'Anna', 43 => 'Fahkumram',
        ];

        foreach ($characters as $id => $name) {
            Character::updateOrCreate(['chara_id' => $id], ['name' => $name]);
        }
    }
}