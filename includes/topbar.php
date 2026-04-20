<div class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h1><?php
            $pageTitles = [
                'dashboard' => 'Dashboard',
                'calculator' => 'Kalkulator Kalorii',
                'onerm' => 'Kalkulator 1RM',
                'workouts' => 'Dziennik Treningów',
                'plans' => 'Plany Treningowe',
                'profile' => 'Profil'
            ];
            echo $pageTitles[$_GET['page'] ?? 'dashboard'] ?? 'Fitness App';
        ?></h1>
    </div>
    <div class="topbar-right">
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-menu">
                <img src="<?php echo $_SESSION['avatar'] ?? 'assets/default-avatar.png'; ?>" alt="Avatar" class="avatar">
                <span><?php echo $_SESSION['name'] ?? 'Użytkownik'; ?></span>
                <div class="user-dropdown">
                    <a href="?page=profile">Edytuj profil</a>
                    <a href="?action=logout">Wyloguj</a>
                </div>
            </div>
        <?php else: ?>
            <a href="?page=profile" class="btn btn-primary">Zaloguj się</a>
        <?php endif; ?>
    </div>
</div>