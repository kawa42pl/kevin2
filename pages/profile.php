<div class="container">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user"></i>
            <h3>Profile Użytkowników</h3>
        </div>
        <div class="card-body">
            <div id="profilesList" style="min-height: 100px; display: flex; align-items: center; justify-content: center;">
                <div class="spinner"></div>
            </div>
            <button id="addProfile" class="btn btn-primary">Dodaj nowy profil</button>
        </div>
    </div>

    <div id="profileForm" class="card" style="display: none;">
        <div class="card-header">
            <i class="fas fa-edit"></i>
            <h3 id="formTitle">Dodaj Profil</h3>
        </div>
        <div class="card-body">
            <form id="userForm">
                <input type="hidden" id="profileId">
                <div class="form-group">
                    <label for="name">Imię:</label>
                    <input type="text" id="name" required>
                </div>
                <div class="form-group">
                    <label for="gender">Płeć:</label>
                    <select id="gender" required>
                        <option value="male">Mężczyzna</option>
                        <option value="female">Kobieta</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="birthdate">Data urodzenia:</label>
                    <input type="date" id="birthdate" required>
                </div>
                <div class="form-group">
                    <label for="weight">Waga (kg):</label>
                    <input type="number" id="weight" step="0.1" min="20" max="300" required>
                </div>
                <div class="form-group">
                    <label for="height">Wzrost (cm):</label>
                    <input type="number" id="height" min="120" max="250" required>
                </div>
                <div class="form-group">
                    <label for="avatar">Avatar:</label>
                    <div class="avatar-selection">
                        <div class="avatar-option" data-avatar="avatar1.png"><img src="assets/avatar1.png" alt="Avatar 1"></div>
                        <div class="avatar-option" data-avatar="avatar2.png"><img src="assets/avatar2.png" alt="Avatar 2"></div>
                        <div class="avatar-option" data-avatar="avatar3.png"><img src="assets/avatar3.png" alt="Avatar 3"></div>
                        <div class="avatar-option" data-avatar="avatar4.png"><img src="assets/avatar4.png" alt="Avatar 4"></div>
                    </div>
                    <input type="hidden" id="avatar">
                </div>
                <div class="form-group">
                    <label for="profilePhoto">Załaduj własne zdjęcie profilowe:</label>
                    <input type="file" id="profilePhoto" accept="image/*">
                    <small>Obsługiwane formaty: JPG, PNG, GIF (max 5MB)</small>
                    <div id="photoPreview" style="margin-top: 10px;"></div>
                </div>
                <button type="submit" class="btn btn-primary">Zapisz</button>
                <button type="button" id="cancelEdit" class="btn btn-secondary">Anuluj</button>
            </form>
        </div>
    </div>
</div>

<script>
// Debounce do zapobiegania wielokrotnym kliknięciom
const debounce = (func, wait) => {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
};

// Throttle dla szybkich akcji
function throttle(func, limit) {
    let inThrottle;
    return function(...args) {
        if (!inThrottle) {
            func.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Escape HTML do XSS protection
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Walidacja danych profilu
function validateProfileData() {
    const name = document.getElementById('name').value.trim();
    const birthdate = new Date(document.getElementById('birthdate').value);
    const weight = parseFloat(document.getElementById('weight').value);
    const height = parseInt(document.getElementById('height').value);
    const today = new Date();

    if (!name || name.length < 2) {
        showToast('Imię musi mieć co najmniej 2 znaki', 'error');
        return false;
    }

    if (name.length > 50) {
        showToast('Imię może mieć maksymalnie 50 znaków', 'error');
        return false;
    }

    if (birthdate >= today) {
        showToast('Data urodzenia musi być w przeszłości', 'error');
        return false;
    }

    const age = today.getFullYear() - birthdate.getFullYear();
    if (age < 10 || age > 120) {
        showToast('Wiek musi być między 10 a 120 lat', 'error');
        return false;
    }

    if (weight < 20 || weight > 300) {
        showToast('Waga musi być między 20 a 300 kg', 'error');
        return false;
    }

    if (height < 120 || height > 250) {
        showToast('Wzrost musi być między 120 a 250 cm', 'error');
        return false;
    }

    return true;
}

function setButtonsLoading(loading = true) {
    const buttons = document.querySelectorAll('#profilesList button, #addProfile, #userForm button');
    buttons.forEach(btn => {
        btn.disabled = loading;
        if (loading) {
            btn.style.opacity = '0.6';
        } else {
            btn.style.opacity = '1';
        }
    });
}

function loadProfiles() {
    const container = document.getElementById('profilesList');
    container.innerHTML = '<div class="spinner"></div>';
    
    fetch('api/user.php?action=get_profiles')
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                displayProfiles(data.data);
            } else {
                throw new Error(data.error || 'Błąd ładowania profili');
            }
        })
        .catch(err => {
            console.error('Load profiles error:', err);
            container.innerHTML = `<p class="error-state">Błąd: ${escapeHtml(err.message)}</p>`;
            showToast('Błąd ładowania profili', 'error');
        });
}

