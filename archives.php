<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/header.php';

// Jurnal sonlarini olish
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;
$offset = ($page - 1) * $limit;

// Jami sonlar soni
$totalIssues = $pdo->query("SELECT COUNT(*) FROM issues")->fetchColumn();

// Sonlarni olish
$issues = $pdo->query("SELECT * FROM issues ORDER BY created_at DESC LIMIT $limit OFFSET $offset")->fetchAll();

// Sahifalar soni
$totalPages = ceil($totalIssues / $limit);
?>

<div class="container mx-auto px-4 py-12">
    <div class="text-center mb-12">
        <h2 class="text-3xl font-playfair font-bold mb-4 relative after:content-[''] after:block after:w-16 after:h-1 after:bg-emerald after:mx-auto after:mt-4 dark:text-white">Jurnal Arxivlari</h2>
        <p class="text-gray-700 dark:text-gray-300 max-w-2xl mx-auto">Jurnalimizning so'nggi va avvalgi sonlarini ko'rib chiqing. Har bir son o'zining muhim tadqiqotlari va ilmiy maqolalari bilan ajralib turadi.</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <?php foreach ($issues as $issue): ?>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow">
            <h3 class="text-xl font-playfair font-bold mb-2 dark:text-white"><?php echo htmlspecialchars($issue['title']); ?></h3>
            <div class="text-gray-500 dark:text-gray-400 text-sm mb-4"><?php echo date('F Y', strtotime($issue['issue_date'])); ?></div>
            <p class="text-gray-700 dark:text-gray-300 mb-6"><?php echo mb_substr(strip_tags($issue['description']), 0, 150); ?>...</p>
            <a href="issue.php?id=<?php echo $issue['id']; ?>" class="bg-emerald hover:bg-green-700 text-white px-4 py-2 rounded text-sm inline-block">Sonni Ko'rish</a>
        </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center space-x-2">
            <?php if ($page > 1): ?>
            <a href="archives.php?page=<?php echo $page - 1; ?>" class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="fas fa-chevron-left"></i>
            </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="archives.php?page=<?php echo $i; ?>" class="px-3 py-1 rounded border <?php echo $i == $page ? 'border-emerald bg-emerald text-white' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'; ?>">
                <?php echo $i; ?>
            </a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="archives.php?page=<?php echo $page + 1; ?>" class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                <i class="fas fa-chevron-right"></i>
            </a>
            <?php endif; ?>
        </nav>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>