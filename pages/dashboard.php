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
                if (!is_array($nutrition)) {
                    $nutrition = [];
                }
                $today = date('Y-m-d');
                $todayGoal = 2000; // Domyślny cel
                $todayConsumed = 0;

                foreach ($nutrition as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    if (($entry['date'] ?? '') === $today && isset($entry['consumed'])) {
                        $todayConsumed = $entry['consumed'];
                        $todayGoal = $entry['goal'] ?? $todayGoal;
                    }
                }

                $progress = min(100, ($todayConsumed / $todayGoal) * 100);
                ?>
                <div class="progress-bar">
                    <div class="progress-fill" id="calorieProgressFill" style="width: <?php echo $progress; ?>%"></div>
                </div>
                <p id="calorieSummary"><span id="calorieConsumed"><?php echo $todayConsumed; ?></span>/<span id="calorieGoalValue"><?php echo $todayGoal; ?></span> kcal</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <i class="fas fa-apple-alt"></i>
                <h3>Dodaj spożycie kalorii</h3>
            </div>
            <div class="card-body">
                <form id="addCaloriesForm">
                    <div class="form-group">
                        <label for="caloriesAmount">Kalorie (kcal):</label>
                        <input type="number" id="caloriesAmount" min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="proteinAmount">Białko (g):</label>
                        <input type="number" id="proteinAmount" min="0" step="0.1" value="0">
                    </div>
                    <div class="form-group">
                        <label for="fatAmount">Tłuszcze (g):</label>
                        <input type="number" id="fatAmount" min="0" step="0.1" value="0">
                    </div>
                    <div class="form-group">
                        <label for="carbsAmount">Węglowodany (g):</label>
                        <input type="number" id="carbsAmount" min="0" step="0.1" value="0">
                    </div>
                    <div class="form-group">
                        <label for="caloriesDescription">Opis produktu/posiłku:</label>
                        <input type="text" id="caloriesDescription" placeholder="np. owsianka, kurczak, shake">
                    </div>
                    <button type="submit" class="btn btn-primary">Dodaj kalorie</button>
                </form>
                <div class="nutrition-log">
                    <h4>Dzisiejszy dziennik</h4>
                    <div class="nutrition-summary">
                        <span>Kalorie: <strong id="totalCalories">0</strong> kcal</span>
                        <span>Białko: <strong id="totalProtein">0</strong> g</span>
                        <span>Tłuszcze: <strong id="totalFat">0</strong> g</span>
                        <span>Węglowodany: <strong id="totalCarbs">0</strong> g</span>
                    </div>
                    <ul id="calorieEntriesList">
                        <?php
                        $todayEntries = [];
                        $todayTotals = ['calories' => 0, 'protein' => 0, 'fat' => 0, 'carbs' => 0];
                        foreach ($nutrition as $entry) {
                            if (!is_array($entry)) {
                                continue;
                            }
                            if (($entry['date'] ?? '') === $today) {
                                $todayEntries = $entry['entries'] ?? [];
                                $todayTotals['calories'] = $entry['consumed'] ?? 0;
                                $todayTotals['protein'] = $entry['protein'] ?? 0;
                                $todayTotals['fat'] = $entry['fat'] ?? 0;
                                $todayTotals['carbs'] = $entry['carbs'] ?? 0;
                                break;
                            }
                        }
                        echo '<script>window.initialNutritionTotals = ' . json_encode($todayTotals) . ';</script>';
                        if (!$todayEntries) {
                            echo '<li class="empty-state">Brak wpisów kalorycznych na dziś.</li>';
                        } else {
                            foreach ($todayEntries as $item) {
                                echo '<li>' . htmlspecialchars($item['time'] ?? '') . ' - ' . htmlspecialchars($item['description'] ?? 'posiłek') . ': ' . intval($item['calories']) . ' kcal, ' . floatval($item['protein']) . 'g B, ' . floatval($item['fat']) . 'g T, ' . floatval($item['carbs']) . 'g W</li>';
                            }
                        }
                        ?>
                    </ul>
                </div>
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
    loadNutritionData();
    const addForm = document.getElementById('addCaloriesForm');
    if (addForm) {
        addForm.addEventListener('submit', addCalories);
    }
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

function loadNutritionData() {
    fetch('api/nutrition.php?action=get_today_nutrition')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const entry = data.data;
                const consumed = entry.consumed || 0;
                const goal = entry.goal || 2000;
                const progress = Math.min(100, (consumed / goal) * 100);
                document.getElementById('calorieConsumed').textContent = consumed;
                document.getElementById('calorieGoalValue').textContent = goal;
                document.getElementById('calorieProgressFill').style.width = progress + '%';

                const list = document.getElementById('calorieEntriesList');
                list.innerHTML = '';
                if (!entry.entries || entry.entries.length === 0) {
                    list.innerHTML = '<li class="empty-state">Brak wpisów kalorycznych na dziś.</li>';
                } else {
                    entry.entries.forEach(item => {
                        const listItem = document.createElement('li');
                        listItem.textContent = `${item.time || ''} - ${item.description || 'posiłek'}: ${item.calories} kcal, ${item.protein || 0}g B, ${item.fat || 0}g T, ${item.carbs || 0}g W`;
                        list.appendChild(listItem);
                    });
                }
                document.getElementById('totalCalories').textContent = entry.consumed || 0;
                document.getElementById('totalProtein').textContent = entry.protein || 0;
                document.getElementById('totalFat').textContent = entry.fat || 0;
                document.getElementById('totalCarbs').textContent = entry.carbs || 0;
            }
        });
}

function addCalories(event) {
    event.preventDefault();
    let calories = parseInt(document.getElementById('caloriesAmount').value, 10);
    const protein = parseFloat(document.getElementById('proteinAmount').value) || 0;
    const fat = parseFloat(document.getElementById('fatAmount').value) || 0;
    const carbs = parseFloat(document.getElementById('carbsAmount').value) || 0;
    const description = document.getElementById('caloriesDescription').value.trim();
    const hasMacros = protein > 0 || fat > 0 || carbs > 0;

    if ((!calories || calories <= 0) && !hasMacros) {
        showToast('Wprowadź liczbę kalorii lub makroskładniki', 'error');
        return;
    }
    if (protein < 0 || fat < 0 || carbs < 0) {
        showToast('Podaj poprawne wartości makroskładników', 'error');
        return;
    }
    if ((!calories || calories <= 0) && hasMacros) {
        calories = Math.round(protein * 4 + fat * 9 + carbs * 4);
    }

    fetch('api/nutrition.php?action=add_calories', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ calories, protein, fat, carbs, description })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Dodano kalorie!', 'success');
                document.getElementById('addCaloriesForm').reset();
                loadNutritionData();
                loadWeightChart();
            } else {
                showToast(data.error || 'Błąd zapisu kalorii', 'error');
            }
        })
        .catch(() => showToast('Błąd połączenia z serwerem', 'error')); 
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