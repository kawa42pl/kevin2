<div class="container">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-calculator"></i>
            <h3>Kalkulator Kalorii</h3>
        </div>
        <div class="card-body">
            <?php
            $profile = $_SESSION['user_data'] ?? null;
            $profileAge = $profile ? calculateAge($profile['birthdate']) : '';
            ?>
            <form id="calorieForm">
                <div class="form-group">
                    <label for="weight">Masa ciała (kg):</label>
                    <input type="number" id="weight" step="0.1" required value="<?php echo $profile['weight'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="height">Wzrost (cm):</label>
                    <input type="number" id="height" required value="<?php echo $profile['height'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="age">Wiek:</label>
                    <input type="number" id="age" required value="<?php echo $profileAge; ?>">
                </div>
                <div class="form-group">
                    <label for="gender">Płeć:</label>
                    <select id="gender" required>
                        <option value="male"<?php echo (isset($profile['gender']) && $profile['gender'] === 'male') ? ' selected' : ''; ?>>Mężczyzna</option>
                        <option value="female"<?php echo (isset($profile['gender']) && $profile['gender'] === 'female') ? ' selected' : ''; ?>>Kobieta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="activity">Poziom aktywności:</label>
                    <select id="activity" required>
                        <option value="sedentary">Siedzący (×1.2)</option>
                        <option value="light">Lekko aktywny (×1.375)</option>
                        <option value="moderate">Umiarkowanie aktywny (×1.55)</option>
                        <option value="active">Bardzo aktywny (×1.725)</option>
                        <option value="very_active">Ekstremalnie aktywny (×1.9)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="goal">Cel:</label>
                    <select id="goal" required>
                        <option value="maintenance">Utrzymanie</option>
                        <option value="reduction">Redukcja (-500 kcal)</option>
                        <option value="mass">Masa (+300 kcal)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Oblicz</button>
            </form>
        </div>
    </div>

    <div id="results" class="card" style="display: none;">
        <div class="card-header">
            <i class="fas fa-chart-pie"></i>
            <h3>Wyniki</h3>
        </div>
        <div class="card-body">
            <div class="results-grid">
                <div class="result-item">
                    <h4>BMR</h4>
                    <span id="bmr" class="animated-counter">0</span> kcal
                </div>
                <div class="result-item">
                    <h4>TDEE</h4>
                    <span id="tdee" class="animated-counter">0</span> kcal
                </div>
                <div class="result-item">
                    <h4>Cel kaloryczny</h4>
                    <span id="calorieGoal" class="animated-counter">0</span> kcal
                </div>
            </div>
            <div class="macros-section">
                <h4>Makroskładniki</h4>
                <div class="macros-grid">
                    <div class="macro-item">
                        <h5>Białko</h5>
                        <span id="protein">0g</span> (<span id="proteinPct">0%</span>)
                    </div>
                    <div class="macro-item">
                        <h5>Tłuszcze</h5>
                        <span id="fat">0g</span> (<span id="fatPct">0%</span>)
                    </div>
                    <div class="macro-item">
                        <h5>Węglowodany</h5>
                        <span id="carbs">0g</span> (<span id="carbsPct">0%</span>)
                    </div>
                </div>
                <canvas id="macrosChart"></canvas>
            </div>
            <button id="saveGoal" class="btn btn-success">Zapisz jako dzienny cel</button>
        </div>
    </div>
</div>

<script>
document.getElementById('calorieForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const weight = parseFloat(document.getElementById('weight').value);
    const height = parseFloat(document.getElementById('height').value);
    const age = parseInt(document.getElementById('age').value);
    const gender = document.getElementById('gender').value;
    const activity = document.getElementById('activity').value;
    const goal = document.getElementById('goal').value;

    fetch('api/nutrition.php?action=calculate', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ weight, height, age, gender, activity, goal })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayResults(data.data);
            document.getElementById('results').style.display = 'block';
        } else {
            showToast(data.error, 'error');
        }
    });
});

function displayResults(data) {
    animateCounter('bmr', data.bmr);
    animateCounter('tdee', data.tdee);
    animateCounter('calorieGoal', data.calorieGoal);

    document.getElementById('protein').textContent = data.macros.protein + 'g';
    document.getElementById('proteinPct').textContent = data.macros.protein_pct + '%';
    document.getElementById('fat').textContent = data.macros.fat + 'g';
    document.getElementById('fatPct').textContent = data.macros.fat_pct + '%';
    document.getElementById('carbs').textContent = data.macros.carbs + 'g';
    document.getElementById('carbsPct').textContent = data.macros.carbs_pct + '%';

    loadMacrosChart(data.macros);
}

function animateCounter(id, target) {
    const element = document.getElementById(id);
    animateNumber(element, 0, Math.round(target), 1000);
}

function loadMacrosChart(macros) {
    const ctx = document.getElementById('macrosChart').getContext('2d');
    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Białko', 'Tłuszcze', 'Węglowodany'],
            datasets: [{
                data: [macros.protein_pct, macros.fat_pct, macros.carbs_pct],
                backgroundColor: ['#00E5FF', '#7C4DFF', '#FF4081']
            }]
        },
        options: {
            responsive: true
        }
    });
}

document.getElementById('saveGoal').addEventListener('click', function() {
    const calorieGoal = document.getElementById('calorieGoal').textContent;
    fetch('api/nutrition.php?action=save_goal', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ goal: parseInt(calorieGoal) })
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.success ? 'Cel zapisany!' : data.error, data.success ? 'success' : 'error');
    });
});
</script>