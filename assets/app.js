// Główny plik JavaScript dla aplikacji fitness

// Funkcja do pokazywania/ukrywania sidebar na mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('show');
}

// Funkcja do animacji liczników
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

// Funkcja do pokazywania toast notifications
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => toast.classList.add('show'), 100);
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Funkcja do obsługi błędów AJAX
function handleAjaxError(error) {
    console.error('AJAX Error:', error);
    showToast('Wystąpił błąd podczas przetwarzania żądania', 'error');
}

// Inicjalizacja po załadowaniu DOM
document.addEventListener('DOMContentLoaded', function() {
    // Obsługa user menu dropdown
    const userMenu = document.querySelector('.user-menu');
    if (userMenu) {
        userMenu.addEventListener('click', function(e) {
            if (e.target.closest('.user-dropdown')) return;
            const dropdown = this.querySelector('.user-dropdown');
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });

        // Zamknij dropdown przy kliknięciu poza
        document.addEventListener('click', function(e) {
            if (!userMenu.contains(e.target)) {
                userMenu.querySelector('.user-dropdown').style.display = 'none';
            }
        });
    }

    // Obsługa hamburger menu
    const hamburger = document.querySelector('.hamburger');
    if (hamburger) {
        hamburger.addEventListener('click', toggleSidebar);
    }

    // Zamknij sidebar przy kliknięciu poza na mobile
    document.addEventListener('click', function(e) {
        const sidebar = document.querySelector('.sidebar');
        const hamburger = document.querySelector('.hamburger');
        if (window.innerWidth <= 768 && 
            !sidebar.contains(e.target) && 
            !hamburger.contains(e.target) &&
            sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    });

    // Animacje liczników na dashboard
    const counters = document.querySelectorAll('.animated-counter');
    counters.forEach(counter => {
        const target = parseFloat(counter.textContent.replace(/[^\d.]/g, ''));
        if (!isNaN(target)) {
            animateNumber(counter, 0, target, 1000);
        }
    });

    // Walidacja formularzy
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#f44336';
                    isValid = false;
                } else {
                    field.style.borderColor = '#333333';
                }
            });

            if (!isValid) {
                e.preventDefault();
                showToast('Wypełnij wszystkie wymagane pola', 'error');
            }
        });
    });

    // Obsługa pól liczbowych - tylko dodatnie wartości
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    });

    // Auto-resize dla textarea
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    // Potwierdzenie dla niebezpiecznych akcji
    const dangerButtons = document.querySelectorAll('.btn-danger');
    dangerButtons.forEach(button => {
        if (button.tagName === 'BUTTON' || button.type === 'submit') {
            button.addEventListener('click', function(e) {
                if (!confirm('Czy na pewno chcesz wykonać tę akcję?')) {
                    e.preventDefault();
                }
            });
        }
    });

    // Lazy loading obrazów (jeśli będą)
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));

    // Obsługa błędów JavaScript
    window.addEventListener('error', function(e) {
        console.error('JavaScript Error:', e.error);
        showToast('Wystąpił błąd aplikacji', 'error');
    });

    // Obsługa obietnic (Promise) errors
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled Promise Rejection:', e.reason);
        showToast('Wystąpił błąd aplikacji', 'error');
    });

    console.log('Aplikacja fitness została zainicjalizowana');
});

// Funkcje pomocnicze dla innych modułów
window.FitnessApp = {
    // Funkcja do formatowania daty
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('pl-PL');
    },

    // Funkcja do formatowania liczby z jednostką
    formatNumber: function(number, unit = '') {
        return new Intl.NumberFormat('pl-PL').format(number) + unit;
    },

    // Funkcja do obliczania wieku
    calculateAge: function(birthdate) {
        const today = new Date();
        const birth = new Date(birthdate);
        let age = today.getFullYear() - birth.getFullYear();
        const monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age;
    },

    // Funkcja do walidacji email
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Funkcja do generowania ID
    generateId: function() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
};