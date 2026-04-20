<div class="container">
    <div class="dashboard-grid">
        <!-- Cel kaloryczny -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-fire"></i>
                <h3>Cel Kaloryczny Dzisiaj</h3>
            </div>
            <div class="card-body">
                <?php
                $nutrition = loadJsonData('data/nutrition.json') ?: [];
                $today = date('Y-m-d');
                $todayGoal = 2000; // Domyślny cel
                $todayConsumed = 0;

                foreach ($nutrition as $entry) {
                    if ($entry['date'] === $today && isset($entry['consumed'])) {
                        $todayConsumed = $entry['consumed'];
                        $todayGoal = $entry['goal'] ?? $todayGoal;
                    }
                }

                $progress = min(100, ($todayConsumed / $todayGoal) * 100);
                ?>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <p><?php echo $todayConsumed; ?>/<?php echo $todayGoal; ?> kcal</p>
            </div>
        </div>

        <!-- Ostatni trening -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-dumbbell"></i>
                <h3>Ostatni Trening</h3>
            </div>
            <div class="card-body">
                <?php
                $workouts = loadJsonData('data/workouts.json') ?: [];
                $lastWorkout = null;
                foreach ($workouts as $workout) {
                    if ($workout['type'] === 'session' && (!$lastWorkout || strtotime($workout['date']) > strtotime($lastWorkout['date']))) {
                        $lastWorkout = $workout;
                    }
                }
                if ($lastWorkout) {
                    echo '<p><strong>Data:</strong> ' . formatDate($lastWorkout['date']) . '</p>';
                    echo '<p><strong>Plan:</strong> ' . ($lastWorkout['plan'] ?? 'Własny') . '</p>';
                    echo '<p><strong>Ćwiczeń:</strong> ' . count($lastWorkout['exercises'] ?? []) . '</p>';
                } else {
                    echo '<p>Brak treningów</p>';
                }
                ?>
            </div>
        </div>

        <!-- Cel tygodniowy -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-calendar-week"></i>
                <h3>Cel Tygodniowy</h3>
            </div>
            <div class="card-body">
                <?php
                $weeklyGoal = 4; // Domyślny cel
                $thisWeek = date('W');
                $thisYear = date('Y');
                $workoutsThisWeek = 0;

                foreach ($workouts as $workout) {
                    if ($workout['type'] === 'session') {
                        $workoutWeek = date('W', strtotime($workout['date']));
                        $workoutYear = date('Y', strtotime($workout['date']));
                        if ($workoutWeek == $thisWeek && $workoutYear == $thisYear) {
                            $workoutsThisWeek++;
                        }
                    }
                }

                $weeklyProgress = min(100, ($workoutsThisWeek / $weeklyGoal) * 100);
                ?>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $weeklyProgress; ?>%"></div>
                </div>
                <p><?php echo $workoutsThisWeek; ?>/<?php echo $weeklyGoal; ?> treningów</p>
            </div>
        </div>

        <!-- Wykres wagi -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-line"></i>
                <h3>Waga (ostatnie 30 dni)</h3>
            </div>
            <div class="card-body">
                <canvas id="weightChart"></canvas>
            </div>
        </div>

        <!-- Rekordy 1RM -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-trophy"></i>
                <h3>Ostatnie Rekordy 1RM</h3>
            </div>
            <div class="card-body">
                <?php
                $records = [];
                foreach ($workouts as $workout) {
                    if ($workout['type'] === 'onerm_record') {
                        $exercise = $workout['exercise'];
                        $weight = $workout['weight'];
                        if (!isset($records[$exercise]) || $weight > $records[$exercise]['weight']) {
                            $records[$exercise] = ['weight' => $weight, 'date' => $workout['date']];
                        }
                    }
                }
                arsort($records);
                $topRecords = array_slice($records, 0, 3, true);
                if ($topRecords) {
                    foreach ($topRecords as $exercise => $data) {
                        echo "<p><strong>$exercise:</strong> {$data['weight']} kg (" . formatDate($data['date']) . ")</p>";
                    }
                } else {
                    echo '<p>Brak rekordów</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div>

<script>
// Animowane liczniki
document.addEventListener('DOMContentLoaded', function() {
    animateCounters();
    loadWeightChart();
});

function animateCounters() {
    const counters = document.querySelectorAll('.card-body p');
    counters.forEach(counter => {
        const text = counter.textContent;
        const match = text.match(/(\d+)\/(\d+)/);
        if (match) {
            const current = parseInt(match[1]);
            const total = parseInt(match[2]);
            animateNumber(counter, 0, current, 1000);
        }
    });
}

function animateNumber(element, start, end, duration) {
    const startTime = performance.now();
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const value = Math.floor(start + (end - start) * progress);
        element.textContent = element.textContent.replace(/\d+/, value);
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    requestAnimationFrame(update);
}

function loadWeightChart() {
    fetch('api/nutrition.php?action=get_weight_data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('weightChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.data.dates,
                        datasets: [{
                            label: 'Waga (kg)',
                            data: data.data.weights,
                            borderColor: '#00E5FF',
                            backgroundColor: 'rgba(0, 229, 255, 0.1)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: false
                            }
                        }
                    }
                });
            }
        });
}
</script>