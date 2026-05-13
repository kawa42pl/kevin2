<?php
// Załaduj dane na potrzeby dashboard
$today = date('Y-m-d');
$workouts = loadJsonData('data/workouts.json') ?: [];
$nutrition = loadJsonData('data/nutrition.json') ?: [];
if (!is_array($nutrition)) {
    $nutrition = [];
}
?>

<div class="container">
    <!-- Sekcja statystyk użytkownika -->
    <div class="stats-banner">
        <div class="stat-card">
            <i class="fas fa-heartbeat"></i>
            <div class="stat-info">
                <span class="stat-label">BMI</span>
                <span class="stat-value" id="userBMI">--</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar-check"></i>
            <div class="stat-info">
                <span class="stat-label">Treningi (miesiąc)</span>
                <span class="stat-value" id="monthlyWorkouts">0</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-fire"></i>
            <div class="stat-info">
                <span class="stat-label">Średnie kalorie</span>
                <span class="stat-value" id="avgCalories">0</span>
            </div>
        </div>
        <div class="stat-card">
            <i class="fas fa-streak"></i>
            <div class="stat-info">
                <span class="stat-label">Seria treningów</span>
                <span class="stat-value" id="workoutStreak">0</span>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Cel kaloryczny -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-fire"></i>
                <h3>Cel Kaloryczny Dzisiaj</h3>
            </div>
            <div class="card-body">
                <?php
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
                <div class="nutrition-summary" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                    <span>Białko: <strong id="dailyProtein">0</strong>g</span>
                    <span>Tłuszcze: <strong id="dailyFat">0</strong>g</span>
                    <span>Węgle: <strong id="dailyCarbs">0</strong>g</span>
                </div>
            </div>
        </div>

        <!-- Ostatnie treningi -->
        <div class="card" style="grid-column: span 2;">
            <div class="card-header">
                <i class="fas fa-dumbbell"></i>
                <h3>Historia Treningów</h3>
            </div>
            <div class="card-body">
                <div id="recentWorkouts" style="max-height: 300px; overflow-y: auto;">
                    <p style="text-align: center; color: var(--text-muted);">Ładowanie...</p>
                </div>
                <a href="?page=workouts" class="btn btn-secondary" style="margin-top: 10px; width: 100%; text-align: center;">Wszystkie treningi →</a>
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
                    <button type="submit" class="btn btn-primary">Dodaj kalorie</button>
                </form>
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

        <!-- Aktywność tygodniowa -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i>
                <h3>Aktywność (ostatnie 7 dni)</h3>
            </div>
            <div class="card-body">
                <canvas id="activityChart"></canvas>
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

<style>
.stats-banner {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
    border-radius: var(--border-radius);
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: var(--shadow);
}

.stat-card i {
    font-size: 2rem;
    opacity: 0.8;
}

.stat-info {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.stat-value {
    font-size: 1.8rem;
    font-weight: bold;
}

.recent-workout-item {
    padding: 12px;
    margin-bottom: 10px;
    background-color: var(--card-bg);
    border-left: 3px solid var(--primary-color);
    border-radius: 4px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.recent-workout-item:hover {
    background-color: var(--darker-bg);
}

.workout-date {
    font-size: 0.85rem;
    color: var(--text-muted);
}

.workout-exercise-count {
    background-color: var(--primary-color);
    color: #000;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: bold;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    loadWeightChart();
    loadActivityChart();
    loadNutritionData();
    loadRecentWorkouts();
    
    const addForm = document.getElementById('addCaloriesForm');
    if (addForm) {
        addForm.addEventListener('submit', addCalories);
    }
});

function loadDashboardData() {
    const userData = <?php echo json_encode($_SESSION['user_data'] ?? []); ?>;
    
    if (userData.weight && userData.height) {
        const bmi = (userData.weight / ((userData.height / 100) ** 2)).toFixed(1);
        document.getElementById('userBMI').textContent = bmi;
    }

    // Pobierz statystyki z API
    fetch('api/workout.php?action=get_monthly_stats')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('monthlyWorkouts').textContent = data.data.monthly_count || 0;
                document.getElementById('avgCalories').textContent = Math.round(data.data.avg_calories || 0);
                document.getElementById('workoutStreak').textContent = data.data.streak || 0;
            }
        })
        .catch(() => {});
}

