<!DOCTYPE html>
<html>
<head>
    <title>Player Sets</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <h1>Sets for {{ $polarisId }}</h1>

    <div style="background-color: #f0f8ff; padding: 10px; margin-bottom: 20px;">
        <strong>Insight:</strong> {{ $insight }}
    </div>

    <div style="display: flex; gap: 20px; margin-bottom: 30px;">
        <div>
            <h4>Win Rate</h4>
            <p style="font-size: 24px;">{{ $winRate }}%</p>
            <small>{{ $winRateDelta >= 0 ? '+' : '' }}{{ $winRateDelta }}%</small>
        </div>
        <div>
            <h4>Most Frequent Opponent</h4>
            <p>{{ $mostFrequentData['character'] }}</p>
            <small>{{ $mostFrequentData['count'] }} sets</small>
        </div>
        <div>
            <h4>Hardest Matchup</h4>
            <p>{{ $hardestData['character'] }}</p>
            <small>{{ $hardestData['summary'] }}</small>
        </div>
        <div>
            <h4>Most Defeats By</h4>
            <p>{{ $mostDefeatsData['character'] }}</p>
            <small>{{ $mostDefeatsData['summary'] }}</small>
        </div>
    </div>

    <h2>Win Rate Trend</h2>
    <canvas id="winrateTrend" height="100"></canvas>
    <script>
        const ctx = document.getElementById('winrateTrend').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: {!! json_encode($trendLabels) !!},
                datasets: [{
                    label: 'Rolling Win Rate (10 sets)',
                    data: {!! json_encode($trendData) !!},
                    borderColor: 'blue',
                    fill: false,
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, max: 100 }
                }
            }
        });
    </script>

    <h2>Hardest Sets</h2>
    <p>These are sets where you won 1 or fewer rounds per match — tough losses worth reviewing.</p>
    <table border="1" cellpadding="5">
        <tr>
            <th>Date</th>
            <th>Opponent</th>
            <th>Character</th>
            <th>1st Match Rounds Won</th>
            <th>2nd Match Rounds Won</th>
        </tr>
        @foreach ($hardestSets as $row)
            <tr>
                <td>{{ $row['date'] }}</td>
                <td>{{ $row['opponent'] }}</td>
                <td>{{ $characterNames[$row['character']] ?? $row['character'] }}</td>
                <td>{{ $row['rounds'][0] }}</td>
                <td>{{ $row['rounds'][1] }}</td>
            </tr>
        @endforeach
    </table>
</body>
</html>