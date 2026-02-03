class PublishMetaBox {
    constructor() {
        this.init();
    }

    init() {
        this.cacheDOM();
        this.bindEvents();
    }

    cacheDOM() {
        this.toggleBtn = document.getElementById('publish-toggle-btn');
        this.body = document.getElementById('publish-body');

        // Buttons
        this.saveDraftBtn = document.getElementById('btn-save-draft');
        this.previewBtn = document.getElementById('btn-preview');
        this.publishBtn = document.getElementById('btn-publish');
        this.trashBtn = document.getElementById('btn-trash');

        // Edit Links
        this.editStatusLink = document.getElementById('edit-status-link');
        this.editVisibilityLink = document.getElementById('edit-visibility-link');
        this.editDateLink = document.getElementById('edit-date-link');

        // Panels
        this.statusPanel = document.getElementById('status-edit-panel');
        this.visibilityPanel = document.getElementById('visibility-edit-panel');
        this.datePanel = document.getElementById('date-edit-panel');

        // Cancel Buttons
        this.cancelStatus = document.getElementById('cancel-status');
        this.cancelVisibility = document.getElementById('cancel-visibility');
        this.cancelDate = document.getElementById('cancel-date');

        // OK Buttons
        this.okStatus = document.getElementById('ok-status');
        this.okVisibility = document.getElementById('ok-visibility');
        this.okDate = document.getElementById('ok-date');

        // Displays
        this.displayStatus = document.getElementById('display-status');
        this.displayVisibility = document.getElementById('display-visibility');
        this.displayDate = document.getElementById('display-date');
    }

    bindEvents() {
        // Toggle Collapse/Expand
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => this.toggleCard());
        }

        // Edit Toggles
        if (this.editStatusLink) {
            this.editStatusLink.addEventListener('click', (e) => this.togglePanel(e, this.statusPanel));
        }
        if (this.editVisibilityLink) {
            this.editVisibilityLink.addEventListener('click', (e) => this.togglePanel(e, this.visibilityPanel));
        }
        if (this.editDateLink) {
            this.editDateLink.addEventListener('click', (e) => this.togglePanel(e, this.datePanel));
        }

        // Cancel Actions
        if (this.cancelStatus) this.cancelStatus.addEventListener('click', (e) => this.closePanel(e, this.statusPanel));
        if (this.cancelVisibility) this.cancelVisibility.addEventListener('click', (e) => this.closePanel(e, this.visibilityPanel));
        if (this.cancelDate) this.cancelDate.addEventListener('click', (e) => this.closePanel(e, this.datePanel));

        // OK Actions
        if (this.okStatus) this.okStatus.addEventListener('click', () => this.updateStatus());
        if (this.okVisibility) this.okVisibility.addEventListener('click', () => this.updateVisibility());
        if (this.okDate) this.okDate.addEventListener('click', () => this.updateDate());

        // Visibility Radio Logic
        const visRadios = document.getElementsByName('visibility');
        visRadios.forEach(radio => {
            radio.addEventListener('change', (e) => this.handleVisibilityChange(e));
        });

        // Main Actions
        if (this.saveDraftBtn) this.saveDraftBtn.addEventListener('click', (e) => this.handleSaveDraft(e));
        if (this.previewBtn) this.previewBtn.addEventListener('click', (e) => this.handlePreview(e));
        if (this.publishBtn) this.publishBtn.addEventListener('click', (e) => this.handlePublish(e));
        if (this.trashBtn) this.trashBtn.addEventListener('click', (e) => this.handleTrash(e));
    }

    toggleCard() {
        if (this.body.style.display === 'none') {
            this.body.style.display = 'block';
            this.toggleBtn.innerHTML = '<span class="icon" style="-webkit-mask-image: url(\'assets/icons/ui-icon_chevron-up.svg\'); mask-image: url(\'assets/icons/ui-icon_chevron-up.svg\'); background-color: #444;"></span>';
        } else {
            this.body.style.display = 'none';
            // Rotate icon or change to down chevron
            this.toggleBtn.innerHTML = '<span class="icon" style="-webkit-mask-image: url(\'assets/icons/ui-icon_chevron-up.svg\'); mask-image: url(\'assets/icons/ui-icon_chevron-up.svg\'); transform: rotate(180deg); background-color: #444;"></span>';
        }
    }

    togglePanel(e, panel) {
        e.preventDefault();
        // Hide other panels first (accordion style behavior optional, but requested is just independent toggles usually)
        if (panel.style.display === 'none') {
            $(panel).slideDown('fast'); // Using jQuery if available, otherwise pure JS
            // panel.style.display = 'block'; 
        } else {
            $(panel).slideUp('fast');
            // panel.style.display = 'none';
        }
    }

    closePanel(e, panel) {
        e.preventDefault();
        $(panel).slideUp('fast');
        // panel.style.display = 'none';
    }

    updateStatus() {
        const select = document.getElementById('post-status-select');
        const text = select.options[select.selectedIndex].text;
        this.displayStatus.textContent = text;
        $(this.statusPanel).slideUp('fast');
    }

    handleVisibilityChange(e) {
        const passwordSpan = document.getElementById('password-span');
        if (e.target.value === 'password') {
            passwordSpan.style.display = 'block';
        } else {
            passwordSpan.style.display = 'none';
        }
    }

    updateVisibility() {
        const radios = document.getElementsByName('visibility');
        let selected = 'Public';
        for (const radio of radios) {
            if (radio.checked) {
                // Get label text
                selected = document.querySelector(`label[for="${radio.id}"]`).textContent;
                break;
            }
        }
        this.displayVisibility.textContent = selected;
        $(this.visibilityPanel).slideUp('fast');
    }

    updateDate() {
        const mm = document.getElementById('mm').value || '01';
        const dd = document.getElementById('jj').value || '01';
        const yyyy = document.getElementById('aa').value || new Date().getFullYear();
        const hh = document.getElementById('hh').value || '00';
        const mn = document.getElementById('mn').value || '00';

        // Format simple for display
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        const monthName = months[parseInt(mm) - 1] || months[0];

        this.displayDate.textContent = `${monthName} ${dd}, ${yyyy} @ ${hh}:${mn}`;
        $(this.datePanel).slideUp('fast');
    }

    handleSaveDraft(e) {
        e.preventDefault();
        // Add draft logic here (e.g., AJAX save or form submit with status=draft)
        const originalText = this.saveDraftBtn.textContent;
        this.saveDraftBtn.textContent = 'Saving...';
        setTimeout(() => {
            this.saveDraftBtn.textContent = 'Saved';
            setTimeout(() => {
                this.saveDraftBtn.textContent = originalText;
            }, 2000);
        }, 1000);
        console.log('Draft Saved');
    }

    handlePreview(e) {
        e.preventDefault();
        // Access content from editor
        const content = document.querySelector('.wp-content-area').innerHTML;
        // In real app, this might open a new window to a preview URL
        console.log('Previewing content...');
        alert('Preview functionality would open a new tab with content.');
    }

    handlePublish(e) {
        e.preventDefault();
        const confirmPublish = confirm('Are you sure you want to publish this post?');
        if (confirmPublish) {
            this.publishBtn.textContent = 'Publishing...';
            this.publishBtn.disabled = true;
            // Simulate submit
            setTimeout(() => {
                alert('Post Published!');
                this.publishBtn.textContent = 'Update';
                this.publishBtn.disabled = false;
                this.displayStatus.textContent = 'Published';
            }, 1000);
        }
    }

    handleTrash(e) {
        e.preventDefault();
        if (confirm('Move this post to trash?')) {
            console.log('Moved to trash');
            window.location.href = 'index.php?page=posts'; // Redirect example
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.publishMetaBox = new PublishMetaBox();
});
