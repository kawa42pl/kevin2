// System tematów aplikacji
const THEMES = {
    dark: {
        name: 'Ciemny (Dark)',
        primary: '#00E5FF',
        secondary: '#7C4DFF',
        accent: '#FF4081',
        darkBg: '#0a0a0a',
        darkerBg: '#050505',
        cardBg: '#1a1a1a',
        textColor: '#ffffff',
        textMuted: '#cccccc',
        borderColor: '#333333'
    },
    light: {
        name: 'Jasny (Light)',
        primary: '#2196F3',
        secondary: '#6200EA',
        accent: '#FF006E',
        darkBg: '#ffffff',
        darkerBg: '#f5f5f5',
        cardBg: '#f9f9f9',
        textColor: '#212121',
        textMuted: '#666666',
        borderColor: '#e0e0e0'
    },
    neon: {
        name: 'Neon',
        primary: '#00FF00',
        secondary: '#FF00FF',
        accent: '#00FFFF',
        darkBg: '#0a0a0a',
        darkerBg: '#050505',
        cardBg: '#1a1a1a',
        textColor: '#00FF00',
        textMuted: '#00CC00',
        borderColor: '#00FF00'
    },
    ocean: {
        name: 'Ocean',
        primary: '#00D4FF',
        secondary: '#006DB3',
        accent: '#00A8E8',
        darkBg: '#0B3D5C',
        darkerBg: '#061F2D',
        cardBg: '#0D5278',
        textColor: '#E0F7FF',
        textMuted: '#A8D5E2',
        borderColor: '#00D4FF'
    },
    sunset: {
        name: 'Sunset',
        primary: '#FF6B35',
        secondary: '#F7931E',
        accent: '#FDB833',
        darkBg: '#1A1A1A',
        darkerBg: '#0F0F0F',
        cardBg: '#2D2D2D',
        textColor: '#FFFFFF',
        textMuted: '#FFD4A3',
        borderColor: '#FF6B35'
    },
    forest: {
        name: 'Forest',
        primary: '#2DD4BF',
        secondary: '#059669',
        accent: '#34D399',
        darkBg: '#0F2F1F',
        darkerBg: '#071C10',
        cardBg: '#1A3A2A',
        textColor: '#E0F9F7',
        textMuted: '#86EFAC',
        borderColor: '#2DD4BF'
    }
};

class ThemeManager {
    constructor() {
        this.currentTheme = this.loadTheme();
        this.applyTheme(this.currentTheme);
    }

    loadTheme() {
        const saved = localStorage.getItem('selectedTheme');
        return saved || 'dark';
    }

    saveTheme(themeName) {
        localStorage.setItem('selectedTheme', themeName);
    }

    applyTheme(themeName) {
        const theme = THEMES[themeName];
        if (!theme) return;

        const root = document.documentElement;
        root.style.setProperty('--primary-color', theme.primary);
        root.style.setProperty('--secondary-color', theme.secondary);
        root.style.setProperty('--accent-color', theme.accent);
        root.style.setProperty('--dark-bg', theme.darkBg);
        root.style.setProperty('--darker-bg', theme.darkerBg);
        root.style.setProperty('--card-bg', theme.cardBg);
        root.style.setProperty('--text-color', theme.textColor);
        root.style.setProperty('--text-muted', theme.textMuted);
        root.style.setProperty('--border-color', theme.borderColor);

        this.currentTheme = themeName;
        this.saveTheme(themeName);

        // Emit event aby inne komponenty wiedzą o zmianie
        document.dispatchEvent(new CustomEvent('themeChanged', { detail: { theme: themeName } }));
    }

    toggleTheme() {
        const themeNames = Object.keys(THEMES);
        const currentIndex = themeNames.indexOf(this.currentTheme);
        const nextIndex = (currentIndex + 1) % themeNames.length;
        this.applyTheme(themeNames[nextIndex]);
    }

    getCurrentTheme() {
        return this.currentTheme;
    }

    getThemeColor(colorName) {
        return THEMES[this.currentTheme][colorName];
    }
}

// Inicjalizacja managera
const themeManager = new ThemeManager();

// Inicjalizuj po załadowaniu DOM
document.addEventListener('DOMContentLoaded', function() {
    setupThemeSwitcher();
});

function setupThemeSwitcher() {
    // Szukaj przycisku theme switchera w topbar
    let themeButton = document.getElementById('themeToggle');
    
    if (!themeButton) {
        // Jeśli go nie ma, spróbuj znaleźć topbar-right
        const topbarRight = document.querySelector('.topbar-right');
        if (topbarRight) {
            themeButton = document.createElement('div');
            themeButton.id = 'themeToggle';
            themeButton.style.cssText = 'margin: 0 15px; cursor: pointer; font-size: 1.2rem;';
            themeButton.innerHTML = '<i class="fas fa-palette"></i>';
            themeButton.title = 'Zmień motyw';
            topbarRight.insertBefore(themeButton, topbarRight.firstChild);
        }
    }

    if (themeButton) {
        // Menu tematów
        const themeMenu = document.createElement('div');
        themeMenu.id = 'themeMenu';
        themeMenu.style.cssText = `
            position: absolute;
            top: 70px;
            right: 10px;
            background-color: var(--darker-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 10px;
            display: none;
            z-index: 2000;
            min-width: 200px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        `;
        
        Object.entries(THEMES).forEach(([key, value]) => {
            const option = document.createElement('div');
            option.style.cssText = `
                padding: 10px;
                cursor: pointer;
                border-radius: 4px;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 5px;
            `;
            
            option.innerHTML = `
                <div style="width: 20px; height: 20px; background: ${value.primary}; border-radius: 50%; border: ${themeManager.currentTheme === key ? '2px solid white' : 'none'};"></div>
                <span>${value.name}</span>
            `;
            
            option.addEventListener('click', () => {
                themeManager.applyTheme(key);
                document.getElementById('themeMenu').style.display = 'none';
                updateThemeMenuStatus();
                showToast(`Zmieniono motyw na ${value.name}`, 'success');
            });
            
            option.addEventListener('mouseover', () => {
                option.style.backgroundColor = 'var(--card-bg)';
            });
            
            option.addEventListener('mouseout', () => {
                option.style.backgroundColor = 'transparent';
            });
            
            themeMenu.appendChild(option);
        });
        
        document.body.appendChild(themeMenu);
        
        themeButton.addEventListener('click', (e) => {
            e.stopPropagation();
            themeMenu.style.display = themeMenu.style.display === 'none' ? 'block' : 'none';
        });

        function updateThemeMenuStatus() {
            document.querySelectorAll('#themeMenu > div').forEach((div, idx) => {
                const themeKey = Object.keys(THEMES)[idx];
                if (themeManager.currentTheme === themeKey) {
                    div.style.backgroundColor = 'var(--card-bg)';
                    div.style.borderLeft = '3px solid var(--primary-color)';
                    div.style.paddingLeft = '7px';
                } else {
                    div.style.backgroundColor = 'transparent';
                    div.style.borderLeft = 'none';
                    div.style.paddingLeft = '10px';
                }
            });
        }
        updateThemeMenuStatus();

        // Zamknij menu przy kliknięciu poza
        document.addEventListener('click', (e) => {
            if (!themeButton.contains(e.target) && !themeMenu.contains(e.target)) {
                themeMenu.style.display = 'none';
            }
        });
    }
}
