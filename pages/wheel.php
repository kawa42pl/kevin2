<?php
// Koło Fortuny - Cheat Day Roulette

if (!isset($_SESSION['user_id'])) {
    header('Location: ?page=profile');
    exit;
}

// Pobierz dane użytkownika
$userData = json_decode(file_get_contents('data/users.json'), true);
$userKey = array_search($_SESSION['user_id'], array_column($userData, 'id'));
$user = $userData[$userKey] ?? null;

// Nagrody na kole (możliwości cheat day'ów)
$prizes = [
    [
        'name' => 'Cheat Meal',
        'description' => 'Jedno dowolne danie',
        'icon' => '🍔',
        'reward' => 'cheat_meal'
    ],
    [
        'name' => 'Pizza Night',
        'description' => 'Wieczór pizzy!',
        'icon' => '🍕',
        'reward' => 'pizza_night'
    ],
    [
        'name' => 'Deserek',
        'description' => 'Słodycze bez wyrzutów',
        'icon' => '🍰',
        'reward' => 'dessert'
    ],
    [
        'name' => 'Sushi & Roll',
        'description' => 'Festiwal sushi',
        'icon' => '🍣',
        'reward' => 'sushi'
    ],
    [
        'name' => 'Burger Night',
        'description' => 'Burgeropolis',
        'icon' => '🍟',
        'reward' => 'burger_night'
    ],
    [
        'name' => 'Ice Cream',
        'description' => 'Lody do woli',
        'icon' => '🍦',
        'reward' => 'ice_cream'
    ],
    [
        'name' => 'Fast Food',
        'description' => 'Szybki posiłek',
        'icon' => '🌮',
        'reward' => 'fast_food'
    ],
    [
        'name' => 'Snack Day',
        'description' => 'Przekąski bez limitów',
        'icon' => '🍿',
        'reward' => 'snack_day'
    ]
];
?>

