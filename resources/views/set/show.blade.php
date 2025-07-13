<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>

<p><strong>Note:</strong> All data shown is based on your latest 100 complete ranked sets.</p>


<p> Win Rate: {{ number_format($winRate * 100, 2) }}%</p>

<p> mostDefeats: {{ $mostDefeats }} Number of sets defeated: {{ $maxLosses }} </p>

<p>mostFrequentOpponent: {{ $mostFrequentOpponent }} Number of sets: {{ $maxEncounters }} </p>

<p>hardestMatchups: {{ $hardestMatchups }} maxSweptLosses: {{ $maxSweptLosses }} </p>

<p>getWorstLosses: These are sets where you won 1 or fewer rounds per match — tough losses worth reviewing.</p>
<table border="1">
  <thead>
    <tr>
      <th>Date</th>
      <th>Opponent Name</th>
      <th>Opponent Character</th>
      <th>Player Character</th>
      <th>Match 1 Rounds Won</th>
      <th>Match 2 Rounds Won</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($getWorstSetLosses as $loss)
      <tr>
        <td>{{ date('Y-m-d H:i', $loss['set_start']) }}</td>
        <td>{{ $loss['opponent_name'] }}</td>
        <td>{{ $loss['opponent_character'] }}</td>
        <td>{{ $loss['player_character'] }}</td>
        <td>{{ $loss['match1_rounds_player_won'] }}</td>
        <td>{{ $loss['match2_rounds_player_won'] }}</td>
      </tr>
    @endforeach
  </tbody>
</table>

</body>
</html>