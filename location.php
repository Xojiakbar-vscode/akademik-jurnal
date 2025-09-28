<?php
// Tilni aniqlash
$current_language = $_GET['lang'] ?? 'en';
include_once 'includes/config.php';
include_once 'includes/database.php';
include_once 'includes/functions.php';

// Tillar massivlari
$languages = [
    ['code' => 'en', 'name' => 'English'],
    ['code' => 'uz', 'name' => 'O‘zbekcha'],
    ['code' => 'ru', 'name' => 'Русский'],
];

// Tarjimalar funksiyasi

?>

<?php include 'components/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center text-center">
        <div class="col-lg-8">
            <h2 class="mb-4"><?php echo getTranslation('NamDTU'); ?></h2>
            <p class="mb-4"><?php echo getTranslation('Namangan Davlat Texnika Universiteti'); ?></p>

            <!-- IFRAME MAP -->
            <div class="ratio ratio-16x9 shadow-sm rounded border">
               <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d1605.3856396248486!2d71.6291973507428!3d41.00702520000001!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x38bb4bb764f93db9%3A0x97bcf79c0832687!2sNamanganskiy%20Pedogogichesko-Politekhnicheskiy%20Institut!5e1!3m2!1sen!2s!4v1759078467966!5m2!1sen!2s" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>
