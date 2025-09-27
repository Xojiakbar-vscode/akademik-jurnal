// Umumiy JavaScript funksiyalari
document.addEventListener('DOMContentLoaded', function() {
    // Search funksiyasi
    const searchForm = document.querySelector('form[role="search"]');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="q"]');
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                searchInput.focus();
            }
        });
    }

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('is-invalid');
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Iltimos, barcha kerakli maydonlarni to\'ldiring.');
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // PDF preview (agar kerak bo'lsa)
    const pdfInputs = document.querySelectorAll('input[type="file"][accept*="pdf"]');
    pdfInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.type === 'application/pdf') {
                // PDF fayl yuklanganida ishlov berish
                console.log('PDF file selected:', file.name);
            } else if (file) {
                alert('Faqat PDF formatidagi fayllarni yuklash mumkin.');
                this.value = '';
            }
        });
    });

    // Character counter for textareas
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        const counter = document.createElement('div');
        counter.className = 'form-text text-end';
        counter.textContent = `0/${maxLength}`;
        textarea.parentNode.appendChild(counter);

        textarea.addEventListener('input', function() {
            const currentLength = this.value.length;
            counter.textContent = `${currentLength}/${maxLength}`;
            
            if (currentLength > maxLength) {
                counter.classList.add('text-danger');
            } else {
                counter.classList.remove('text-danger');
            }
        });
    });
});

// Til almashish funksiyasi
function changeLanguage(lang) {
    fetch('includes/language.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'lang=' + lang
    }).then(() => {
        window.location.reload();
    });
}

// PDF ko'rish funksiyasi
function viewPDF(url) {
    window.open(url, '_blank');
}

// Maqolani yuklab olish
function downloadArticle(pdfUrl, articleTitle) {
    const link = document.createElement('a');
    link.href = pdfUrl;
    link.download = articleTitle + '.pdf';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Sahifa yuklanishini ko'rsatish
function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loading';
    loading.innerHTML = `
        <div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);z-index:9999;display:flex;align-items:center;justify-content:center;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    document.body.appendChild(loading);
}

function hideLoading() {
    const loading = document.getElementById('loading');
    if (loading) {
        loading.remove();
    }
}

// AJAX so'rovlari uchun umumiy funksiya
function makeRequest(url, method = 'GET', data = null) {
    showLoading();
    
    return fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        body: data ? JSON.stringify(data) : null
    })
    .then(response => {
        hideLoading();
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .catch(error => {
        hideLoading();
        console.error('Request failed:', error);
        throw error;
    });
}