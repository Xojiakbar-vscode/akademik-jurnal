<footer class="bg-dark text-light pt-5 mt-5">
  <div class="container">
    <div class="row gy-4">
      <!-- Site info -->
      <div class="col-lg-4 col-md-6">
        <h5 class="mb-3"><?php echo getTranslation('site_name'); ?></h5>
        <p class="text-white"><?php echo getTranslation('site_description'); ?></p>
        <div class="d-flex gap-3 mt-3">
          <a href="#" class="text-light fs-5"><i class="bi bi-facebook"></i></a>
          <a href="#" class="text-light fs-5"><i class="bi bi-telegram"></i></a>
          <a href="#" class="text-light fs-5"><i class="bi bi-twitter"></i></a>
          <a href="#" class="text-light fs-5"><i class="bi bi-youtube"></i></a>
        </div>
      </div>

      <!-- Pages -->
      <div class="col-lg-2 col-md-6 col-6">
        <h6 class="mb-3">Sahifalar</h6>
        <ul class="list-unstyled text-white">
          <li><a href="index.php" class="text-white text-decoration-none d-block mb-2">Bosh Sahifa</a></li>
          <li><a href="articles.php" class="text-white text-decoration-none d-block mb-2">Maqolalar</a></li>
          <li><a href="issues.php" class="text-white text-decoration-none d-block mb-2">Jurnal Sonlari</a></li>
          <li><a href="contact.php" class="text-white text-decoration-none d-block mb-2">Bog‘lanish</a></li>
        </ul>
      </div>

      <!-- Quick links -->
      <div class="col-lg-3 col-md-6 col-6">
        <h6 class="mb-3">Tezkor Havolalar</h6>
        <ul class="list-unstyled">
          <li><a href="about.php" class="text-white text-decoration-none d-block mb-2">Biz Haqimizda</a></li>
          <li><a href="faq.php" class="text-white text-decoration-none d-block mb-2">Ko‘p So‘raladigan Savollar</a></li>
          <li><a href="privacy.php" class="text-white text-decoration-none d-block mb-2">Maxfiylik Siyosati</a></li>
          <li><a href="terms.php" class="text-white text-decoration-none d-block mb-2">Foydalanish Shartlari</a></li>
        </ul>
      </div>

      <!-- Contact + subscribe -->
      <div class="col-lg-3 col-md-6">
        <h6 class="mb-3">Bog‘lanish</h6>
        <ul class="list-unstyled text-white mb-3">
          <li class="mb-2"><i class="bi bi-envelope me-2"></i> info@akademikjurnal.uz</li>
          <li class="mb-2"><i class="bi bi-telephone me-2"></i> +998 71 123 45 67</li>
          <li><i class="bi bi-geo-alt me-2"></i> Toshkent shahar, Universitet ko‘chasi 4</li>
        </ul>
        <h6 class="mb-2">Obuna Bo‘lish</h6>
        <form class="d-flex">
          <input type="email" class="form-control me-2" placeholder="Email manzilingiz">
          <button type="submit" class="btn btn-primary">Obuna</button>
        </form>
      </div>
    </div>

    <hr class="my-4">

    <div class="row">
      <div class="col-12 text-center">
        <p class="text-white mb-0">
          &copy; <?php echo date('Y'); ?> <?php echo getTranslation('site_name'); ?>. 
          Barcha huquqlar himoyalangan.
        </p>
      </div>
    </div>
  </div>
</footer>
