<?php
require_once('../includes/auth.php');



// Foydalanuvchi admin emasligini tekshirish
$auth->requireAdmin();

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="bg-navy text-white w-64 min-h-screen p-4 bg-red-400">
    <div class="p-4 mb-6">
        <h2 class="text-xl font-bold"><?php echo getTranslation('admin_panel'); ?></h2>
        <p class="text-sm text-gray-300"><?php echo getTranslation('welcome_message'); ?>, <?php echo $_SESSION['user_name']; ?></p>
    </div>
    
    <nav class="space-y-2">
        <a href="index.php" class="block py-2 px-4 rounded <?php echo $currentPage == 'index.php' ? 'bg-emerald' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-tachometer-alt mr-2"></i> <?php echo getTranslation('dashboard'); ?>
        </a>
        
        <a href="articles.php" class="block py-2 px-4 rounded <?php echo $currentPage == './articles.php' ? 'bg-emerald' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-file-alt mr-2"></i> <?php echo getTranslation('articles'); ?>
        </a>
        
        <a href="users.php" class="block py-2 px-4 rounded <?php echo $currentPage == 'users.php' ? 'bg-emerald' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-users mr-2"></i> <?php echo getTranslation('users'); ?>
        </a>
        
        <a href="categories.php" class="block py-2 px-4 rounded <?php echo $currentPage == 'categories.php' ? 'bg-emerald' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-tags mr-2"></i> <?php echo getTranslation('categories'); ?>
        </a>
        
        <a href="issues.php" class="block py-2 px-4 rounded <?php echo $currentPage == 'issues.php' ? 'bg-emerald' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-book mr-2"></i> <?php echo getTranslation('issues'); ?>
        </a>
        
        <a href="settings.php" class="block py-2 px-4 rounded <?php echo $currentPage == 'settings.php' ? 'bg-emerald' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-cog mr-2"></i> <?php echo getTranslation('settings'); ?>
        </a>
        
        <a href="translations.php" class="block py-2 px-4 rounded <?php echo $currentPage == 'translations.php' ? 'bg-emerald' : 'hover:bg-gray-700'; ?>">
            <i class="fas fa-language mr-2"></i> <?php echo getTranslation('translations'); ?>
        </a>
        
        <a href="logout.php" class="block py-2 px-4 rounded hover:bg-gray-700">
            <i class="fas fa-sign-out-alt mr-2"></i> <?php echo getTranslation('logout'); ?>
        </a>
    </nav>
</div>