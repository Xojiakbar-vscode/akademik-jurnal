<?php 
include 'components/header.php'; 
?>

<!-- Main Content -->
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <h1 class="text-center mb-4"><?php echo getTranslation('scientific_journal_requirements'); ?></h1>
            <p class="lead text-center"><?php echo getTranslation('journal_intro'); ?></p>
            
            <!-- Talablar bo'limi -->
            <div class="mb-4">
                <h2 class="h4"><?php echo getTranslation('article_requirements'); ?></h2>
                <ul class="list-unstyled">
                    <li><strong>1. <?php echo getTranslation('language_requirements'); ?></strong></li>
                    <p><?php echo getTranslation('language_details'); ?></p>

                    <li><strong>2. <?php echo getTranslation('article_format'); ?></strong></li>
                    <p><?php echo getTranslation('article_format_details'); ?></p>

                    <li><strong>3. <?php echo getTranslation('relevant_scientific_topics'); ?></strong></li>
                    <p><?php echo getTranslation('scientific_topic_details'); ?></p>

                    <li><strong>4. <?php echo getTranslation('article_structure'); ?></strong></li>
                    <p><?php echo getTranslation('structure_details'); ?></p>

                    <li><strong>5. <?php echo getTranslation('imrad_format'); ?></strong></li>
                    <p><?php echo getTranslation('imrad_format_details'); ?></p>

                    <li><strong>6. <?php echo getTranslation('abstract1'); ?></strong></li>
                    <p><?php echo getTranslation('abstract_details'); ?></p>

                    <li><strong>7. <?php echo getTranslation('formulas_and_graphs'); ?></strong></li>
                    <p><?php echo getTranslation('formulas_graphs_details'); ?></p>

                    <li><strong>8. <?php echo getTranslation('references1'); ?></strong></li>
                    <p><?php echo getTranslation('references_details'); ?></p>

                    <li><strong>9. <?php echo getTranslation('review_process'); ?></strong></li>
                    <p><?php echo getTranslation('review_process_details'); ?></p>

                    <li><strong>10. <?php echo getTranslation('final_note'); ?></strong></li>
                    <p><?php echo getTranslation('final_note_details'); ?></p>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>