function displayProfiles(profiles) {
    const container = document.getElementById('profilesList');
    container.innerHTML = '';
    
    if (profiles.length === 0) {
        container.innerHTML = '<p class="empty-state">Brak profili. <a href="#" onclick="showProfileForm(); return false;">Dodaj pierwszy profil</a></p>';
        return;
    }
    
    profiles.forEach(profile => {
        const profileDiv = document.createElement('div');
        profileDiv.className = 'profile-item';
        const age = calculateAge(profile.birthdate);
        profileDiv.innerHTML = `
            <img src="${escapeHtml(profile.avatar || 'assets/default-avatar.png')}" alt="Avatar" class="profile-avatar" onerror="this.src='assets/default-avatar.png'">
            <div class="profile-info">
                <h4>${escapeHtml(profile.name)}</h4>
                <p>Wiek: ${age} | Waga: ${profile.weight}kg | Wzrost: ${profile.height}cm</p>
            </div>
            <div class="profile-actions">
                <button class="btn btn-primary" onclick="switchProfile('${escapeHtml(profile.id)}')">Przełącz</button>
                <button class="btn btn-secondary" onclick="editProfile('${escapeHtml(profile.id)}')">Edytuj</button>
                <button class="btn btn-danger" onclick="deleteProfile('${escapeHtml(profile.id)}')">Usuń</button>
            </div>
        `;
        container.appendChild(profileDiv);
    });
}

function calculateAge(birthdate) {
    const today = new Date();
    const birth = new Date(birthdate);
    let age = today.getFullYear() - birth.getFullYear();
    const monthDiff = today.getMonth() - birth.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
        age--;
    }
    return age;
}

function showProfileForm(profile = null) {
    const form = document.getElementById('profileForm');
    form.style.display = 'block';
    document.getElementById('formTitle').textContent = profile ? 'Edytuj Profil' : 'Dodaj Profil';
    
    if (profile) {
        document.getElementById('profileId').value = profile.id;
        document.getElementById('name').value = profile.name;
        document.getElementById('gender').value = profile.gender;
        document.getElementById('birthdate').value = profile.birthdate;
        document.getElementById('weight').value = profile.weight;
        document.getElementById('height').value = profile.height;
        document.getElementById('avatar').value = profile.avatar;
        selectAvatar(profile.avatar);
    } else {
        document.getElementById('userForm').reset();
        document.getElementById('profileId').value = '';
        document.getElementById('avatar').value = 'avatar1.png';
        selectAvatar('avatar1.png');
    }
    
    // Scroll do formularza
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function selectAvatar(avatar) {
    document.querySelectorAll('.avatar-option').forEach(opt => {
        opt.classList.remove('selected');
        if (opt.dataset.avatar === avatar) {
            opt.classList.add('selected');
        }
    });
}

document.querySelectorAll('.avatar-option').forEach(opt => {
    opt.addEventListener('click', function() {
        document.querySelectorAll('.avatar-option').forEach(o => o.classList.remove('selected'));
        this.classList.add('selected');
        document.getElementById('avatar').value = this.dataset.avatar;
    });
});

document.getElementById('addProfile').addEventListener('click', () => {
    showProfileForm();
});

document.getElementById('cancelEdit').addEventListener('click', () => {
    document.getElementById('profileForm').style.display = 'none';
});

document.getElementById('profilePhoto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('photoPreview');
    
    if (!file) {
        preview.innerHTML = '';
        return;
    }

    if (!file.type.startsWith('image/')) {
        showToast('Plik musi być obrazkiem', 'error');
        e.target.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        showToast('Plik za duży (max 5MB)', 'error');
        e.target.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = function(event) {
        preview.innerHTML = `<img src="${event.target.result}" style="max-width: 150px; border-radius: 8px;">`;
    };
    reader.readAsDataURL(file);
});

document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();

    if (!validateProfileData()) {
        return;
    }

    setButtonsLoading(true);
    
    const profileId = document.getElementById('profileId').value || generateId();
    const fileInput = document.getElementById('profilePhoto');
    const file = fileInput.files[0];
    
    if (file) {
        const formData = new FormData();
        formData.append('id', profileId);
        formData.append('file', file);
        
        fetch('api/user.php?action=upload_avatar', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(uploadData => {
            if (!uploadData.success) {
                showToast(uploadData.error || 'Błąd przy wgrywaniu zdjęcia', 'error');
                setButtonsLoading(false);
                return;
            }
            
            saveProfile(profileId, uploadData.data.avatar);
        })
        .catch(err => {
            showToast('Błąd przy wgrywaniu zdjęcia: ' + err.message, 'error');
            setButtonsLoading(false);
        });
    } else {
        saveProfile(profileId, document.getElementById('avatar').value);
    }
});

