<nav class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fas fa-dumbbell"></i> Fitness App</h2>
    </div>
    <ul class="sidebar-menu">
        <li><a href="?page=dashboard" class="menu-item <?php echo ($_GET['page'] ?? 'dashboard') === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="?page=calculator" class="menu-item <?php echo ($_GET['page'] ?? '') === 'calculator' ? 'active' : ''; ?>"><i class="fas fa-calculator"></i> Kalkulator Kalorii</a></li>
        <li><a href="?page=onerm" class="menu-item <?php echo ($_GET['page'] ?? '') === 'onerm' ? 'active' : ''; ?>"><i class="fas fa-weight-hanging"></i> Kalkulator 1RM</a></li>
        <li><a href="?page=workouts" class="menu-item <?php echo ($_GET['page'] ?? '') === 'workouts' ? 'active' : ''; ?>"><i class="fas fa-calendar-alt"></i> Dziennik Treningów</a></li>
        <li><a href="?page=plans" class="menu-item <?php echo ($_GET['page'] ?? '') === 'plans' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Plany Treningowe</a></li>
        <li><a href="?page=wheel" class="menu-item <?php echo ($_GET['page'] ?? '') === 'wheel' ? 'active' : ''; ?>"><i class="fas fa-dharmachakra"></i> Koło Fortuny</a></li>
        <li><a href="?page=profile" class="menu-item <?php echo ($_GET['page'] ?? '') === 'profile' ? 'active' : ''; ?>"><i class="fas fa-user"></i> Profil</a></li>
    </ul>
</nav>