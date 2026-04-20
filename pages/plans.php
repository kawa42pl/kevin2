<div class="container">
    <div class="plans-tabs">
        <button class="tab-button active" onclick="showPlan('powerlifting')">🏋️ Trójbój Siłowy</button>
        <button class="tab-button" onclick="showPlan('physique_male')">💪 Sylwetka — Mężczyzna</button>
        <button class="tab-button" onclick="showPlan('physique_female')">👩 Sylwetka — Kobieta</button>
        <button class="tab-button" onclick="showPlan('calisthenics')">🤸 Kalistenika</button>
    </div>

    <div id="planContent" class="card">
        <!-- Zawartość planu zostanie załadowana JS -->
    </div>
</div>

<script>
function showPlan(planType) {
    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');

    fetch(`api/plans.php?plan=${planType}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPlan(data.data);
            }
        });
}

function displayPlan(plan) {
    const container = document.getElementById('planContent');
    container.innerHTML = `
        <div class="card-header">
            <i class="fas fa-clipboard-list"></i>
            <h3>${plan.name}</h3>
        </div>
        <div class="card-body">
            <p>${plan.description}</p>
            <div class="plan-days">
                ${plan.days.map((day, index) => `
                    <div class="plan-day">
                        <h4>Dzień ${index + 1}: ${day.name}</h4>
                        <ul>
                            ${day.exercises.map(ex => `<li>${ex.name}: ${ex.sets}×${ex.reps} ${ex.notes ? `(${ex.notes})` : ''}</li>`).join('')}
                        </ul>
                        <button class="btn btn-primary" onclick="startWorkout('${plan.type}', ${index})">Rozpocznij trening</button>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}

function startWorkout(planType, dayIndex) {
    fetch(`api/plans.php?plan=${planType}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const day = data.data.days[dayIndex];
                const exercises = day.exercises.map(ex => ({
                    name: ex.name,
                    sets: ex.sets,
                    reps: ex.reps,
                    weight: 0, // Użytkownik uzupełni
                    rpe: null
                }));

                const sessionData = {
                    date: new Date().toISOString().split('T')[0],
                    plan: `${data.data.name} - Dzień ${dayIndex + 1}`,
                    note: '',
                    exercises: exercises
                };

                // Przejdź do strony workouts z pre-filled formularzem
                localStorage.setItem('pendingSession', JSON.stringify(sessionData));
                window.location.href = '?page=workouts';
            }
        });
}

// Załaduj domyślny plan
document.addEventListener('DOMContentLoaded', () => showPlan('powerlifting'));

// Jeśli jest pending session z plans, załaduj go
if (localStorage.getItem('pendingSession')) {
    const session = JSON.parse(localStorage.getItem('pendingSession'));
    // Tutaj można pre-fill formularz na stronie workouts
    localStorage.removeItem('pendingSession');
}
</script>