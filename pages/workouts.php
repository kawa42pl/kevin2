<div class="container">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-plus"></i>
            <h3>Dodaj Sesję Treningową</h3>
        </div>
        <div class="card-body">
            <form id="workoutForm">
                <div class="form-group">
                    <label for="workoutDate">Data:</label>
                    <input type="date" id="workoutDate" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label for="workoutPlan">Plan treningowy:</label>
                    <input type="text" id="workoutPlan" placeholder="Nazwa planu lub własne">
                </div>
                <div class="form-group">
                    <label for="workoutNote">Notatka:</label>
                    <textarea id="workoutNote" rows="3"></textarea>
                </div>
                <div id="exercisesContainer">
                    <h4>Ćwiczenia</h4>
                    <div class="exercise-item">
                        <input type="text" class="exercise-name" placeholder="Nazwa ćwiczenia" required>
                        <input type="number" class="exercise-sets" placeholder="Serie" min="1" required>
                        <input type="number" class="exercise-reps" placeholder="Powtórzenia" min="1" required>
                        <input type="number" class="exercise-weight" placeholder="Ciężar (kg)" step="0.5" required>
                        <input type="number" class="exercise-rpe" placeholder="RPE (1-10)" min="1" max="10">
                        <button type="button" class="btn btn-danger remove-exercise">Usuń</button>
                    </div>
                </div>
                <button type="button" id="addExercise" class="btn btn-secondary">Dodaj ćwiczenie</button>
                <button type="submit" class="btn btn-primary">Zapisz trening</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-history"></i>
            <h3>Historia Treningów</h3>
        </div>
        <div class="card-body">
            <div class="filters">
                <select id="filterMonth">
                    <option value="">Wszystkie miesiące</option>
                    <?php for ($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>"><?php echo date('F', mktime(0, 0, 0, $i, 1)); ?></option>
                    <?php endfor; ?>
                </select>
                <input type="text" id="filterPlan" placeholder="Filtruj po planie">
            </div>
            <div id="workoutsList">
                <!-- Lista treningów zostanie załadowana JS -->
            </div>
            <div id="stats">
                <!-- Statystyki zostaną załadowane JS -->
            </div>
            <canvas id="workoutsChart"></canvas>
        </div>
    </div>
</div>

<script>
document.getElementById('addExercise').addEventListener('click', function() {
    const container = document.getElementById('exercisesContainer');
    const exerciseItem = document.createElement('div');
    exerciseItem.className = 'exercise-item';
    exerciseItem.innerHTML = `
        <input type="text" class="exercise-name" placeholder="Nazwa ćwiczenia" required>
        <input type="number" class="exercise-sets" placeholder="Serie" min="1" required>
        <input type="number" class="exercise-reps" placeholder="Powtórzenia" min="1" required>
        <input type="number" class="exercise-weight" placeholder="Ciężar (kg)" step="0.5" required>
        <input type="number" class="exercise-rpe" placeholder="RPE (1-10)" min="1" max="10">
        <button type="button" class="btn btn-danger remove-exercise">Usuń</button>
    `;
    container.appendChild(exerciseItem);
    attachRemoveListeners();
});

function attachRemoveListeners() {
    document.querySelectorAll('.remove-exercise').forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.remove();
        });
    });
}

attachRemoveListeners();

document.getElementById('workoutForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const exercises = [];
    document.querySelectorAll('.exercise-item').forEach(item => {
        exercises.push({
            name: item.querySelector('.exercise-name').value,
            sets: parseInt(item.querySelector('.exercise-sets').value),
            reps: parseInt(item.querySelector('.exercise-reps').value),
            weight: parseFloat(item.querySelector('.exercise-weight').value),
            rpe: item.querySelector('.exercise-rpe').value ? parseInt(item.querySelector('.exercise-rpe').value) : null
        });
    });

    const data = {
        date: document.getElementById('workoutDate').value,
        plan: document.getElementById('workoutPlan').value,
        note: document.getElementById('workoutNote').value,
        exercises: exercises
    };

    fetch('api/workout.php?action=save_session', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.success ? 'Trening zapisany!' : data.error, data.success ? 'success' : 'error');
        if (data.success) {
            document.getElementById('workoutForm').reset();
            loadWorkouts();
        }
    });
});

function loadWorkouts() {
    const month = document.getElementById('filterMonth').value;
    const plan = document.getElementById('filterPlan').value;
    
    fetch(`api/workout.php?action=get_sessions&month=${month}&plan=${plan}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayWorkouts(data.data.sessions);
                displayStats(data.data.stats);
                loadWorkoutsChart(data.data.chartData);
            }
        });
}

function displayWorkouts(sessions) {
    const container = document.getElementById('workoutsList');
    container.innerHTML = '';
    if (sessions.length === 0) {
        container.innerHTML = '<p class="empty-state">Brak treningów. <a href="#workoutForm">Dodaj pierwszy trening</a></p>';
        return;
    }
    sessions.forEach(session => {
        const sessionDiv = document.createElement('div');
        sessionDiv.className = 'workout-session';
        sessionDiv.innerHTML = `
            <div class="session-header" onclick="toggleSession(this)">
                <h4>${session.date} - ${session.plan || 'Własny'}</h4>
                <span>${session.exercises.length} ćwiczeń</span>
            </div>
            <div class="session-details" style="display: none;">
                ${session.note ? `<p><strong>Notatka:</strong> ${session.note}</p>` : ''}
                <ul>
                    ${session.exercises.map(ex => `
                        <li>${ex.name}: ${ex.sets}×${ex.reps} @ ${ex.weight}kg ${ex.rpe ? `(RPE: ${ex.rpe})` : ''}</li>
                    `).join('')}
                </ul>
            </div>
        `;
        container.appendChild(sessionDiv);
    });
}

function toggleSession(header) {
    const details = header.nextElementSibling;
    details.style.display = details.style.display === 'none' ? 'block' : 'none';
}

function displayStats(stats) {
    const container = document.getElementById('stats');
    container.innerHTML = `
        <div class="stats-grid">
            <div class="stat-item">
                <h4>Sesji</h4>
                <span>${stats.totalSessions}</span>
            </div>
            <div class="stat-item">
                <h4>Ton (kg)</h4>
                <span>${stats.totalVolume}</span>
            </div>
            <div class="stat-item">
                <h4>Ulubione ćwiczenie</h4>
                <span>${stats.favoriteExercise || 'Brak'}</span>
            </div>
        </div>
    `;
}

function loadWorkoutsChart(chartData) {
    const ctx = document.getElementById('workoutsChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: chartData.weeks,
            datasets: [{
                label: 'Liczba treningów',
                data: chartData.counts,
                backgroundColor: '#00E5FF'
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

document.getElementById('filterMonth').addEventListener('change', loadWorkouts);
document.getElementById('filterPlan').addEventListener('input', loadWorkouts);

document.addEventListener('DOMContentLoaded', loadWorkouts);
</script>