class PaperToolbar {
    constructor(editorInstance) {
        this.editor = editorInstance.editor; // Reference to the editable div
        this.editorInstance = editorInstance;
        this.savedRange = null;
        this.init();
    }

    init() {
        this.bindEvents();
        this.initColorPicker();

        // Bind editor events for state updates
        this.editor.addEventListener('mouseup', () => this.checkActiveState());
        this.editor.addEventListener('keyup', () => this.checkActiveState());
        this.editor.addEventListener('input', () => this.checkActiveState()); // Also on input

        // Detect saat selection berubah (lebih reliable)
        document.addEventListener('selectionchange', () => {
            // Pastikan selection ada di dalam editor
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                const range = selection.getRangeAt(0);
                if (this.editor.contains(range.commonAncestorContainer)) {
                    this.checkActiveState();
                }
            }
        });
    }

    bindEvents() {
        // Toolbar button clicks
        document.querySelectorAll('.toolbar-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const command = btn.getAttribute('data-command');
                if (command) {
                    this.exec(command);
                }
                setTimeout(() => this.checkActiveState(), 100);
            });
        });

        // Dropdowns
        const formatSelect = document.getElementById('formatBlockSelect');
        if (formatSelect) {
            // The inline onchange in HTML handles logic, but we need to update state
            formatSelect.addEventListener('change', () => setTimeout(() => this.checkActiveState(), 100));
        }

        // Color Picker Outside Click
        document.addEventListener('click', (e) => {
            const picker = document.getElementById('colorPickerModal');
            const btn = document.getElementById('btn-color-picker');
            if (picker && picker.style.display === 'block') {
                if (!picker.contains(e.target) && !btn.contains(e.target)) {
                    picker.style.display = 'none';
                }
            }
        });
    }

    checkActiveState() {
        const buttons = document.querySelectorAll('[data-command]');
        buttons.forEach(btn => {
            const command = btn.getAttribute('data-command');
            // Link is special case
            if (command === 'link') return;

            try {
                if (document.queryCommandState(command)) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            } catch (e) {
                // Ignore errors for commands that don't support state
            }
        });

        // Font Size Select Logic (New)
        const fontSizeSelect = document.getElementById('fontSizeSelect');
        if (fontSizeSelect && !fontSizeSelect.getAttribute('data-bound')) {
            fontSizeSelect.setAttribute('data-bound', true);
            fontSizeSelect.addEventListener('mousedown', () => {
                this.saveSelection();
            });
            fontSizeSelect.addEventListener('change', (e) => {
                if (e.target.value) {
                    this.restoreSelection();
                    // We need to call setFontSize via the editor instance if possible, or direct method
                    // Since PaperToolbar has setFontSize method:
                    this.setFontSize(e.target.value);
                    setTimeout(() => {
                        this.editorInstance.editor.focus();
                    }, 50);
                }
            });
        }

        // Update Font Family Select
        const fontSelect = document.querySelector('.toolbar-select[onchange*="fontName"]');
        if (fontSelect) {
            const font = document.queryCommandValue('fontName');
            if (font) fontSelect.value = font.replace(/['"]/g, '');
        }

        // Detect Format Block (Heading)
        const formatSelect = document.getElementById('formatBlockSelect');
        if (formatSelect) {
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                let node = selection.anchorNode;

                // Traverse up untuk cari block element
                while (node && node !== this.editor) {
                    if (node.nodeType === 1) { // Element node
                        const tagName = node.tagName.toUpperCase();
                        const blockTags = ['P', 'H1', 'H2', 'H3', 'H4', 'H5', 'BLOCKQUOTE', 'PRE'];

                        if (blockTags.includes(tagName)) {
                            // Convert tagName ke value yang cocok dengan option
                            formatSelect.value = tagName.toLowerCase();
                            break;
                        }
                    }
                    node = node.parentNode;
                }
            }
        }

        // Update Font Size Select (Input)
        const fontSizeInput = document.getElementById('customFontSize');
        if (fontSizeInput) {
            const selection = window.getSelection();
            if (selection.rangeCount > 0) {
                let node = selection.anchorNode;

                // Kalau text node, ambil parent element-nya
                const element = (node.nodeType === 3) ? node.parentElement : node;

                if (element && element !== this.editor) {
                    const computedSize = window.getComputedStyle(element).fontSize;
                    // computedSize dalam format "16px", ambil angkanya saja
                    const sizeNumber = parseInt(computedSize, 10);

                    if (!isNaN(sizeNumber)) {
                        fontSizeInput.value = sizeNumber;
                        fontSizeInput.placeholder = sizeNumber + 'px';
                    }
                }
            }
        }

        // Link Button State
        const linkBtn = document.getElementById('btn-link');
        if (linkBtn) {
            const node = document.getSelection().anchorNode;
            let isLink = false;
            // Traverse up to find 'A' tag
            let parent = node ? node.parentElement : null;
            while (parent && parent.classList && !parent.classList.contains('wp-content-area')) {
                if (parent.tagName === 'A') {
                    isLink = true;
                    break;
                }
                parent = parent.parentElement;
            }

            const iconSpan = linkBtn.querySelector('.icon');
            if (isLink) {
                linkBtn.classList.add('active');
                linkBtn.setAttribute('title', 'Unlink');
                if (iconSpan) {
                    iconSpan.style.maskImage = "url('assets/icons/ui-icon_unlink.svg')";
                    iconSpan.style.webkitMaskImage = "url('assets/icons/ui-icon_unlink.svg')";
                }
            } else {
                linkBtn.classList.remove('active');
                linkBtn.setAttribute('title', 'Link');
                if (iconSpan) {
                    iconSpan.style.maskImage = "url('assets/icons/ui-icon_link.svg')";
                    iconSpan.style.webkitMaskImage = "url('assets/icons/ui-icon_link.svg')";
                }
            }
        }
    }

    exec(command, value = null) {
        document.execCommand(command, false, value);
        this.editor.focus();
        this.checkActiveState();
    }

    /* --- Dialog Helper --- */
    saveSelection() {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            this.savedRange = selection.getRangeAt(0);
        }
    }

    restoreSelection() {
        if (this.savedRange) {
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(this.savedRange);
        } else {
            this.editor.focus();
        }
    }

    insertCodeBlock() {
        this.saveSelection();

        // Dialog dengan language selector
        this.showCodeDialog('Insert Code Block', (language, code) => {
            this.restoreSelection();

            if (code && code.trim()) {
                // Escape HTML entities
                const escapedCode = code
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                const langClass = language ? `language-${language}` : 'language-plaintext';
                const html = `<pre class="line-numbers"><code class="${langClass}">${escapedCode}</code></pre><p><br></p>`;

                this.exec('insertHTML', html);

                // Re-highlight setelah insert
                setTimeout(() => {
                    if (window.Prism) {
                        Prism.highlightAllUnder(this.editor);
                    }
                }, 50);
            }
        });
    }

    showCodeDialog(title, onConfirm) {
        const overlay = document.getElementById('editor-modal-overlay');
        const titleEl = document.getElementById('editor-modal-title');
        const bodyEl = document.getElementById('editor-modal-body');
        const cancelBtn = document.getElementById('editor-modal-cancel');
        const confirmBtn = document.getElementById('editor-modal-confirm');

        if (!overlay || !bodyEl) return;

        titleEl.textContent = title;
        bodyEl.innerHTML = `
            <div class="editor-input-group">
                <label>Language</label>
                <select id="code-language-select" class="editor-input" style="width:100%;">
                    <option value="plaintext">Plain Text</option>
                    <option value="markup">HTML / XML</option>
                    <option value="css">CSS</option>
                    <option value="javascript">JavaScript</option>
                    <option value="php">PHP</option>
                    <option value="python">Python</option>
                    <option value="bash">Bash / Shell</option>
                    <option value="sql">SQL</option>
                    <option value="json">JSON</option>
                </select>
            </div>
            <div class="editor-input-group">
                <label>Code</label>
                <textarea id="code-content-input" class="editor-input" style="width:100%; height:200px; font-family:monospace; resize:vertical;" placeholder="Paste your code here..."></textarea>
            </div>
        `;

        overlay.style.display = 'flex';

        // Focus textarea
        setTimeout(() => {
            const input = document.getElementById('code-content-input');
            if (input) input.focus();
        }, 100);

        const close = () => {
            overlay.style.display = 'none';
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
        };

        cancelBtn.onclick = close;

        confirmBtn.onclick = () => {
            const language = document.getElementById('code-language-select').value;
            const code = document.getElementById('code-content-input').value;
            onConfirm(language, code);
            close();
        };

        overlay.onclick = (e) => { if (e.target === overlay) close(); };
    }

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
            const label = document.createElement('label');
            label.textContent = field.label;
            group.appendChild(label);

            const input = document.createElement('input');
            input.type = field.type || 'text';
            input.className = 'editor-input';
            input.value = field.value || '';
            if (field.placeholder) input.placeholder = field.placeholder;
            // Focus first input
            if (inputs.length === 0) setTimeout(() => input.focus(), 100);

            input.addEventListener('keyup', (e) => {
                if (e.key === 'Enter') confirmBtn.click();
            });

            group.appendChild(input);
            bodyEl.appendChild(group);
            inputs.push(input);
        });

        overlay.style.display = 'flex';
        const close = () => { overlay.style.display = 'none'; confirmBtn.onclick = null; cancelBtn.onclick = null; };
        cancelBtn.onclick = close;
        confirmBtn.onclick = () => {
            const values = inputs.map(input => input.value);
            onConfirm(values);
            close();
        };
        overlay.onclick = (e) => { if (e.target === overlay) close(); };
    }

    /* --- Features --- */

    promptLink() {
        const linkBtn = document.getElementById('btn-link');
        this.editor.focus();

        if (linkBtn && linkBtn.classList.contains('active')) {
            document.execCommand('unlink');
            this.checkActiveState();
        } else {
            this.saveSelection();
            this.showDialog('Insert Link', [
                { label: 'URL', value: 'http://' }
            ], (values) => {
                this.restoreSelection();
                const url = values[0];
                if (url && url !== 'http://') {
                    this.exec('createLink', url);
                }
            });
        }
    }

    insertTable() {
        this.saveSelection();
        this.showDialog('Insert Table', [
            { label: 'Number of Rows', type: 'number', value: 3 },
            { label: 'Number of Columns', type: 'number', value: 3 }
        ], (values) => {
            this.restoreSelection();
            const rows = parseInt(values[0], 10);
            const cols = parseInt(values[1], 10);
            if (rows > 0 && cols > 0) {
                // Paper CSS table with table-hover class
                let html = '<table class="table-hover" style="width:100%; margin: 10px 0;">';
                // Add thead with first row
                html += '<thead><tr>';
                for (let j = 0; j < cols; j++) {
                    html += '<th>Header ' + (j + 1) + '</th>';
                }
                html += '</tr></thead>';
                // Add tbody with remaining rows
                html += '<tbody>';
                for (let i = 0; i < rows - 1; i++) {
                    html += '<tr>';
                    for (let j = 0; j < cols; j++) {
                        html += '<td>Cell</td>';
                    }
                    html += '</tr>';
                }
                html += '</tbody>';
                html += '</table><br>';
                this.exec('insertHTML', html);
            }
        });
    }

    insertEmbedVideo() {
        this.saveSelection();
        this.showDialog('Embed YouTube Video', [
            { label: 'YouTube URL', value: 'https://' }
        ], (values) => {
            this.restoreSelection();
            const url = values[0];
            if (!url) return;

            let embedUrl = '';
            if (url.includes('youtube.com') || url.includes('youtu.be')) {
                const videoId = url.split('v=')[1]?.split('&')[0] || url.split('/').pop();
                embedUrl = `https://www.youtube.com/embed/${videoId}`;
            }

            if (embedUrl) {
                const html = `<div class="video-wrapper" style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;margin:10px 0;"><iframe src="${embedUrl}" style="position:absolute;top:0;left:0;width:100%;height:100%;" frameborder="0" allowfullscreen></iframe></div><br>`;
                this.exec('insertHTML', html);
            } else {
                alert('Invalid YouTube URL.');
            }
        });
    }

    setFontSize(size) {
        document.execCommand("fontSize", false, "7");
        const fontElements = this.editor.getElementsByTagName("font");
        Array.from(fontElements).forEach(font => {
            if (font.getAttribute("size") === "7") {
                font.removeAttribute("size");
                font.style.fontSize = size;
            }
        });
        this.editor.focus();
    }

    applyLineHeight(val) {
        const selection = window.getSelection();
        if (!selection.rangeCount) return;

        let node = selection.anchorNode;
        let block = null;

        while (node && node !== this.editor) {
            if (node.nodeType === 1 && ['P', 'DIV', 'H1', 'H2', 'H3', 'H4', 'H5', 'LI', 'BLOCKQUOTE'].includes(node.tagName)) {
                block = node;
                break;
            }
            node = node.parentElement;
        }

        if (block) {
            block.style.lineHeight = val;
        } else {
            document.execCommand('formatBlock', false, 'div');
            // Re-find block logic omitted for brevity as it's often not needed immediately if we just formatted
        }
        this.editor.focus();
    }

    applyLetterSpacing(val) {
        const selection = window.getSelection();
        if (!selection.rangeCount || selection.isCollapsed) {
            // No selection, apply to current block
            let node = selection.anchorNode;
            let block = null;

            while (node && node !== this.editor) {
                if (node.nodeType === 1 && ['P', 'DIV', 'H1', 'H2', 'H3', 'H4', 'H5', 'LI', 'BLOCKQUOTE', 'SPAN'].includes(node.tagName)) {
                    block = node;
                    break;
                }
                node = node.parentElement;
            }

            if (block) {
                block.style.letterSpacing = val;
            }
        } else {
            // Wrap selection in span with letter-spacing
            const range = selection.getRangeAt(0);
            const span = document.createElement('span');
            span.style.letterSpacing = val;
            range.surroundContents(span);
        }
        this.editor.focus();
    }

    saveSelection() {
        const selection = window.getSelection();
        if (selection.rangeCount > 0) {
            this.savedRange = selection.getRangeAt(0);
        }
    }

    restoreSelection() {
        if (this.savedRange) {
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(this.savedRange);
        } else {
            this.editor.focus();
        }
    }

    insertCodeBlock() {
        this.saveSelection();

        // Dialog dengan language selector
        this.showCodeDialog('Insert Code Block', (language, code) => {
            this.restoreSelection();

            if (code && code.trim()) {
                // Escape HTML entities
                const escapedCode = code
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                const langClass = language ? `language-${language}` : 'language-plaintext';
                const html = `<pre class="line-numbers"><code class="${langClass}">${escapedCode}</code></pre><p><br></p>`;

                this.exec('insertHTML', html);

                // Re-highlight setelah insert
                setTimeout(() => {
                    if (this.editorInstance && typeof this.editorInstance.highlightCodeBlocks === 'function') {
                        this.editorInstance.highlightCodeBlocks();
                    }
                }, 50);
            }
        });
    }

    showCodeDialog(title, onConfirm) {
        const overlay = document.getElementById('editor-modal-overlay');
        const titleEl = document.getElementById('editor-modal-title');
        const bodyEl = document.getElementById('editor-modal-body');
        const cancelBtn = document.getElementById('editor-modal-cancel');
        const confirmBtn = document.getElementById('editor-modal-confirm');

        if (!overlay || !bodyEl) return;

        titleEl.textContent = title;
        bodyEl.innerHTML = `
            <div class="editor-input-group">
                <label>Language</label>
                <select id="code-language-select" class="editor-input" style="width:100%;">
                    <option value="plaintext">Plain Text</option>
                    <option value="markup">HTML / XML</option>
                    <option value="css">CSS</option>
                    <option value="javascript">JavaScript</option>
                    <option value="php">PHP</option>
                    <option value="python">Python</option>
                    <option value="bash">Bash / Shell</option>
                    <option value="sql">SQL</option>
                    <option value="json">JSON</option>
                </select>
            </div>
            <div class="editor-input-group">
                <label>Code</label>
                <textarea id="code-content-input" class="editor-input" style="width:100%; height:200px; font-family:monospace; resize:vertical;" placeholder="Paste your code here..."></textarea>
            </div>
        `;

        overlay.style.display = 'flex';

        // Focus textarea
        setTimeout(() => {
            const input = document.getElementById('code-content-input');
            if (input) input.focus();
        }, 100);

        const close = () => {
            overlay.style.display = 'none';
            confirmBtn.onclick = null;
            cancelBtn.onclick = null;
        };

        cancelBtn.onclick = close;

        confirmBtn.onclick = () => {
            const language = document.getElementById('code-language-select').value;
            const code = document.getElementById('code-content-input').value;
            onConfirm(language, code);
            close();
        };

        overlay.onclick = (e) => { if (e.target === overlay) close(); };
    }

    /* --- Color Picker --- */
    initColorPicker() {
        const colors = [
            ['#000000', '#424242', '#636363', '#9C9C94', '#CEC6CE', '#EFEFEF', '#F7F7F7', '#FFFFFF'],
            ['#FF0000', '#FF9C00', '#FFFF00', '#00FF00', '#00FFFF', '#0000FF', '#9C00FF', '#FF00FF'],
            ['#7B3900', '#E79439', '#555555', '#222222', '#111111', '#003163', '#21104A', '#4A1031']
            // Simplified palette for brevity
        ];
        const bgGrid = document.getElementById('bgColorGrid');
        const textGrid = document.getElementById('textColorGrid');

        if (!bgGrid || !textGrid) return;
        // Clear existing if any (in case of re-init)
        bgGrid.innerHTML = '';
        textGrid.innerHTML = '';

        const createGridItems = (gridElement, actionType) => {
            colors.forEach(row => {
                row.forEach(color => {
                    const btn = document.createElement('button');
                    btn.className = 'color-btn';
                    btn.style.backgroundColor = color;
                    btn.title = color;
                    btn.onclick = (e) => {
                        e.stopPropagation();
                        this.applyColor(actionType, color);
                    };
                    gridElement.appendChild(btn);
                });
            });
        };
        createGridItems(bgGrid, 'hiliteColor');
        createGridItems(textGrid, 'foreColor');
    }

    toggleColorPalette(btn) {
        const modal = document.getElementById('colorPickerModal');
        if (!modal) return;
        if (modal.style.display === 'block') {
            modal.style.display = 'none';
        } else {
            modal.style.display = 'block';
            modal.style.top = (btn.offsetTop + btn.offsetHeight + 5) + 'px';
            modal.style.left = btn.offsetLeft + 'px';
        }
    }

    applyColor(type, color) {
        if (type === 'hiliteColor') {
            document.execCommand('hiliteColor', false, color);
        } else {
            document.execCommand('foreColor', false, color);
        }
        document.getElementById('colorPickerModal').style.display = 'none';
        this.editor.focus();
    }
}