<div class="container">
    <div class="wheel-container">
        <h1><i class="fas fa-dharmachakra"></i> Koło Fortuny - Cheat Day</h1>
        <p class="wheel-subtitle">Czy zasługujesz na dzisiaj? Obrócić koło i sprawdź swoją nagrodę!</p>
        
        <div class="wheel-content">
            <div class="wheel-canvas-wrapper">
                <div class="wheel-canvas">
                    <canvas id="wheelCanvas" width="500" height="500"></canvas>
                    <div class="wheel-pointer"></div>
                    <button id="spinButton" class="btn-spin">KRĘĆ<br>KOŁO!</button>
                </div>
            </div>
            
            <div class="wheel-info">
                <h3>Jak to działa?</h3>
                <ul>
                    <li>✓ Możesz kręcić koło raz dziennie</li>
                    <li>✓ Wylosuj swoją nagrodę cheat day</li>
                    <li>✓ Ciesz się bez wyrzutów sumienia!</li>
                    <li>✓ Działa motywacyjnie na treningi</li>
                </ul>
                
                <div class="wheel-actions">
                    <button id="resetButton" class="btn-reset"><i class="fas fa-redo"></i> Reset</button>
                </div>
                
                <div id="resultDiv" class="result-box" style="display: none;">
                    <h3>Gratulacje!</h3>
                    <div class="result-icon" id="resultIcon"></div>
                    <div class="result-name" id="resultName"></div>
                    <div class="result-desc" id="resultDesc"></div>
                    <p class="result-time" id="resultTime"></p>
                </div>
            </div>
        </div>
        
        <div class="history-section">
            <h3><i class="fas fa-history"></i> Historia Wyników</h3>
            <div id="historyList" class="history-list">
                <p class="text-muted">Brak historii. Zacznij od pierwszego krętu!</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Dane nagrody
    const prizes = <?php echo json_encode($prizes); ?>;
    
    // Canvas i kontekst
    const canvas = document.getElementById('wheelCanvas');
    const ctx = canvas.getContext('2d');
    
    // Stan koła
    let isSpinning = false;
    let currentRotation = 0;
    let lastSpinTime = localStorage.getItem('lastWheelSpin');
    let wheelHistory = JSON.parse(localStorage.getItem('wheelHistory') || '[]');
    
    // Funkcje
    function drawWheel() {
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = 200;
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        const sliceAngle = (2 * Math.PI) / prizes.length;
        
        const colors = ['#FF4081', '#7C4DFF', '#00E5FF', '#FF6E40', '#26C6DA', '#AB47BC', '#EF5350', '#29B6F6'];
        
        prizes.forEach((prize, index) => {
            // Rysuj sektor
            ctx.save();
            ctx.translate(centerX, centerY);
            ctx.rotate(sliceAngle * index + currentRotation);
            
            // Tło sektora
            ctx.fillStyle = colors[index % colors.length];
            ctx.beginPath();
            ctx.moveTo(0, 0);
            ctx.arc(0, 0, radius, 0, sliceAngle);
            ctx.closePath();
            ctx.fill();
            
            // Obramowanie
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            // Tekst
            ctx.fillStyle = '#fff';
            ctx.font = 'bold 14px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            const textRadius = radius * 0.65;
            ctx.translate(textRadius, 0);
            ctx.rotate(sliceAngle / 2);
            
            ctx.fillText(prize.icon, 0, 0);
            ctx.font = '12px Arial';
            ctx.fillText(prize.name, 0, 25);
            
            ctx.restore();
        });
        
        // Rysuj koło na środku
        ctx.fillStyle = '#1a1a1a';
        ctx.beginPath();
        ctx.arc(centerX, centerY, 30, 0, 2 * Math.PI);
        ctx.fill();
        
        ctx.strokeStyle = '#00E5FF';
        ctx.lineWidth = 3;
        ctx.stroke();
        
        ctx.fillStyle = '#00E5FF';
        ctx.font = 'bold 20px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText('GO!', centerX, centerY);
    }
    
    function spin() {
        if (isSpinning) return;
        
        // Sprawdź czy można kręcić (raz dziennie)
        const now = new Date();
        const today = now.toDateString();
        const lastDate = lastSpinTime ? new Date(parseInt(lastSpinTime)).toDateString() : null;
        
        if (lastDate === today) {
            showNotification('⏳ Możesz kręcić koło raz dziennie. Spróbuj jutro!', 'warning');
            return;
        }
        
        isSpinning = true;
        
        const spinButton = document.getElementById('spinButton');
        spinButton.disabled = true;
        spinButton.textContent = 'KRĘCĘ...';
        
        const spinCount = Math.floor(Math.random() * 5) + 10; // 10-14 obrotów
        const sliceAngle = (2 * Math.PI) / prizes.length;
        const randomSlice = Math.floor(Math.random() * prizes.length);
        const finalRotation = spinCount * 2 * Math.PI + (sliceAngle * randomSlice);
        
        let startTime = null;
        const duration = 4000; // 4 sekundy
        
        function animate(currentTime) {
            if (!startTime) startTime = currentTime;
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Easing function - powoli przyśpiesza na początku
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            
            currentRotation = finalRotation * easeProgress;
            drawWheel();
            
            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                isSpinning = false;
                showResult(randomSlice);
                spinButton.disabled = false;
                spinButton.textContent = 'KRĘĆ KOŁO!';
                lastSpinTime = Date.now();
                localStorage.setItem('lastWheelSpin', lastSpinTime);
            }
        }
        
        requestAnimationFrame(animate);
    }
    
    function showResult(prizeIndex) {
        const prize = prizes[prizeIndex];
        const resultDiv = document.getElementById('resultDiv');
        const resultIcon = document.getElementById('resultIcon');
        const resultName = document.getElementById('resultName');
        const resultDesc = document.getElementById('resultDesc');
        const resultTime = document.getElementById('resultTime');
        
        resultIcon.textContent = prize.icon;
        resultName.textContent = prize.name;
        resultDesc.textContent = prize.description;
        resultTime.textContent = new Date().toLocaleString('pl-PL');
        
        resultDiv.style.display = 'block';
        resultDiv.classList.add('show-result');
        
        // Dodaj do historii
        addToHistory(prize);
        
        // Animacja
        setTimeout(() => {
            resultDiv.classList.add('celebrate');
        }, 100);
    }
    
    function addToHistory(prize) {
        const now = new Date();
        const entry = {
            name: prize.name,
            icon: prize.icon,
            time: now.toLocaleString('pl-PL'),
            date: now.toDateString()
        };
        
        wheelHistory.unshift(entry);
        if (wheelHistory.length > 10) wheelHistory.pop();
        
        localStorage.setItem('wheelHistory', JSON.stringify(wheelHistory));
        updateHistory();
    }
    
    function updateHistory() {
        const historyList = document.getElementById('historyList');
        
        if (wheelHistory.length === 0) {
            historyList.innerHTML = '<p class="text-muted">Brak historii. Zacznij od pierwszego krętu!</p>';
            return;
        }
        
        let html = '<div class="history-items">';
        wheelHistory.forEach((entry, index) => {
            html += `
                <div class="history-item">
                    <span class="history-icon">${entry.icon}</span>
                    <div class="history-details">
                        <strong>${entry.name}</strong>
                        <span class="history-date">${entry.time}</span>
                    </div>
                    <span class="history-number">#${index + 1}</span>
                </div>
            `;
        });
        html += '</div>';
        
        historyList.innerHTML = html;
    }
    
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
    
    // Event listeners
    document.getElementById('spinButton').addEventListener('click', spin);
    
    // Inicjalizacja
    drawWheel();
    updateHistory();
    
    // Jeśli już dzisiaj kręcił, wyłącz przycisk
    const now = new Date();
    if (lastSpinTime) {
        const lastDate = new Date(parseInt(lastSpinTime)).toDateString();
        const todayDate = now.toDateString();
        if (lastDate === todayDate) {
            document.getElementById('spinButton').disabled = true;
            document.getElementById('spinButton').textContent = '⏳ Jutro!';
        }
    }
</script>
