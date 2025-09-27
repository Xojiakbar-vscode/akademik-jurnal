<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/header.php';

// Tahririyat kengashi a'zolarini olish
$editors = $pdo->query("SELECT u.*, p.position, p.order_index 
                       FROM users u 
                       JOIN editorial_positions p ON u.id = p.user_id 
                       WHERE u.role = 'editor' 
                       ORDER BY p.order_index")->fetchAll();
?>

<div class="container mx-auto px-4 py-12">
    <div class="text-center mb-12">
        <h2 class="text-3xl font-playfair font-bold mb-4 relative after:content-[''] after:block after:w-16 after:h-1 after:bg-emerald after:mx-auto after:mt-4 dark:text-white">Tahririyat Kengashi</h2>
        <p class="text-gray-700 dark:text-gray-300 max-w-2xl mx-auto">Jurnalimizning tahririyat kengashi taniqli olimlar va mutaxassislardan iborat bo'lib, ular maqolalarni baholash va nashrga tayyorlashda muhim rol o'ynaydilar.</p>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
        <?php foreach ($editors as $editor): ?>
        <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow text-center">
            <div class="w-32 h-32 rounded-full bg-gray-300 mx-auto mb-4 bg-cover bg-center" style="background-image: url('<?php echo $editor['profile_image'] ? 'uploads/' . $editor['profile_image'] : 'https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1374&q=80'; ?>');"></div>
            <h3 class="text-xl font-playfair font-bold mb-2 dark:text-white"><?php echo htmlspecialchars($editor['name']); ?></h3>
            <div class="text-emerald font-medium mb-4"><?php echo htmlspecialchars($editor['position']); ?></div>
            <p class="text-gray-700 dark:text-gray-300 mb-4"><?php echo htmlspecialchars($editor['affiliation']); ?></p>
            <p class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($editor['bio']); ?></p>
            
            <?php if ($editor['orcid_id']): ?>
            <div class="mt-4">
                <a href="https://orcid.org/<?php echo $editor['orcid_id']; ?>" target="_blank" class="inline-flex items-center text-blue-600 hover:text-blue-800">
                    <i class="fab fa-orcid mr-2"></i> ORCID Profile
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>