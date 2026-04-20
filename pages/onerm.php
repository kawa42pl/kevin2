<div class="container">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-weight-hanging"></i>
            <h3>Kalkulator 1RM</h3>
        </div>
        <div class="card-body">
            <form id="onermForm">
                <div class="form-group">
                    <label for="exercise">Ćwiczenie:</label>
                    <select id="exercise" required>
                        <option value="Przysiad">Przysiad</option>
                        <option value="Martwy ciąg">Martwy ciąg</option>
                        <option value="Wyciskanie sztangi">Wyciskanie sztangi</option>
                        <option value="Wyciskanie żołnierskie">Wyciskanie żołnierskie</option>
                        <option value="Wiosłowanie sztangą">Wiosłowanie sztangą</option>
                        <option value="Podciąganie">Podciąganie</option>
                        <option value="Pompki obciążone">Pompki obciążone</option>
                        <option value="Dipy obciążone">Dipy obciążone</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="weight">Ciężar (kg):</label>
                    <input type="number" id="weight" step="0.5" required>
                </div>
                <div class="form-group">
                    <label for="reps">Powtórzenia:</label>
                    <input type="number" id="reps" min="1" max="20" required>
                </div>
                <button type="submit" class="btn btn-primary">Oblicz 1RM</button>
            </form>
        </div>
    </div>

    <div id="onermResults" class="card" style="display: none;">
        <div class="card-header">
            <i class="fas fa-trophy"></i>
            <h3>Wyniki 1RM</h3>
        </div>
        <div class="card-body">
            <div class="onerm-formulas">
                <div class="formula-result">
                    <h4>Epley</h4>
                    <span id="epley" class="animated-counter">0</span> kg
                </div>
                <div class="formula-result">
                    <h4>Brzycki</h4>
                    <span id="brzycki" class="animated-counter">0</span> kg
                </div>
                <div class="formula-result">
                    <h4>Lander</h4>
                    <span id="lander" class="animated-counter">0</span> kg
                </div>
            </div>
            <div class="percentages-table">
                <h4>Obciążenia procentowe</h4>
                <table>
                    <thead>
                        <tr>
                            <th>% 1RM</th>
                            <th>Ciężar (kg)</th>
                        </tr>
                    </thead>
                    <tbody id="percentagesBody">
                    </tbody>
                </table>
            </div>
            <button id="saveRecord" class="btn btn-success">Zapisz rekord</button>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-history"></i>
            <h3>Historia Rekordów</h3>
        </div>
        <div class="card-body">
            <div id="recordsHistory">
                <!-- Historia zostanie załadowana JS -->
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('onermForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const weight = parseFloat(document.getElementById('weight').value);
    const reps = parseInt(document.getElementById('reps').value);

    fetch('api/workout.php?action=calculate_onerm', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ weight, reps })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayOnermResults(data.data);
            document.getElementById('onermResults').style.display = 'block';
        } else {
            showToast(data.error, 'error');
        }
    });
});

function displayOnermResults(data) {
    animateCounter('epley', data.epley);
    animateCounter('brzycki', data.brzycki);
    animateCounter('lander', data.lander);

    const tbody = document.getElementById('percentagesBody');
    tbody.innerHTML = '';
    for (const [pct, weight] of Object.entries(data.percentages)) {
        tbody.innerHTML += `<tr><td>${pct}%</td><td>${weight} kg</td></tr>`;
    }
}

document.getElementById('saveRecord').addEventListener('click', function() {
    const exercise = document.getElementById('exercise').value;
    const weight = parseFloat(document.getElementById('epley').textContent); // Używamy Epley jako głównego

    fetch('api/workout.php?action=save_onerm_record', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ exercise, weight })
    })
    .then(response => response.json())
    .then(data => {
        showToast(data.success ? 'Rekord zapisany!' : data.error, data.success ? 'success' : 'error');
        if (data.success) loadRecordsHistory();
    });
});

function loadRecordsHistory() {
    fetch('api/workout.php?action=get_onerm_records')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('recordsHistory');
                container.innerHTML = '';
                for (const [exercise, records] of Object.entries(data.data)) {
                    container.innerHTML += `<h4>${exercise}</h4>`;
                    const canvas = document.createElement('canvas');
                    canvas.id = `chart-${exercise}`;
                    container.appendChild(canvas);
                    loadRecordChart(exercise, records);
                }
            }
        });
}

function loadRecordChart(exercise, records) {
    const ctx = document.getElementById(`chart-${exercise}`).getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: records.map(r => r.date),
            datasets: [{
                label: '1RM (kg)',
                data: records.map(r => r.weight),
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

document.addEventListener('DOMContentLoaded', loadRecordsHistory);
</script>