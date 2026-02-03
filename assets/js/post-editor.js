/* ========================================
   POST EDITOR - Sidebar Functions
   ======================================== */

class PostEditor {
    constructor() {
        this.postId = this.getPostIdFromUrl();
        this.init();
    }

    getPostIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('edit') || null;
    }

    init() {
        this.loadCategories();
        this.loadTags();
        this.initFeaturedImage();
        this.initPublishButtons();
        this.initTagInput();

        // Load existing post if editing
        if (this.postId) {
            this.loadPost(this.postId);
        }
    }

    // ========================================
    // CATEGORIES
    // ========================================
    async loadCategories() {
        try {
            const res = await fetch('api/categories.php');
            const data = await res.json();

            if (data.success) {
                const container = document.getElementById('categories-list');
                container.innerHTML = '';

                data.categories.forEach(cat => {
                    const inputId = `category-${cat.id}`;

                    const label = document.createElement('label');
                    label.setAttribute('for', inputId);
                    label.className = 'paper-check';
                    label.innerHTML = `
                        <input type="checkbox" name="categories[]" id="${inputId}" value="${cat.id}">
                        <span>${cat.name}</span>
                    `;

                    container.appendChild(label);
                });
            }
        } catch (err) {
            console.error('Failed to load categories:', err);
        }
    }

    addCategory() {
        this.showDialog('New Category', [{ label: 'Enter new category name:', value: '' }], async (values) => {
            const name = values[0];
            if (!name) return;

            try {
                const res = await fetch('api/categories.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name })
                });
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error: ' + text.substring(0, 50));
                }

                if (data.success) {
                    this.loadCategories();
                } else {
                    this.showAlert('Error', data.message);
                }
            } catch (err) {
                console.error(err);
                this.showAlert('Error', 'Failed to add category: ' + err.message);
            }
        });
    }

    // ========================================
    // TAGS
    // ========================================
    selectedTags = [];

    async loadTags() {
        try {
            const res = await fetch('api/tags.php');
            const data = await res.json();

            if (data.success) {
                this.allTags = data.tags;
            }
        } catch (err) {
            console.error('Failed to load tags:', err);
        }
    }

    initTagInput() {
        const input = document.getElementById('tag-input');
        const addBtn = document.getElementById('tag-add-btn');

        if (addBtn) {
            addBtn.addEventListener('click', () => this.addTags());
        }

        if (input) {
            input.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.addTags();
                }
            });
        }
    }

    async addTags() {
        const input = document.getElementById('tag-input');
        const value = input.value.trim();
        if (!value) return;

        // Split by comma
        const names = value.split(',').map(t => t.trim()).filter(t => t);

        try {
            const res = await fetch('api/tags.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ names })
            });
            const data = await res.json();

            if (data.success) {
                data.tags.forEach(tag => {
                    if (!this.selectedTags.find(t => t.id === tag.id)) {
                        this.selectedTags.push(tag);
                    }
                });
                this.renderSelectedTags();
                input.value = '';
            }
        } catch (err) {
            this.showAlert('Error', 'Failed to add tags');
        }
    }

    removeTag(tagId) {
        this.selectedTags = this.selectedTags.filter(t => t.id !== tagId);
        this.renderSelectedTags();
    }

    renderSelectedTags() {
        const container = document.getElementById('selected-tags');
        container.innerHTML = '';

        this.selectedTags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'badge tag-badge';
            span.innerHTML = `
                ${tag.name}
                <button type="button" onclick="postEditor.removeTag(${tag.id})">&times;</button>
            `;
            container.appendChild(span);
        });
    }

    // ========================================
    // FEATURED IMAGE
    // ========================================
    featuredImage = null;

    initFeaturedImage() {
        const setBtn = document.getElementById('set-featured-image');
        const removeBtn = document.getElementById('remove-featured-image');

        if (setBtn) {
            setBtn.addEventListener('click', () => this.selectFeaturedImage());
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.removeFeaturedImage());
        }
    }

    selectFeaturedImage() {
        // Reuse media manager
        window.paperEditor.saveSelection();
        const modal = document.getElementById('media-modal-overlay');

        // Temporarily override insert behavior
        this._originalInsertMedia = window.paperEditor.insertMedia.bind(window.paperEditor);
        window.paperEditor.insertMedia = (url, ext) => {
            this.setFeaturedImage(url);
            modal.style.display = 'none';
            window.paperEditor.insertMedia = this._originalInsertMedia;
        };

        if (modal) {
            modal.style.display = 'flex';
            document.querySelector('.media-tab[data-tab="library"]').click();
        }
    }

    setFeaturedImage(url) {
        this.featuredImage = url;
        const preview = document.getElementById('featured-image-preview');
        const placeholder = document.getElementById('featured-image-placeholder');
        const removeBtn = document.getElementById('remove-featured-image');

        preview.innerHTML = `<img src="${url}" style="width:100%;">`;
        preview.style.display = 'block';
        placeholder.style.display = 'none';
        removeBtn.style.display = 'inline-block';
    }

    removeFeaturedImage() {
        this.featuredImage = null;
        const preview = document.getElementById('featured-image-preview');
        const placeholder = document.getElementById('featured-image-placeholder');
        const removeBtn = document.getElementById('remove-featured-image');

        preview.innerHTML = '';
        preview.style.display = 'none';
        placeholder.style.display = 'block';
        removeBtn.style.display = 'none';
    }

    // ========================================
    // PUBLISH
    // ========================================
    initPublishButtons() {
        document.getElementById('btn-save-draft')?.addEventListener('click', () => this.savePost('draft'));
        document.getElementById('btn-publish')?.addEventListener('click', () => this.savePost('published'));
        document.getElementById('btn-preview')?.addEventListener('click', () => this.previewPost());
        document.getElementById('btn-trash')?.addEventListener('click', () => this.trashPost());
    }

    async savePost(status, showNotification = true) {
        // Updated selector for title input to match the one in adding
        const titleInput = document.querySelector('.wp-title-input');
        const title = titleInput ? titleInput.value.trim() : '';
        const content = document.querySelector('.wp-content-area').innerHTML;

        // Collect all checked category checkboxes
        const categoryCheckboxes = document.querySelectorAll('input[name="categories[]"]:checked');
        const categoryIds = Array.from(categoryCheckboxes).map(cb => cb.value);

        if (!title) {
            this.showAlert('Validation', 'Title is required');
            return;
        }

        const postData = {
            title,
            content,
            status,
            category_ids: categoryIds,
            featured_image: this.featuredImage,
            tags: this.selectedTags.map(t => t.id)
        };

        // If editing, include ID
        if (this.postId) {
            postData.id = this.postId;
        }

        try {
            const res = await fetch('api/posts.php', {
                method: this.postId ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(postData)
            });
            const data = await res.json();

            if (data.success) {
                // Update URL if new post
                if (!this.postId && data.post_id) {
                    this.postId = data.post_id;
                    window.history.replaceState({}, '', `tambah-post.php?edit=${data.post_id}`);
                }

                // Update status display
                this.updateStatusDisplay(status);

                if (showNotification) {
                    const msg = status === 'published' ? 'Post published!' : 'Draft saved!';
                    this.showAlert('Success', msg, () => {
                        if (status === 'published') {
                            window.open(`post.php?slug=${data.slug}`, '_blank');
                        }
                    });
                }
            } else {
                this.showAlert('Error', data.message);
            }
        } catch (err) {
            this.showAlert('Error', 'Failed to save post');
            console.error(err);
        }
    }

    async loadPost(id) {
        try {
            const res = await fetch(`api/posts.php?id=${id}`);
            const data = await res.json();

            if (data.success) {
                const post = data.post;

                if (document.querySelector('.wp-title-input')) {
                    document.querySelector('.wp-title-input').value = post.title;
                }
                document.querySelector('.wp-content-area').innerHTML = post.content;

                // Set categories (multiple checkboxes)
                setTimeout(() => {
                    // Handle both single category_id and multiple category_ids
                    const catIds = post.category_ids || (post.category_id ? [post.category_id] : []);
                    catIds.forEach(catId => {
                        const catCheck = document.querySelector(`input[name="categories[]"][value="${catId}"]`);
                        if (catCheck) catCheck.checked = true;
                    });
                }, 500);

                // Set featured image
                if (post.featured_image) {
                    this.setFeaturedImage(post.featured_image);
                }

                // Set tags
                if (post.tags) {
                    this.selectedTags = post.tags;
                    this.renderSelectedTags();
                }

                // Update status display
                this.updateStatusDisplay(post.status);
            }
        } catch (err) {
            console.error('Failed to load post:', err);
        }
    }

    updateStatusDisplay(status) {
        const statusEl = document.getElementById('post-status');
        if (statusEl) {
            statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        }
    }

    previewPost() {
        // Save as draft first, then open preview
        this.savePost('draft', false).then(() => {
            if (this.postId) {
                // Create temporary preview (or just show draft)
                this.showAlert('Info', 'Preview functionality - implement as needed');
            }
        });
    }

    trashPost() {
        if (!this.postId) return;

        this.showConfirm('Trash Post', 'Move this post to trash?', async () => {
            try {
                const res = await fetch('api/posts.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: this.postId })
                });
                const data = await res.json();

                if (data.success) {
                    this.showAlert('Success', 'Post moved to trash', () => {
                        window.location.href = 'admin/posts.php';
                    });
                } else {
                    this.showAlert('Error', 'Failed to delete post');
                }
            } catch (err) {
                this.showAlert('Error', 'Failed to delete post');
            }
        });
    }
    // ========================================
    // CUSTOM DIALOGS
    // ========================================

    showDialog(title, fields, onConfirm) {
        const overlay = document.getElementById('editor-modal-overlay');
        const titleEl = document.getElementById('editor-modal-title');
        const bodyEl = document.getElementById('editor-modal-body');
        const cancelBtn = document.getElementById('editor-modal-cancel');
        const confirmBtn = document.getElementById('editor-modal-confirm');

        if (!overlay || !bodyEl) return;

        titleEl.textContent = title;
        bodyEl.innerHTML = '';
        const inputs = [];

        fields.forEach(field => {
            const group = document.createElement('div');
            group.className = 'editor-input-group';
            if (field.label) {
                const label = document.createElement('label');
                label.textContent = field.label;
                group.appendChild(label);
            }

            const input = document.createElement('input');
            input.type = field.type || 'text';
            input.className = 'editor-input';
            input.value = field.value || '';
            if (field.placeholder) input.placeholder = field.placeholder;

            input.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') confirmBtn.click();
            });

            group.appendChild(input);
            bodyEl.appendChild(group);
            inputs.push(input);
        });

        // Focus first input
        if (inputs.length > 0) setTimeout(() => inputs[0].focus(), 100);

        overlay.style.display = 'flex';
        confirmBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'inline-block';
        cancelBtn.textContent = 'Cancel';
        confirmBtn.textContent = 'OK';

        const close = () => { overlay.style.display = 'none'; confirmBtn.onclick = null; cancelBtn.onclick = null; };

        cancelBtn.onclick = close;
        confirmBtn.onclick = () => {
            const values = inputs.map(i => i.value);
            onConfirm(values);
            close();
        };
        overlay.onclick = (e) => { if (e.target === overlay) close(); };
    }

    showAlert(title, message, onOk = null) {
        const overlay = document.getElementById('editor-modal-overlay');
        const titleEl = document.getElementById('editor-modal-title');
        const bodyEl = document.getElementById('editor-modal-body');
        const cancelBtn = document.getElementById('editor-modal-cancel');
        const confirmBtn = document.getElementById('editor-modal-confirm');

        if (!overlay || !bodyEl) return;

        titleEl.textContent = title;
        bodyEl.innerHTML = `<p>${message}</p>`;

        overlay.style.display = 'flex';
        confirmBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'none';
        confirmBtn.textContent = 'OK';

        const close = () => {
            overlay.style.display = 'none';
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
            cancelBtn.style.display = ''; // Restore
            if (onOk) onOk();
        };

        confirmBtn.onclick = close;
        overlay.onclick = (e) => { if (e.target === overlay) close(); };
    }

    showConfirm(title, message, onConfirm) {
        const overlay = document.getElementById('editor-modal-overlay');
        const titleEl = document.getElementById('editor-modal-title');
        const bodyEl = document.getElementById('editor-modal-body');
        const cancelBtn = document.getElementById('editor-modal-cancel');
        const confirmBtn = document.getElementById('editor-modal-confirm');

        if (!overlay || !bodyEl) return;

        titleEl.textContent = title;
        bodyEl.innerHTML = `<p>${message}</p>`;

        overlay.style.display = 'flex';
        confirmBtn.style.display = 'inline-block';
        cancelBtn.style.display = 'inline-block';
        cancelBtn.textContent = 'Cancel';
        confirmBtn.textContent = 'OK';

        const close = () => { overlay.style.display = 'none'; confirmBtn.onclick = null; cancelBtn.onclick = null; };

        cancelBtn.onclick = close;
        confirmBtn.onclick = () => {
            onConfirm();
            close();
        };
        overlay.onclick = (e) => { if (e.target === overlay) close(); };
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.postEditor = new PostEditor();
});
