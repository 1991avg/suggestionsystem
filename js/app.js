// Enhanced Form Handling
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('suggestionForm');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');

    // Drag and drop functionality
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.classList.add('drag-hover');
    }

    function unhighlight() {
        dropZone.classList.remove('drag-hover');
    }

    // Handle file drop
    dropZone.addEventListener('drop', handleDrop, false);
    fileInput.addEventListener('change', handleFileSelect, false);

    function handleDrop(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        handleFiles(files);
    }

    function handleFileSelect(e) {
        const files = e.target.files;
        handleFiles(files);
    }

    function handleFiles(files) {
        if (files.length > 0) {
            const file = files[0];
            updateFilePreview(file);
        }
    }

    function updateFilePreview(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'application/msword'];

        if (file.size > maxSize) {
            showNotification('error', 'حجم الملف يتجاوز 5 ميجابايت');
            fileInput.value = '';
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            showNotification('error', 'نوع الملف غير مدعوم');
            fileInput.value = '';
            return;
        }

        filePreview.innerHTML = `
            <div class="file-info">
                <i class="${getFileIcon(file.type)}"></i>
                <span>${file.name}</span>
                <button type="button" class="remove-file">×</button>
            </div>
        `;

        document.querySelector('.remove-file').addEventListener('click', () => {
            fileInput.value = '';
            filePreview.innerHTML = '';
        });
    }

    function getFileIcon(fileType) {
        switch(fileType) {
            case 'application/pdf':
                return 'fas fa-file-pdf';
            case 'application/msword':
                return 'fas fa-file-word';
            default:
                return 'fas fa-file-image';
        }
    }

    // Form submission with validation and loading state
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (!form.checkValidity()) {
            showNotification('error', 'يرجى تعبئة جميع الحقول المطلوبة');
            return;
        }

        const submitBtn = form.querySelector('.submit-btn');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';

        try {
            const formData = new FormData(form);
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            if (!response.ok) throw new Error('حدث خطأ أثناء الإرسال');

            const result = await response.text();
            showNotification('success', 'تم إرسال اقتراحك بنجاح');
            form.reset();
            filePreview.innerHTML = '';
            refreshSuggestionsList();

        } catch (error) {
            console.error('Error:', error);
            showNotification('error', 'حدث خطأ أثناء إرسال اقتراحك');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> إرسال الاقتراح';
        }
    });

    // Show notifications
    function showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    // Refresh suggestions list
    async function refreshSuggestionsList() {
        const suggestionsList = document.getElementById('suggestionsList');
        suggestionsList.classList.add('loading');

        try {
            const response = await fetch(`${window.location.href}?refresh=true`);
            if (!response.ok) throw new Error('Failed to refresh suggestions');
            
            const html = await response.text();
            suggestionsList.innerHTML = html;
        } catch (error) {
            console.error('Error refreshing suggestions:', error);
        } finally {
            suggestionsList.classList.remove('loading');
        }
    }

    // Smooth scrolling for suggestion cards
    document.querySelector('.feed-content').addEventListener('scroll', function() {
        const cards = document.querySelectorAll('.suggestion-card');
        cards.forEach(card => {
            const rect = card.getBoundingClientRect();
            if (rect.top >= 0 && rect.bottom <= window.innerHeight) {
                card.classList.add('in-view');
            }
        });
    });
});
