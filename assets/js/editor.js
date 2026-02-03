class PaperEditor {
    constructor() {
        this.editor = document.querySelector('.wp-content-area');
        this.fileInput = document.getElementById('mediaFileInput');
        this.wordCountDisplay = document.getElementById('word-count-display');
        this.mediaMode = 'media'; // 'media', 'image', or 'video'

        // Initialize Toolbar and delegate
        this.toolbar = new PaperToolbar(this);

        this.init();
    }

    init() {
        this.bindResizeEvents();
        this.initMediaManager();
        this.initWordCount();
        this.initFileInput();
        this.initPrismHighlight(); // NEW
        this.initCodeBlockEdit(); // NEW
        if (this.editor) this.editor.focus();
    }

    initFileInput() {
        if (this.fileInput) {
            this.fileInput.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) this.uploadImage(file);
                e.target.value = ''; // reset agar bisa pilih file sama
            });
        }
    }

    // Delegation to Toolbar
    exec(command, value = null) {
        this.toolbar.exec(command, value);
    }

    promptLink() { this.toolbar.promptLink(); }
    insertTable() { this.toolbar.insertTable(); }
    insertEmbedVideo() { this.toolbar.insertEmbedVideo(); }
    setFontSize(size) { this.toolbar.setFontSize(size); }
    applyLineHeight(val) { this.toolbar.applyLineHeight(val); }
    applyLetterSpacing(val) { this.toolbar.applyLetterSpacing(val); }
    toggleColorPalette(btn) { this.toolbar.toggleColorPalette(btn); }
    applyColor(type, color) { this.toolbar.applyColor(type, color); }
    insertCodeBlock() { this.toolbar.insertCodeBlock(); } // NEW

    saveSelection() {
        this.toolbar.saveSelection();
    }

    restoreSelection() {
        this.toolbar.restoreSelection();
    }

    bindResizeEvents() {
        const imageToolbar = document.getElementById('resize-toolbar');
        let currentTarget = null;

        // Hide toolbar saat klik di luar
        document.addEventListener('click', (e) => {
            if (imageToolbar && imageToolbar.style.display !== 'none') {
                if (!imageToolbar.contains(e.target) && e.target !== currentTarget) {
                    imageToolbar.style.display = 'none';
                    currentTarget = null;
                }
            }
        });

        // Show toolbar saat klik image/video
        this.editor.addEventListener('click', (e) => {
            const tag = e.target.tagName;

            if (tag === 'IMG' || tag === 'VIDEO' || tag === 'IFRAME' ||
                (tag === 'DIV' && e.target.classList.contains('video-wrapper'))) {

                currentTarget = e.target;
                if (tag === 'IFRAME') currentTarget = e.target.parentElement;

                // Position toolbar
                const rect = currentTarget.getBoundingClientRect();
                const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

                imageToolbar.style.display = 'flex';
                imageToolbar.style.top = (rect.top + scrollTop - 70) + 'px'; // Raised higher for multi-row
                imageToolbar.style.left = (rect.left + scrollLeft) + 'px';

                // Update button states untuk reflect current image state
                this.updateImageToolbarState(currentTarget);
            }
        });

        // Handle semua button clicks
        if (imageToolbar) {
            imageToolbar.querySelectorAll('.img-tool-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    if (!currentTarget) return;

                    const action = btn.getAttribute('data-action');
                    const value = btn.getAttribute('data-value');

                    switch (action) {
                        case 'size':
                            this.applyImageSize(currentTarget, value);
                            break;
                        case 'float':
                            this.applyImageFloat(currentTarget, value);
                            break;
                        case 'border':
                            this.toggleImageBorder(currentTarget);
                            break;
                    }

                    // Update button states setelah change
                    this.updateImageToolbarState(currentTarget);
                });
            });
        }

        // Keyboard Shortcut: Delete
        document.addEventListener('keydown', (e) => {
            if (!currentTarget) return;

            // Delete image
            if (e.key === 'Delete' || e.key === 'Backspace') {
                if (imageToolbar.style.display !== 'none') {
                    e.preventDefault();
                    currentTarget.remove();
                    imageToolbar.style.display = 'none';
                    currentTarget = null;
                }
            }
        });
    }

    /* --- Image Manipulation Methods --- */

    applyImageSize(img, size) {
        img.style.width = size;
        img.style.height = 'auto';
        if (img.classList.contains('video-wrapper') && size === '100%') {
            // specific logic usually not needed if wrapper is div block, but good safety
            img.style.width = '100%';
        }
    }

    applyImageFloat(img, floatClass) {
        // Remove existing float classes
        img.classList.remove('float-left', 'float-right');

        // Clear inline float style juga
        img.style.float = '';

        // Apply new float class (kecuali "none")
        if (floatClass !== 'none') {
            img.classList.add(floatClass);
        }
    }

    toggleImageBorder(img) {
        // Toggle no-border class
        img.classList.toggle('no-border');
    }

    updateImageToolbarState(img) {
        const toolbar = document.getElementById('resize-toolbar');
        if (!toolbar) return;

        // Reset semua active states
        toolbar.querySelectorAll('.img-tool-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // --- Detect & highlight SIZE ---
        // Normalize width check, might be inline style or class, mostly inline style from this tool
        const currentWidth = img.style.width;
        if (currentWidth) {
            const sizeBtn = toolbar.querySelector(`.img-tool-btn[data-action="size"][data-value="${currentWidth}"]`);
            if (sizeBtn) sizeBtn.classList.add('active');
        } else {
            // Assume 100% or auto if no inline width, maybe default active?
        }

        // --- Detect & highlight FLOAT ---
        let currentFloat = 'none';
        if (img.classList.contains('float-left')) {
            currentFloat = 'float-left';
        } else if (img.classList.contains('float-right')) {
            currentFloat = 'float-right';
        }
        const floatBtn = toolbar.querySelector(`.img-tool-btn[data-action="float"][data-value="${currentFloat}"]`);
        if (floatBtn) floatBtn.classList.add('active');

        // --- Detect & highlight BORDER ---
        if (img.classList.contains('no-border')) {
            const borderBtn = toolbar.querySelector('[data-action="border"]');
            if (borderBtn) borderBtn.classList.add('active');
        }
    }

    /* --- Media Manager --- */
    initMediaManager() {
        const modal = document.getElementById('media-modal-overlay');
        const overlay = modal;
        const cancelBtn = document.getElementById('media-modal-cancel');
        const tabs = document.querySelectorAll('.media-tab');
        const dropArea = document.getElementById('upload-drop-area');

        if (!modal) return;

        if (cancelBtn) cancelBtn.onclick = () => { modal.style.display = 'none'; };
        overlay.onclick = (e) => { if (e.target === overlay) modal.style.display = 'none'; }

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.media-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.media-pane').forEach(p => p.classList.remove('active'));

                tab.classList.add('active');
                const targetId = 'tab-' + tab.getAttribute('data-tab');
                document.getElementById(targetId).classList.add('active');

                if (tab.getAttribute('data-tab') === 'library') {
                    this.fetchMediaLibrary();
                }
            });
        });

        if (dropArea) {
            dropArea.onclick = () => {
                if (this.fileInput) this.fileInput.click();
            };

            dropArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropArea.style.background = '#e0e0e0';
            });
            dropArea.addEventListener('dragleave', () => {
                dropArea.style.background = '#fafafa';
            });
            dropArea.addEventListener('drop', (e) => {
                e.preventDefault();
                dropArea.style.background = '#fafafa';
                if (e.dataTransfer.files[0]) this.uploadImage(e.dataTransfer.files[0]);
            });
        }
    }

    fetchMediaLibrary() {
        const grid = document.getElementById('media-grid');
        grid.innerHTML = '<p style="text-align:center;">Loading...</p>';

        fetch('get_media.php')
            .then(res => res.json())
            .then(data => {
                grid.innerHTML = '';
                if (data.success && data.files.length > 0) {
                    const imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
                    const videoExts = ['mp4', 'webm', 'ogv', 'mov', 'avi'];

                    // Filter files based on current mode
                    let filteredFiles = data.files.filter(fileUrl => {
                        const ext = fileUrl.split('.').pop().toLowerCase();
                        if (this.mediaMode === 'image') {
                            return imageExts.includes(ext);
                        } else if (this.mediaMode === 'video') {
                            return videoExts.includes(ext);
                        }
                        return true; // 'media' mode shows all
                    });

                    if (filteredFiles.length > 0) {
                        filteredFiles.forEach(fileUrl => {
                            const ext = fileUrl.split('.').pop().toLowerCase();
                            let element;

                            if (videoExts.includes(ext)) {
                                element = document.createElement('video');
                                element.src = fileUrl;
                                element.style.objectFit = 'cover';
                            } else {
                                element = document.createElement('img');
                                element.src = fileUrl;
                            }

                            element.className = 'media-item';
                            element.title = 'Click to Insert';
                            element.onclick = () => {
                                this.restoreSelection();
                                this.insertMedia(fileUrl, ext);
                                document.getElementById('media-modal-overlay').style.display = 'none';
                            };
                            grid.appendChild(element);
                        });
                    } else {
                        const typeLabel = this.mediaMode === 'image' ? 'images' : (this.mediaMode === 'video' ? 'videos' : 'files');
                        grid.innerHTML = `<p style="text-align:center;">No ${typeLabel} found.</p>`;
                    }
                } else {
                    grid.innerHTML = '<p style="text-align:center;">No files found.</p>';
                }
            })
            .catch(err => {
                grid.innerHTML = '<p style="text-align:center;color:red;">Error fetching library.</p>';
            });
    }

    openMediaModal(mode = 'media') {
        this.mediaMode = mode;
        this.saveSelection();

        const modal = document.getElementById('media-modal-overlay');
        if (!modal) return;

        // Update modal title
        const titleEl = modal.querySelector('h3');
        if (titleEl) {
            if (mode === 'image') titleEl.textContent = 'Insert Image';
            else if (mode === 'video') titleEl.textContent = 'Insert Video';
            else titleEl.textContent = 'Insert Media';
        }

        // Update file input accept attribute
        const fileInput = document.getElementById('mediaFileInput');
        if (fileInput) {
            if (mode === 'image') fileInput.accept = 'image/*';
            else if (mode === 'video') fileInput.accept = 'video/*';
            else fileInput.accept = 'image/*,video/*';
        }

        // Update upload area text
        const uploadText = document.querySelector('#upload-drop-area p');
        if (uploadText) {
            const iconSpan = '<span class="folder-icon"></span>';
            if (mode === 'image') uploadText.innerHTML = iconSpan + ' Drag images here or click to upload';
            else if (mode === 'video') uploadText.innerHTML = iconSpan + ' Drag videos here or click to upload';
            else uploadText.innerHTML = iconSpan + ' Drag files here or click to upload';
        }

        modal.style.display = 'flex';
        document.querySelector('.media-tab[data-tab="upload"]').click();
    }

    triggerImageUpload() {
        this.openMediaModal('image');
    }

    triggerVideoUpload() {
        this.openMediaModal('video');
    }

    triggerMediaUpload() {
        this.openMediaModal('media');
    }

    uploadImage(file) {
        const formData = new FormData();
        formData.append('file', file);
        const dropArea = document.getElementById('upload-drop-area');
        const originHTML = dropArea.innerHTML;
        dropArea.innerHTML = '<p>Uploading...</p>';

        fetch('upload_handler.php', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                dropArea.innerHTML = originHTML;
                if (data.success) {
                    this.restoreSelection();
                    const ext = data.url.split('.').pop().toLowerCase();
                    this.insertMedia(data.url, ext);
                    document.getElementById('media-modal-overlay').style.display = 'none';
                } else {
                    alert('Upload Failed: ' + data.message);
                }
            })
            .catch(error => {
                dropArea.innerHTML = originHTML;
                alert('Upload Error');
            });
    }

    insertMedia(url, ext) {
        let html = '';
        if (['mp4', 'webm', 'ogv'].includes(ext)) {
            html = `<video src="${url}" controls style="max-width:100%; height:auto; display:block; margin: 10px 0;"></video><br>`;
        } else {
            html = `<img src="${url}" style="max-width:100%; height:auto;" /><br>`;
        }
        this.exec('insertHTML', html);
    }

    /* --- Word Count --- */
    initWordCount() {
        if (!this.editor || !this.wordCountDisplay) {
            console.error('Word count elements not found', this.editor, this.wordCountDisplay);
            return;
        }

        const update = () => this.updateWordCount();

        // Update on multiple events to be safe
        this.editor.addEventListener('input', update);
        this.editor.addEventListener('keyup', update);
        this.editor.addEventListener('change', update);
        this.editor.addEventListener('click', update); // In case of paste via context menu sometimes
        this.editor.addEventListener('paste', () => setTimeout(update, 100)); // Delay for paste

        // Initial count
        update();
    }

    updateWordCount() {
        const text = this.editor.innerText || this.editor.textContent;
        // Replace multiple spaces/newlines with single space, then split
        const words = text.trim().split(/[\s\n]+/);
        const count = words.filter(word => word.length > 0).length;
        this.wordCountDisplay.textContent = `Word count: ${count}`;
    }

    /* --- Prism.js Integration --- */

    initPrismHighlight() {
        if (!window.Prism) return;

        // Initial highlight
        this.highlightCodeBlocks();

        // Re-highlight on content change (debounced)
        let highlightTimeout;
        this.editor.addEventListener('input', () => {
            clearTimeout(highlightTimeout);
            highlightTimeout = setTimeout(() => {
                this.highlightCodeBlocks();
            }, 500); // Delay 500ms untuk performance
        });

        // Re-highlight on paste
        this.editor.addEventListener('paste', () => {
            setTimeout(() => {
                this.highlightCodeBlocks();
            }, 100);
        });
    }

    highlightCodeBlocks() {
        if (!window.Prism) return;
        const codeBlocks = this.editor.querySelectorAll('pre code');
        codeBlocks.forEach(code => {
            if (code.getAttribute('class') && code.getAttribute('class').includes('language-')) {
                Prism.highlightElement(code);
            }
        });
    }

    initCodeBlockEdit() {
        this.editor.addEventListener('dblclick', (e) => {
            // Check if clicked on code block
            let target = e.target;
            let preElement = null;
            let codeElement = null;

            // Traverse up to find pre > code structure
            while (target && target !== this.editor) {
                if (target.tagName === 'CODE' && target.parentElement.tagName === 'PRE') {
                    codeElement = target;
                    preElement = target.parentElement;
                    break;
                }
                if (target.tagName === 'PRE') {
                    preElement = target;
                    codeElement = target.querySelector('code');
                    break;
                }
                target = target.parentElement;
            }

            if (preElement && codeElement) {
                e.preventDefault();
                this.editCodeBlock(preElement, codeElement);
            }
        });
    }

    editCodeBlock(preElement, codeElement) {
        // Extract current language
        const classMatch = codeElement.className.match(/language-(\w+)/);
        const currentLang = classMatch ? classMatch[1] : 'plaintext';

        // Extract current code (decode HTML entities)
        const currentCode = codeElement.textContent;

        this.toolbar.showCodeDialog('Edit Code Block', (language, code) => {
            if (code && code.trim()) {
                // Update code content
                const escapedCode = code
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                // Update class
                codeElement.className = `language-${language}`;
                codeElement.innerHTML = escapedCode;

                // Re-highlight
                if (window.Prism) {
                    Prism.highlightElement(codeElement);
                }
            }
        });

        // Pre-fill dialog dengan current values
        setTimeout(() => {
            const langSelect = document.getElementById('code-language-select');
            const codeInput = document.getElementById('code-content-input');

            if (langSelect) langSelect.value = currentLang;
            if (codeInput) codeInput.value = currentCode;
        }, 50);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    window.paperEditor = new PaperEditor();
});