function saveProfile(profileId, avatar) {
    const data = {
        id: profileId,
        name: document.getElementById('name').value.trim(),
        gender: document.getElementById('gender').value,
        birthdate: document.getElementById('birthdate').value,
        weight: parseFloat(document.getElementById('weight').value),
        height: parseInt(document.getElementById('height').value),
        avatar: avatar
    };

    fetch('api/user.php?action=save_profile', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        setButtonsLoading(false);
        if (data.success) {
            showToast('Profil zapisany!', 'success');
            document.getElementById('profileForm').style.display = 'none';
            loadProfiles();
        } else {
            showToast(data.error || 'Błąd zapisu profilu', 'error');
        }
    })
    .catch(err => {
        setButtonsLoading(false);
        showToast('Błąd: ' + err.message, 'error');
    });
}

const switchProfile = throttle(function(id) {
    setButtonsLoading(true);
    fetch(`api/user.php?action=switch_profile&id=${encodeURIComponent(id)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Profil przełączony!', 'success');
                setTimeout(() => {
                    window.location.href = '?page=dashboard';
                }, 500);
            } else {
                showToast(data.error || 'Błąd przełączania profilu', 'error');
                setButtonsLoading(false);
            }
        })
        .catch(err => {
            showToast('Błąd: ' + err.message, 'error');
            setButtonsLoading(false);
        });
}, 1000);

function editProfile(id) {
    setButtonsLoading(true);
    fetch(`api/user.php?action=get_profile&id=${encodeURIComponent(id)}`)
        .then(response => response.json())
        .then(data => {
            setButtonsLoading(false);
            if (data.success) {
                showProfileForm(data.data);
            } else {
                showToast(data.error || 'Błąd ładowania profilu', 'error');
            }
        })
        .catch(err => {
            setButtonsLoading(false);
            showToast('Błąd: ' + err.message, 'error');
        });
}

const deleteProfile = throttle(function(id) {
    if (confirm('Czy na pewno chcesz usunąć ten profil? Ta operacja jest nieodwracalna.')) {
        setButtonsLoading(true);
        fetch(`api/user.php?action=delete_profile&id=${encodeURIComponent(id)}`)
            .then(response => response.json())
            .then(data => {
                setButtonsLoading(false);
                if (data.success) {
                    showToast('Profil usunięty!', 'success');
                    loadProfiles();
                } else {
                    showToast(data.error || 'Błąd usuwania profilu', 'error');
                }
            })
            .catch(err => {
                setButtonsLoading(false);
                showToast('Błąd: ' + err.message, 'error');
            });
    }
}, 1000);

function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

document.addEventListener('DOMContentLoaded', loadProfiles);
</script>