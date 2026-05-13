<div class="container">
    <div class="card">
        <div class="card-header">
            <i class="fas fa-user"></i>
            <h3>Profile Użytkowników</h3>
        </div>
        <div class="card-body">
            <div id="profilesList">
                <!-- Lista profili zostanie załadowana JS -->
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
                    <input type="number" id="weight" step="0.1" required>
                </div>
                <div class="form-group">
                    <label for="height">Wzrost (cm):</label>
                    <input type="number" id="height" required>
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
function loadProfiles() {
    fetch('api/user.php?action=get_profiles')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProfiles(data.data);
            }
        });
}

function displayProfiles(profiles) {
    const container = document.getElementById('profilesList');
    container.innerHTML = '';
    if (profiles.length === 0) {
        container.innerHTML = '<p class="empty-state">Brak profili. <a href="#" onclick="showProfileForm()">Dodaj pierwszy profil</a></p>';
        return;
    }
    profiles.forEach(profile => {
        const profileDiv = document.createElement('div');
        profileDiv.className = 'profile-item';
        profileDiv.innerHTML = `
            <img src="${profile.avatar || 'assets/default-avatar.png'}" alt="Avatar" class="profile-avatar">
            <div class="profile-info">
                <h4>${profile.name}</h4>
                <p>Wiek: ${calculateAge(profile.birthdate)} | Waga: ${profile.weight}kg | Wzrost: ${profile.height}cm</p>
            </div>
            <div class="profile-actions">
                <button class="btn btn-primary" onclick="switchProfile('${profile.id}')">Przełącz</button>
                <button class="btn btn-secondary" onclick="editProfile('${profile.id}')">Edytuj</button>
                <button class="btn btn-danger" onclick="deleteProfile('${profile.id}')">Usuń</button>
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
    document.getElementById('profileForm').style.display = 'block';
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
    }
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

document.getElementById('addProfile').addEventListener('click', () => showProfileForm());

document.getElementById('cancelEdit').addEventListener('click', () => {
    document.getElementById('profileForm').style.display = 'none';
});

document.getElementById('profilePhoto').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('photoPreview');
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(event) {
            preview.innerHTML = `<img src="${event.target.result}" style="max-width: 150px; border-radius: 8px;">`;
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('userForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
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
                return;
            }
            
            const profileData = {
                id: profileId,
                name: document.getElementById('name').value,
                gender: document.getElementById('gender').value,
                birthdate: document.getElementById('birthdate').value,
                weight: parseFloat(document.getElementById('weight').value),
                height: parseInt(document.getElementById('height').value),
                avatar: uploadData.data.avatar
            };
            
            fetch('api/user.php?action=save_profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(profileData)
            })
            .then(response => response.json())
            .then(data => {
                showToast(data.success ? 'Profil zapisany!' : data.error, data.success ? 'success' : 'error');
                if (data.success) {
                    document.getElementById('profileForm').style.display = 'none';
                    loadProfiles();
                }
            });
        })
        .catch(err => showToast('Błąd przy wgrywaniu zdjęcia: ' + err.message, 'error'));
    } else {
        const data = {
            id: profileId,
            name: document.getElementById('name').value,
            gender: document.getElementById('gender').value,
            birthdate: document.getElementById('birthdate').value,
            weight: parseFloat(document.getElementById('weight').value),
            height: parseInt(document.getElementById('height').value),
            avatar: document.getElementById('avatar').value
        };

        fetch('api/user.php?action=save_profile', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            showToast(data.success ? 'Profil zapisany!' : data.error, data.success ? 'success' : 'error');
            if (data.success) {
                document.getElementById('profileForm').style.display = 'none';
                loadProfiles();
            }
        });
    }
});

function switchProfile(id) {
    fetch(`api/user.php?action=switch_profile&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Profil przełączony!', 'success');
                setTimeout(() => {
                    window.location.href = '?page=dashboard';
                }, 500);
            } else {
                showToast(data.error, 'error');
            }
        });
}

function editProfile(id) {
    fetch(`api/user.php?action=get_profile&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showProfileForm(data.data);
            }
        });
}

function deleteProfile(id) {
    if (confirm('Czy na pewno chcesz usunąć ten profil?')) {
        fetch(`api/user.php?action=delete_profile&id=${id}`)
            .then(response => response.json())
            .then(data => {
                showToast(data.success ? 'Profil usunięty!' : data.error, data.success ? 'success' : 'error');
                if (data.success) loadProfiles();
            })
            .catch(err => showToast('Błąd usuwania profilu', 'error'));
    }
}

function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}

document.addEventListener('DOMContentLoaded', loadProfiles);
</script>