function loadRecentWorkouts() {
    fetch('api/workout.php?action=get_recent')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('recentWorkouts');
            if (data.success && data.data.length > 0) {
                container.innerHTML = data.data.map(w => `
                    <div class="recent-workout-item">
                        <div>
                            <strong>${w.plan || 'Własny trening'}</strong>
                            <div class="workout-date">${formatDate(w.date)}</div>
                        </div>
                        <div class="workout-exercise-count">${w.exercises?.length || 0} ćw.</div>
                    </div>
                `).join('');
            } else {
                container.innerHTML = '<p style="text-align: center; color: var(--text-muted);">Brak treningów</p>';
            }
        })
        .catch(() => {
            document.getElementById('recentWorkouts').innerHTML = '<p style="color: var(--text-muted);">Błąd ładowania</p>';
        });
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

                document.getElementById('dailyProtein').textContent = Math.round(entry.protein || 0);
                document.getElementById('dailyFat').textContent = Math.round(entry.fat || 0);
                document.getElementById('dailyCarbs').textContent = Math.round(entry.carbs || 0);
            }
        });
}

function addCalories(event) {
    event.preventDefault();
    let calories = parseInt(document.getElementById('caloriesAmount').value, 10);
    const protein = parseFloat(document.getElementById('proteinAmount').value) || 0;
    const fat = parseFloat(document.getElementById('fatAmount').value) || 0;
    const carbs = parseFloat(document.getElementById('carbsAmount').value) || 0;
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
        body: JSON.stringify({ calories, protein, fat, carbs })
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

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('pl-PL');
}

let weightChart = null;
function loadWeightChart() {
    fetch('api/nutrition.php?action=get_weight_data')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('weightChart');
                if (!ctx) return;
                
                if (weightChart) weightChart.destroy();
                
                weightChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.data.dates,
                        datasets: [{
                            label: 'Waga (kg)',
                            data: data.data.weights,
                            borderColor: 'var(--primary-color)',
                            backgroundColor: 'rgba(0, 229, 255, 0.1)',
                            tension: 0.3,
                            fill: true,
                            pointBackgroundColor: 'var(--primary-color)'
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: true }
                        },
                        scales: {
                            y: {
                                beginAtZero: false,
                                ticks: { color: 'var(--text-muted)' }
                            },
                            x: {
                                ticks: { color: 'var(--text-muted)' }
                            }
                        }
                    }
                });
            }
        });
}

let activityChart = null;
function loadActivityChart() {
    fetch('api/workout.php?action=get_weekly_activity')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const ctx = document.getElementById('activityChart');
                if (!ctx) return;
                
                if (activityChart) activityChart.destroy();
                
                activityChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.data.days,
                        datasets: [{
                            label: 'Treningi',
                            data: data.data.counts,
                            backgroundColor: [
                                'var(--primary-color)',
                                'var(--secondary-color)',
                                'var(--accent-color)',
                                'var(--success-color)',
                                'var(--warning-color)',
                                'var(--error-color)',
                                'var(--primary-color)'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: { color: 'var(--text-muted)' }
                            },
                            x: {
                                ticks: { color: 'var(--text-muted)' }
                            }
                        }
                    }
                });
            }
        });
}

// Aktualizuj wykresy gdy zmieni się motyw
document.addEventListener('themeChanged', function() {
    setTimeout(() => {
        loadWeightChart();
        loadActivityChart();
    }, 100);
});
</script>
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
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text || response.statusText); });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const entry = data.data;
                const consumed = entry.consumed || 0;
                const goal = entry.goal || 2000;
                const progress = Math.min(100, (consumed / goal) * 100);
                document.getElementById('calorieConsumed').textContent = consumed;
                document.getElementById('calorieGoalValue').textContent = goal;
                document.getElementById('calorieProgressFill').style.width = progress + '%';

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
        body: JSON.stringify({ calories, protein, fat, carbs })
    })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => { throw new Error(text || response.statusText); });
            }
            return response.json();
        })
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