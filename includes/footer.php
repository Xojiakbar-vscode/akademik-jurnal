 
    <!-- Footer -->
    <footer class="bg-navy text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-8">
                <div>
                    <h3 class="text-xl font-playfair font-bold mb-4">Akademik Jurnal</h3>
                    <p class="mb-4">Turli fanlar sohasidagi ilmiy tadqiqotlar va innovatsion kashfiyotlarni nashr etish.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-white bg-opacity-10 flex items-center justify-center hover:bg-emerald transition"><i class="fab fa-telegram"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white bg-opacity-10 flex items-center justify-center hover:bg-emerald transition"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white bg-opacity-10 flex items-center justify-center hover:bg-emerald transition"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="w-10 h-10 rounded-full bg-white bg-opacity-10 flex items-center justify-center hover:bg-emerald transition"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-xl font-playfair font-bold mb-4">Tezkor Havolalar</h3>
                    <ul class="space-y-2">
                        <li><a href="/" class="hover:text-emerald transition">Bosh Sahifa</a></li>
                        <li><a href="/archives.php" class="hover:text-emerald transition">Arxiv</a></li>
                        <li><a href="/authors.php" class="hover:text-emerald transition">Mualliflar</a></li>
                        <li><a href="/articles.php" class="hover:text-emerald transition">Maqolalar</a></li>
                        <li><a href="/editorial.php" class="hover:text-emerald transition">Tahririyat Kengashi</a></li>
                        <li><a href="/contact.php" class="hover:text-emerald transition">Bog'lanish</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-xl font-playfair font-bold mb-4">Bog'lanish Ma'lumotlari</h3>
                    <div class="space-y-2">
                        <p class="flex items-start"><i class="fas fa-map-marker-alt mr-3 mt-1"></i> 100174, Toshkent shahar, Universitet ko'chasi, 4</p>
                        <p class="flex items-start"><i class="fas fa-phone mr-3 mt-1"></i> +998 (71) 123-45-67</p>
                        <p class="flex items-start"><i class="fas fa-envelope mr-3 mt-1"></i> info@akademikjurnal.uz</p>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-xl font-playfair font-bold mb-4">Obuna Bo'lish</h3>
                    <p class="mb-4">Yangiliklar va so'nggi maqolalardan xabardor bo'lish uchun elektron pochtangizni kiriting.</p>
                    <form class="flex" action="/subscribe.php" method="POST">
                        <input type="email" name="email" placeholder="Email manzilingiz" class="px-4 py-2 rounded-l w-full text-gray-800" required>
                        <button type="submit" class="bg-emerald hover:bg-green-700 px-4 py-2 rounded-r"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            </div>
            
            <div class="pt-8 border-t border-white border-opacity-10 text-center">
                <p>&copy; 2023 Akademik Tadqiqotlar Jurnali. Barcha huquqlar himoyalangan.</p>
            </div>
        </div>
    </footer>

    <script src="/js/script.js"></script>
</body>
</html>