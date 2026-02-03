<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Post - PaperCMS</title>
    
    <!-- Prism Theme (switchable via JS) -->
    <link id="prism-theme" rel="stylesheet" href="../assets/css/prism/prism.min.css">
    
    <!-- Apply saved dark mode preference ASAP to prevent flash -->
    <script>
        (function() {
            const saved = localStorage.getItem('paperCMS_darkMode');
            if (saved === 'dark' || (!saved && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
                // Also switch Prism theme early if possible
                const prismTheme = document.getElementById('prism-theme');
                if (prismTheme) prismTheme.href = 'assets/css/prism/prism-tomorrow.min.css';
            }
        })();
    </script>
    
    <link rel="stylesheet" href="../assets/css/prism/prism-line-numbers.min.css">
    <link rel="stylesheet" href="https://unpkg.com/papercss@1.9.2/dist/paper.min.css">
    <style>
        /* CSS Custom untuk meniru WP Classic + Summernote Vibe */
        @font-face { font-family: 'Neucha'; src: url('../assets/font/neucha.otf'); }
        @font-face { font-family: 'Notepen'; src: url('../assets/font/Notepen.otf'); }

        body { background-color: #f1f1f1; font-family: 'Neucha', sans-serif; }
        
        .wp-header { padding: 10px 0; margin-bottom: 20px; }
        .wp-title-input { font-size: 1.5rem !important; height: 3rem; font-weight: bold; }
        
        /* Area Editor */
        .wp-editor-container { background: #fff; margin-top: 20px; border: 1px solid #ddd; overflow: hidden; max-width: 100%; }
        
        /* Toolbar Styling */
        .wp-toolbar { 
            background: #f5f5f5; 
            padding: 5px 10px;
            border-bottom: 1px solid #ddd; 
            display: flex; 
            flex-wrap: wrap; 
            gap: 5px;
            align-items: center; 
        }

        .toolbar-group {
            display: flex;
            flex-direction: row;
            gap: 2px;
            align-items: center;
            position: relative;
        }

        .toolbar-group-label {
            display: none;
        }

        .toolbar-actions {
            display: flex;
            gap: 1px;
            background: #fff;
            border: 1px solid #e5e5e5;
            border-radius: 4px;
            padding: 2px;
        }

        .toolbar-btn { 
            background: transparent; 
            border: none; 
            cursor: pointer; 
            width: 34px;
            height: 34px; 
            display: flex; 
            align-items: center; 
            justify-content: center;
            border-radius: 3px;
            transition: background 0.2s;
        }
        
        .toolbar-btn:hover { background: #e9ecef; }
        .toolbar-btn.active { background: #d3d3d3; box-shadow: inset 0 2px 4px rgba(0,0,0,0.1); }
        .toolbar-btn.active .icon { background-color: #000; }
        
        /* Icon Styling via Mask (Modern & Robust) */
        .icon {
            display: inline-block;
            width: 20px;
            height: 20px;
            min-width: 20px; 
            background-color: #444;
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-position: center;
            mask-position: center;
            -webkit-mask-size: 100%; 
            mask-size: 100%;
        }
        
        /* Hover effect */
        .toolbar-btn:hover .icon {
            background-color: #000;
        }

        /* Toolbar Divider */
        .toolbar-divider {
            display: inline-block;
            width: 1px;
            height: 24px;
            background: #ddd;
            margin: 0 3px;
        }

        /* Dropdowns */
        .toolbar-select {
            border-bottom-left-radius: 255px 15px;
            border-bottom-right-radius: 15px 255px;
            border-top-left-radius: 255px 15px;
            border-top-right-radius: 15px 255px;
            border: 2px solid #41403e;
            box-shadow: 1px 4px 2px -3px rgba(0, 0, 0, 0.3);
            background: #fff;
            padding: 0 10px;
            height: 34px;
            font-family: 'Neucha', sans-serif;
            font-size: 0.9rem;
            cursor: pointer;
            outline: none;
            min-width: 100px;
            transition: all 235ms ease-in-out;
        }
        .toolbar-select:hover { 
            box-shadow: 2px 8px 8px -6px rgba(0, 0, 0, 0.3);
            transform: translateY(-2px); 
        }
        .toolbar-select:focus {
            box-shadow: 2px 8px 15px -6px rgba(0, 0, 0, 0.3);
        }

        /* Color Picker Modal */
        .color-picker-modal {
            display: none;
            position: absolute;
            background: #fff;
            border: 2px solid #41403e;
            border-bottom-left-radius: 15px 255px;
            border-bottom-right-radius: 225px 15px;
            border-top-left-radius: 255px 15px;
            border-top-right-radius: 15px 225px;
            box-shadow: 5px 10px 15px -5px rgba(0,0,0,0.3);
            padding: 15px;
            z-index: 1000;
            width: 380px;
        }
        .color-picker-row { display: flex; gap: 15px; }
        .color-section { flex: 1; text-align: center; }
        .color-section h5 { margin: 0 0 10px; font-family: 'Neucha'; font-size: 1rem; }
        .color-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 2px; margin-top: 5px; }
        .color-btn { 
            width: 20px; height: 20px; border: 1px solid #ddd; cursor: pointer; padding: 0; 
            transition: transform 0.1s;
        }
        .color-btn:hover { transform: scale(1.2); border-color: #000; z-index: 2; position: relative; }
        .reset-btn {
            width: 100%; margin-bottom: 5px; font-size: 0.8rem; padding: 2px;
            border: 1px solid #41403e; background: #fff; cursor: pointer;
            font-family: 'Neucha';
        }

        /* Generic Editor Modal (PaperCSS Style) */
        .editor-modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        .editor-modal {
            background: #fff;
            border: 2px solid #41403e;
            border-bottom-left-radius: 15px 255px;
            border-bottom-right-radius: 225px 15px;
            border-top-left-radius: 255px 15px;
            border-top-right-radius: 15px 225px;
            box-shadow: 15px 28px 25px -18px rgba(0, 0, 0, 0.2);
            padding: 2rem;
            width: 400px;
            max-width: 90%;
            position: relative;
        }
        .editor-modal h3 { margin-top: 0; font-family: 'Neucha'; }
        .editor-modal-body { margin-bottom: 1.5rem; }
        .editor-modal-footer { text-align: right; }
        
        .editor-input-group { margin-bottom: 1rem; }
        .editor-input-group label { display: block; margin-bottom: 0.5rem; font-weight: bold; font-family: 'Neucha'; }
        .editor-input {
            width: 100%;
            padding: 0.5rem;
            border: 2px solid #41403e;
            border-bottom-left-radius: 15px 255px;
            border-bottom-right-radius: 225px 15px;
            border-top-left-radius: 255px 15px;
            border-top-right-radius: 15px 225px;
            outline: none;
            font-family: 'Neucha';
            font-size: 1rem;
            transition: box-shadow 0.2s;
        }
        .editor-input:focus { box-shadow: 2px 8px 15px -6px rgba(0,0,0,0.3); }

        /* Media Manager Styles */
        .media-tabs { display: flex; border-bottom: 2px solid #41403e; margin-bottom: 1rem; }
        .media-tab {
            padding: 0.5rem 1rem; cursor: pointer; background: transparent; border: none;
            font-family: 'Neucha'; font-size: 1.1rem; border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .media-tab.active { border-bottom-color: #41403e; font-weight: bold; }
        .media-pane { display: none; }
        .media-pane.active { display: block; }
        
        .upload-area {
            border: 2px dashed #41403e; padding: 2rem; text-align: center;
            cursor: pointer; background: #fafafa;
        }
        .upload-area:hover { background: #f0f0f0; }

        .media-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px; max-height: 300px; overflow-y: auto; padding: 5px;
        }
        
        /* Categories List - Paper Radio Styling */
        #categories-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        #categories-list .paper-radio {
            display: block;
        }
        
        .media-item {
            width: 100%; aspect-ratio: 1; object-fit: cover;
            border: 2px solid #ddd; cursor: pointer; transition: transform 0.1s;
        }
        .media-item:hover { border-color: #41403e; transform: scale(1.05); }

        /* Resize Handle */
        .resize-overlay {
            position: absolute; border: 2px dashed #007bff; pointer-events: none; z-index: 10;
        }
        .resize-options {
            position: absolute; background: #fff; border: 2px solid #41403e; padding: 5px;
            display: flex; gap: 5px; z-index: 100;
        }
        .resize-btn { 
            font-size: 0.8rem; padding: 2px 5px; border: 1px solid #ddd; cursor: pointer; 
            font-family: 'Neucha';
        }
        .resize-btn:hover { background: #eee; }

        /* Image Toolbar Styling */
        .image-toolbar {
            position: absolute;
            background: #fff;
            border: 2px solid #41403e;
            border-radius: 8px;
            padding: 8px 12px;
            display: flex;
            gap: 12px;
            align-items: center;
            z-index: 100;
            box-shadow: 2px 4px 10px rgba(0,0,0,0.15);
            font-family: 'Neucha', sans-serif;
        }

        .toolbar-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .section-label {
            font-size: 0.7rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ========================================
           DARK MODE TOGGLE BUTTON
           ======================================== */

        .dark-mode-toggle {
            background: #f5f5f5;
            border: 2px solid #41403e;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-bottom-left-radius: 255px 15px;
            border-bottom-right-radius: 15px 255px;
            border-top-left-radius: 15px 255px;
            border-top-right-radius: 255px 15px;
        }

        .dark-mode-toggle:hover {
            transform: scale(1.1);
            box-shadow: 2px 4px 8px rgba(0,0,0,0.2);
        }

        .dark-mode-toggle.fixed-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        .dark-mode-toggle .icon {
            position: absolute;
            width: 24px;
            height: 24px;
            background-color: #41403e; 
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        .dark-mode-toggle .icon-moon {
            transform: rotate(-180deg) scale(0);
            opacity: 0;
        }
        
        .dark-mode-toggle .icon-sun {
            transform: rotate(0) scale(1);
            opacity: 1;
        }

        html.dark .dark-mode-toggle {
            background: #2d2d2d;
            border-color: #555;
        }
        
        html.dark .dark-mode-toggle .icon {
            background-color: #e0e0e0;
        }

        html.dark .dark-mode-toggle .icon-moon {
            transform: rotate(0) scale(1);
            opacity: 1;
        }

        html.dark .dark-mode-toggle .icon-sun {
            transform: rotate(180deg) scale(0);
            opacity: 0;
        }

        /* ========================================
           DARK MODE OVERRIDES - Custom Elements
           ======================================== */
        
        html {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        html.dark {
            color-scheme: dark;
        }

        html.dark body {
            background-color: #1a1a1a;
            color: #e0e0e0;
        }

        html.dark .wp-editor-container {
            background: #2d2d2d;
            border-color: #444;
        }

        html.dark .wp-toolbar {
            background: #333;
            border-color: #444;
        }

        html.dark .toolbar-actions {
            background: #3a3a3a;
            border-color: #555;
        }

        html.dark .toolbar-btn {
            color: #e0e0e0;
        }

        html.dark .toolbar-btn:hover {
            background: #4a4a4a;
        }

        html.dark .toolbar-btn.active {
            background: #555;
        }

        html.dark .toolbar-btn .icon {
            background-color: #ccc;
        }

        html.dark .toolbar-btn:hover .icon {
            background-color: #fff;
        }

        html.dark .toolbar-select {
            background: #3a3a3a;
            color: #e0e0e0;
            border-color: #555;
        }

        html.dark .wp-content-area {
            background-color: #2d2d2d;
            color: #e0e0e0;
        }

        html.dark .wp-content-area:empty:before {
            color: #666;
        }

        html.dark .meta-box {
            background: #2d2d2d;
            border-color: #444;
        }

        html.dark .meta-box .card-header {
            background: #333;
            border-color: #444;
            color: #e0e0e0;
        }

        html.dark .meta-box .card-body {
            color: #ccc;
        }

        html.dark .editor-modal-overlay,
        html.dark #media-modal-overlay {
            background: rgba(0, 0, 0, 0.85);
        }
        
        html.dark #colorPickerModal {
            background: #2d2d2d;
            border-color: #555;
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
        }

        html.dark .editor-modal {
            background: #2d2d2d;
            border-color: #555;
            color: #e0e0e0;
        }

        html.dark .editor-input {
            background: #3a3a3a;
            color: #e0e0e0;
            border-color: #555;
        }
        
        html.dark .color-section h5 {
            color: #e0e0e0;
        }

        /* PRISM.JS OVERRIDES */
        .wp-content-area pre[class*="language-"] {
            margin: 1rem 0;
            border-radius: 4px;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
        }

        .wp-content-area code[class*="language-"] {
            font-family: 'Courier New', Consolas, Monaco, monospace;
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .wp-content-area code:not([class*="language-"]) {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
        }

        .wp-content-area pre.line-numbers {
            padding-left: 3.8em;
        }

        .wp-content-area pre {
            white-space: pre-wrap;
            word-wrap: break-word;
            cursor: text;
        }

        html.dark .wp-content-area code:not([class*="language-"]) {
            background: #3a3a3a;
            color: #e0e0e0;
        }

        html.dark .wp-content-area pre[class*="language-"] {
            border-color: #555 !important;
        }

        #code-content-input {
            font-family: 'Courier New', Consolas, Monaco, monospace !important;
            tab-size: 4;
            -moz-tab-size: 4;
        }

        html.dark #code-content-input {
            background: #2d2d2d;
            color: #e0e0e0;
        }

        html.dark #code-language-select {
            background: #3a3a3a;
            color: #e0e0e0;
        }

        /* Image Toolbar Dark Mode */
        html.dark .image-toolbar {
            background: #2d2d2d;
            border-color: #555;
        }

        html.dark .section-label {
            color: #999;
        }

        html.dark .section-buttons {
            background: #3a3a3a;
        }

        html.dark .img-tool-btn {
            color: #e0e0e0;
        }

        html.dark .img-tool-btn:hover {
            background: #4a4a4a;
        }

        html.dark .img-tool-btn.active {
            background: #666;
            color: #fff;
        }
        
        html.dark .img-tool-btn .icon {
            background-color: #ccc;
        }
        
        html.dark .img-tool-btn.active .icon {
             background-color: #fff !important;
        }

        /* Media Manager Dark Mode */
        html.dark .media-tabs {
            border-color: #555;
        }

        html.dark .media-tab {
            color: #ccc;
        }

        html.dark .media-tab.active {
            border-color: #888;
            color: #fff;
        }

        html.dark .upload-area {
            background: #3a3a3a;
            border-color: #555;
            color: #ccc;
        }

        html.dark .upload-area:hover {
            background: #444;
        }

        html.dark .media-item {
            border-color: #555;
        }

        html.dark .media-item:hover {
            border-color: #888;
        }

        /* Word Count */
        #word-count-display {
            background: #f9f9f9;
        }

        html.dark #word-count-display {
            background: #333 !important;
            color: #999;
            border-color: #444;
        }

        /* Line Height & Letter Spacing Icons Dark Mode */
        html.dark .wp-toolbar .toolbar-select + div .icon {
            background-color: #e0e0e0;
        }

        /* Add Media Button Icon Dark Mode */
        html.dark .paper-btn .icon {
            background-color: #e0e0e0;
        }

        /* Folder Icon for Media Modal */
        .folder-icon {
            display: inline-block;
            width: 48px;
            height: 48px;
            background-color: #999;
            mask-image: url('../assets/icons/folder-empty.svg');
            -webkit-mask-image: url('../assets/icons/folder-empty.svg');
            mask-size: contain;
            -webkit-mask-size: contain;
            mask-repeat: no-repeat;
            -webkit-mask-repeat: no-repeat;
            mask-position: center;
            -webkit-mask-position: center;
            margin-bottom: 5px;
            vertical-align: middle;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        html.dark .folder-icon {
            background-color: #ccc;
        }

        /* Links */
        html.dark a {
            color: #6db3f2;
        }

        html.dark a:hover {
            color: #8ec5f2;
        }
        
        /* Buttons */
        html.dark .paper-btn {
            background: #3a3a3a;
            color: #e0e0e0;
            border-color: #555;
        }
        
        html.dark .paper-btn:hover {
             background: #4a4a4a;
        }
        
        html.dark .paper-btn.btn-primary {
            background: #4a7c59;
            color: #fff;
        }

        /* Form inputs */
        html.dark input[type="text"], 
        html.dark input[type="number"], 
        html.dark textarea {
            background: #3a3a3a;
            color: #e0e0e0;
            border-color: #555;
        }

        .section-buttons {
            display: flex;
            gap: 2px;
            background: #f5f5f5;
            padding: 2px;
            border-radius: 4px;
        }

        .img-tool-btn {
            background: transparent;
            border: none;
            padding: 4px 8px;
            font-size: 0.8rem;
            cursor: pointer;
            border-radius: 3px;
            font-family: 'Neucha', sans-serif;
            transition: all 0.15s ease;
            min-width: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .img-tool-btn:hover {
            background: #e0e0e0;
        }

        .img-tool-btn.active {
            background: #41403e;
            color: #fff;
        }
        
        .img-tool-btn.active .icon {
            background-color: #fff !important;
        }

        .toggle-btn {
            padding: 4px 10px;
        }

        /* Paper CSS Image Utilities */
        img.float-left {
            float: left;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }

        img.float-right {
            float: right;
            margin-left: 1rem;
            margin-bottom: 0.5rem;
        }

        img.no-border {
            border: none !important;
            box-shadow: none !important;
        }

        .wp-content-area p::after {
            content: "";
            display: table;
            clear: both;
        }

        .wp-content-area { 
            height: 600px; 
            overflow-y: auto; 
            padding: 40px; 
            outline: none; 
            font-size: 1.2rem; line-height: 1.8;
            border-bottom-left-radius: 15px 255px;
            border-bottom-right-radius: 225px 15px;
            width: 100%;
            box-sizing: border-box;
            background-color: #fff;
        }
        
        .toolbar-input {
            width: 60px;
            text-align: center;
        }

        .toolbar-group-label {
            font-family: 'Neucha', sans-serif;
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 2px;
            padding-left: 2px;
            letter-spacing: 0.5px;
        }
        
        .wp-content-area:empty:before {
            content: attr(placeholder);
            color: #aaa;
            pointer-events: none;
            display: block; 
            font-style: italic;
        }

        /* Sidebar Meta Boxes */
        .meta-box { margin-bottom: 20px; background: #fff; border: 1px solid #ddd; }
        .meta-box .card-header { font-size: 1rem; font-weight: bold; padding: 12px 15px; border-bottom: 1px solid #eee; background: #fafafa; }
        .meta-box .card-body { padding: 15px; font-size: 0.9rem; }
        
        /* Utility */
        .flex-between { display: flex; justify-content: space-between; align-items: center; }
        .full-width { width: 100%; }
        .display-flex { display: flex; }
        .align-center { align-items: center; }
        
        @media (max-width: 768px) { 
            .wp-toolbar { gap: 10px; }
            .toolbar-group-label { display: none; }
        }
        
        /* Fix grid for editor layout */
        .row { display: flex; flex-wrap: wrap; }
        .col.md-9 { flex: 0 0 75%; max-width: 75%; min-width: 0; overflow: hidden; }
        .col.md-3 { flex: 0 0 25%; max-width: 25%; }
        @media (max-width: 768px) {
            .col.md-9, .col.md-3 { flex: 0 0 100%; max-width: 100%; }
        }
    </style>
</head>
<body>

    <nav class="border fixed split-nav">
        <div class="nav-brand">
            <h4>PaperCMS</h4>
        </div>
        <div class="collapsible">
            <input id="collapsible1" type="checkbox" name="collapsible1">
            <label for="collapsible1">Menu</label>
            <div class="collapsible-body">
                <ul class="inline">
                    <!-- Cleaned up nav items -->
                </ul>
            </div>
        </div>
    </nav>
    <div style="height: 80px;"></div>
    
    <div class="container container-lg">
        <div class="wp-header margin-bottom-large">
            <h2>Add New Post</h2>
        </div>

        <div class="row">
            <!-- Main Content: Editor (9 columns on medium screens, 12 on small) -->
            <div class="col sm-12 md-9" style="min-width: 0;">
                
                <div class="form-group margin-bottom">
                    <input type="text" class="input-block wp-title-input" placeholder="Enter title here" style="font-size: 1.5rem;">
                </div>

                <div class="margin-bottom-small">
                    <button class="paper-btn btn-small" onclick="window.paperEditor.triggerMediaUpload()" title="Insert Media">
                        <span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_image.svg'); mask-image: url('../assets/icons/ui-icon_image.svg'); vertical-align: middle;"></span> Add Media
                    </button>
                    <!-- Input removed, using #mediaFileInput in modal -->
                </div>

                <div class="paper wp-editor-container padding-none margin-bottom-large">
                    <div class="wp-toolbar" style="display: flex; flex-direction: column; gap: 5px;">
                        
                        <!-- Row 1: Style Controls -->
                        <div class="toolbar-row" style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                            <!-- Undo/Redo -->
                            <button class="toolbar-btn" onclick="document.execCommand('undo')" title="Undo"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_undo.svg'); mask-image: url('../assets/icons/ui-icon_undo.svg');"></span></button>
                            <button class="toolbar-btn" onclick="document.execCommand('redo')" title="Redo"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_redo.svg'); mask-image: url('../assets/icons/ui-icon_redo.svg');"></span></button>
                            
                            <span class="toolbar-divider"></span>
                            
                            <!-- Format Block -->
                            <select class="toolbar-select" id="formatBlockSelect" style="font-family: 'Neucha', sans-serif; width: 110px;" onchange="document.execCommand('formatBlock', false, this.value);">
                                <option value="p" selected>Paragraph</option>
                                <option value="h1">Heading 1</option>
                                <option value="h2">Heading 2</option>
                                <option value="h3">Heading 3</option>
                                <option value="h4">Heading 4</option>
                                <option value="blockquote">Quote</option>
                                <option value="pre">Code</option>
                            </select>
                            
                            <!-- Font Family -->
                            <select class="toolbar-select" style="width: 110px;" onchange="document.execCommand('fontName', false, this.value); this.value='0';">
                                <option value="0">Font Family</option>
                                <option value="Neucha">Neucha</option>
                                <option value="Notepen">Notepen</option>
                                <option value="Arial">Arial</option>
                                <option value="Georgia">Georgia</option>
                                <option value="Courier New">Courier</option>
                                <option value="Times New Roman">Times</option>
                            </select>
                            
                            <!-- Font Size -->
                            <select class="toolbar-select" id="fontSizeSelect" style="width:60px;">
                                <option value="">Size</option>
                                <option value="10px">10</option>
                                <option value="12px">12</option>
                                <option value="14px">14</option>
                                <option value="16px">16</option>
                                <option value="18px">18</option>
                                <option value="20px">20</option>
                                <option value="24px">24</option>
                                <option value="32px">32</option>
                                <option value="48px">48</option>
                            </select>
                            
                            <span class="toolbar-divider"></span>
                            
                            <!-- B/I/U/S -->
                            <button class="toolbar-btn" data-command="bold" title="Bold"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_bold.svg'); mask-image: url('../assets/icons/ui-icon_bold.svg');"></span></button>
                            <button class="toolbar-btn" data-command="italic" title="Italic"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_italic.svg'); mask-image: url('../assets/icons/ui-icon_italic.svg');"></span></button>
                            <button class="toolbar-btn" data-command="underline" title="Underline"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_underline.svg'); mask-image: url('../assets/icons/ui-icon_underline.svg');"></span></button>
                            <button class="toolbar-btn" data-command="strikethrough" title="Strikethrough"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_Strikethrough.svg'); mask-image: url('../assets/icons/ui-icon_Strikethrough.svg');"></span></button>
                            <button class="toolbar-btn" data-command="superscript" title="Superscript"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_superscript.svg'); mask-image: url('../assets/icons/ui-icon_superscript.svg');"></span></button>
                            
                            <!-- Color Picker -->
                            <div style="position: relative; display: inline-block;">
                                <button class="toolbar-btn" id="btn-color-picker" onclick="window.paperEditor.toggleColorPalette(this)" title="Text/BG Color">
                                    <span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_text-color.svg'); mask-image: url('../assets/icons/ui-icon_text-color.svg');"></span>
                                    <span style="font-size: 8px;">▼</span>
                                </button>
                                <!-- Color Picker Modal -->
                                <div id="colorPickerModal" class="color-picker-modal">
                                    <div class="color-picker-row">
                                        <div class="color-section">
                                            <h5>Background Color</h5>
                                            <button class="reset-btn" onclick="window.paperEditor.applyColor('hiliteColor', 'transparent')">Transparent</button>
                                            <div class="color-grid" id="bgColorGrid"></div>
                                        </div>
                                        <div class="color-section">
                                            <h5>Text Color</h5>
                                            <button class="reset-btn" onclick="window.paperEditor.applyColor('foreColor', 'inherit')">Reset</button>
                                            <div class="color-grid" id="textColorGrid"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <span class="toolbar-divider"></span>
                            
                            <!-- Line Height -->
                            <div style="position: relative; display: inline-block;">
                                <select class="toolbar-select" style="width: 60px; appearance: none; padding-left: 28px;" onchange="window.paperEditor.applyLineHeight(this.value)" title="Line Height">
                                    <option value="1.0">1.0</option>
                                    <option value="1.2">1.2</option>
                                    <option value="1.5" selected>1.5</option>
                                    <option value="1.8">1.8</option>
                                    <option value="2.0">2.0</option>
                                </select>
                                <div style="position:absolute; left:5px; top:50%; transform:translateY(-50%); pointer-events:none;">
                                    <span class="icon" style="mask-image: url('../assets/icons/ui-icon_line-height.svg'); -webkit-mask-image: url('../assets/icons/ui-icon_line-height.svg'); width:16px; height:16px;"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Row 2: Paragraph & Insert -->
                        <div class="toolbar-row" style="display: flex; flex-wrap: wrap; gap: 5px; align-items: center;">
                            <!-- Lists -->
                            <button class="toolbar-btn" data-command="insertUnorderedList" title="Bullet List"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_bullet.svg'); mask-image: url('../assets/icons/ui-icon_bullet.svg');"></span></button>
                            <button class="toolbar-btn" data-command="insertOrderedList" title="Numbered List"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_numbering.svg'); mask-image: url('../assets/icons/ui-icon_numbering.svg');"></span></button>
                            
                            <span class="toolbar-divider"></span>
                            
                            <!-- Alignment -->
                            <button class="toolbar-btn" data-command="justifyLeft" title="Align Left"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_align-left.svg'); mask-image: url('../assets/icons/ui-icon_align-left.svg');"></span></button>
                            <button class="toolbar-btn" data-command="justifyCenter" title="Align Center"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_align-center.svg'); mask-image: url('../assets/icons/ui-icon_align-center.svg');"></span></button>
                            <button class="toolbar-btn" data-command="justifyRight" title="Align Right"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_align-right.svg'); mask-image: url('../assets/icons/ui-icon_align-right.svg');"></span></button>
                            <button class="toolbar-btn" data-command="justifyFull" title="Justify"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_align-justify.svg'); mask-image: url('../assets/icons/ui-icon_align-justify.svg');"></span></button>
                            
                            <!-- Letter Spacing -->
                            <div style="position: relative; display: inline-block;">
                                <select class="toolbar-select" style="width: 55px; appearance: none; padding-left: 26px;" onchange="window.paperEditor.applyLetterSpacing(this.value)" title="Letter Spacing">
                                    <option value="normal" selected>0</option>
                                    <option value="1px">1</option>
                                    <option value="2px">2</option>
                                    <option value="3px">3</option>
                                    <option value="-1px">-1</option>
                                </select>
                                <div style="position:absolute; left:4px; top:50%; transform:translateY(-50%); pointer-events:none;">
                                    <span class="icon" style="mask-image: url('../assets/icons/ui-icon_letter-spacing.svg'); -webkit-mask-image: url('../assets/icons/ui-icon_letter-spacing.svg'); width:16px; height:16px;"></span>
                                </div>
                            </div>
                            
                            <span class="toolbar-divider"></span>
                            
                            <!-- Insert: Link, Image, Video, YouTube, Table, Code, HR -->
                            <button class="toolbar-btn" id="btn-link" data-command="link" onclick="window.paperEditor.promptLink()" title="Insert Link"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_link.svg'); mask-image: url('../assets/icons/ui-icon_link.svg');"></span></button>
                            <button class="toolbar-btn" onclick="window.paperEditor.triggerImageUpload()" title="Insert Image"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_image.svg'); mask-image: url('../assets/icons/ui-icon_image.svg');"></span></button>
                            <button class="toolbar-btn" onclick="window.paperEditor.triggerVideoUpload()" title="Insert Video"><span class="icon" style="mask-image: url('../assets/icons/ui-icon_video.svg'); -webkit-mask-image: url('../assets/icons/ui-icon_video.svg');"></span></button>
                            <button class="toolbar-btn" onclick="window.paperEditor.insertEmbedVideo()" title="Embed YouTube"><span class="icon" style="mask-image: url('../assets/icons/youtube.svg'); -webkit-mask-image: url('../assets/icons/youtube.svg');"></span></button>
                            <button class="toolbar-btn" onclick="window.paperEditor.insertTable()" title="Insert Table"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_table.svg'); mask-image: url('../assets/icons/ui-icon_table.svg');"></span></button>
                            <button class="toolbar-btn" onclick="window.paperEditor.insertCodeBlock()" title="Insert Code Block"><span class="icon" style="mask-image: url('../assets/icons/ui-icon_code.svg'); -webkit-mask-image: url('../assets/icons/ui-icon_code.svg');"></span></button>
                            <button class="toolbar-btn" onclick="document.execCommand('insertHorizontalRule')" title="Horizontal Line"><span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_divider.svg'); mask-image: url('../assets/icons/ui-icon_divider.svg');"></span></button>
                            
                            <span class="toolbar-divider"></span>
                            
                            <!-- Clear Formatting -->
                            <button class="toolbar-btn" onclick="document.execCommand('removeFormat')" title="Clear Formatting" style="padding: 0 8px; gap: 4px;">
                                <span class="icon" style="-webkit-mask-image: url('../assets/icons/eraser.svg'); mask-image: url('../assets/icons/eraser.svg'); width:14px; height:14px;"></span>
                                <span style="font-size: 10px; font-weight: 500;">CLEAR</span>
                            </button>
                        </div>
                        
                    </div>

                    <div class="wp-content-area" contenteditable="true" placeholder="Tulis cerita sketsamu di sini..."></div>
                    
                    <div id="word-count-display" class="border-top padding-small text-muted" style="font-size: 0.8rem;">
                        Word count: 0
                    </div>
                </div>

            </div>

            <!-- Sidebar Content: Meta boxes (3 columns on medium screens, 12 on small) -->
            <div class="col sm-12 md-3">
                
    <!-- Sidebar Content -->
    
    <!-- Publish Box -->
    <div class="card meta-box">
        <div class="card-header flex-between">
            <span>Publish</span>
        </div>
        <div class="card-body">
            <div class="row flex-between margin-bottom">
                <button class="paper-btn btn-small" id="btn-save-draft">Save Draft</button>
                <button class="paper-btn btn-small" id="btn-preview">Preview</button>
            </div>
            
            <div class="margin-bottom-small">
                📍 Status: <b id="post-status">Draft</b>
            </div>
            <div class="margin-bottom-small">
                👁️ Visibility: <b>Public</b>
            </div>
            <div class="margin-bottom-small">
                📅 Publish: <b>Immediately</b>
            </div>

            <div class="row flex-between margin-top-large border-top padding-top-small">
                <a href="#" class="text-danger" id="btn-trash">Move to Trash</a>
                <button class="paper-btn btn-primary" id="btn-publish">Publish</button>
            </div>
        </div>
    </div>

    <!-- Categories Box -->
    <div class="card meta-box">
        <div class="card-header">Categories</div>
        <div class="card-body">
            <fieldset class="form-group">
                <div id="categories-list" class="categories-list" style="max-height: 200px; overflow-y: auto;">
                    <!-- Loaded via JS -->
                    <p>Loading...</p>
                </div>
            </fieldset>
            <a href="#" onclick="postEditor.addCategory(); return false;">+ Add New Category</a>
        </div>
    </div>

    <!-- Tags Box -->
    <div class="card meta-box">
        <div class="card-header">Tags</div>
        <div class="card-body">
            <div id="selected-tags" class="selected-tags margin-bottom-small">
                <!-- Selected tags appear here -->
            </div>
            <div class="form-group">
                <input type="text" id="tag-input" class="input-block margin-bottom-small" placeholder="Add tags...">
                <button class="paper-btn btn-small btn-block" id="tag-add-btn">Add</button>
            </div>
            <small class="text-muted">Separate tags with commas</small>
        </div>
    </div>

    <!-- Featured Image Box -->
    <div class="card meta-box">
        <div class="card-header">Featured Image</div>
        <div class="card-body">
            <div id="featured-image-preview" style="display:none;"></div>
            <div id="featured-image-placeholder">
                <a href="#" id="set-featured-image">Set featured image</a>
            </div>
            <button class="paper-btn btn-small btn-danger" id="remove-featured-image" style="display:none; margin-top:10px;">Remove</button>
        </div>
    </div>

            </div>
        </div>
    </div>

    <!-- Generic Editor Modal -->
    <div class="editor-modal-overlay" id="editor-modal-overlay">
        <div class="editor-modal">
            <h3 id="editor-modal-title">Dialog</h3>
            <div class="editor-modal-body" id="editor-modal-body"></div>
            <div class="editor-modal-footer">
                <button class="paper-btn" id="editor-modal-cancel">Cancel</button>
                <button class="paper-btn btn-primary" id="editor-modal-confirm">OK</button>
            </div>
        </div>
    </div>

    <!-- Media Manager Modal -->
    <div class="editor-modal-overlay" id="media-modal-overlay" style="display:none;">
        <div class="editor-modal" style="width: 600px; max-width: 95%;">
            <h3>Insert Media</h3>
            <div class="media-tabs">
                <button class="media-tab active" data-tab="upload">Upload</button>
                <button class="media-tab" data-tab="library">Media Library</button>
            </div>
            <div class="media-pane active" id="tab-upload">
                <div class="upload-area" id="upload-drop-area">
                    <p>📁 Drag files here or click to upload</p>
                    <input type="file" id="mediaFileInput" accept="image/*,video/*" multiple style="display:none;">
                </div>
                <div id="upload-progress" style="margin-top:10px;"></div>
            </div>
            <div class="media-pane" id="tab-library">
                <div class="media-grid" id="media-grid">
                    <!-- JS loads items -->
                </div>
            </div>
            <div class="editor-modal-footer" style="margin-top: 1rem;">
                <button class="paper-btn" onclick="document.getElementById('media-modal-overlay').style.display='none'">Close</button>
            </div>
        </div>
    </div>

    <!-- Image Resize Toolbar (appears when clicking images/videos) -->
    <div id="resize-toolbar" class="image-toolbar" style="display:none;">
        <!-- Size Section -->
        <div class="toolbar-section">
            <span class="section-label">Size</span>
            <div class="section-buttons">
                <button class="img-tool-btn" data-action="size" data-value="25%">S</button>
                <button class="img-tool-btn" data-action="size" data-value="50%">M</button>
                <button class="img-tool-btn" data-action="size" data-value="75%">L</button>
                <button class="img-tool-btn" data-action="size" data-value="100%">XL</button>
            </div>
        </div>

        <!-- Float Section -->
        <div class="toolbar-section">
            <span class="section-label">Float</span>
            <div class="section-buttons">
                <button class="img-tool-btn" data-action="float" data-value="float-left" title="Float Left">
                    <span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_align-left.svg'); mask-image: url('../assets/icons/ui-icon_align-left.svg'); width:16px; height:16px;"></span>
                </button>
                <button class="img-tool-btn" data-action="float" data-value="none" title="No Float">—</button>
                <button class="img-tool-btn" data-action="float" data-value="float-right" title="Float Right">
                    <span class="icon" style="-webkit-mask-image: url('../assets/icons/ui-icon_align-right.svg'); mask-image: url('../assets/icons/ui-icon_align-right.svg'); width:16px; height:16px;"></span>
                </button>
            </div>
        </div>

        <!-- Border Toggle -->
        <div class="toolbar-section">
            <span class="section-label">Border</span>
            <div class="section-buttons">
                <button class="img-tool-btn toggle-btn" data-action="border">Hide</button>
            </div>
        </div>
    </div>

    <!-- Dark Mode Toggle Button (Fixed Position) -->
    <button class="dark-mode-toggle fixed-toggle" id="darkModeToggle" title="Toggle Dark Mode">
        <span class="icon icon-sun" style="-webkit-mask-image: url('../assets/icons/ui-icon_sun.svg'); mask-image: url('../assets/icons/ui-icon_sun.svg');"></span>
        <span class="icon icon-moon" style="-webkit-mask-image: url('../assets/icons/ui-icon_moon.svg'); mask-image: url('../assets/icons/ui-icon_moon.svg');"></span>
    </button>

    <!-- Prism.js (Local) -->
    <script src="../assets/js/prism/prism.min.js"></script>
    <script src="../assets/js/prism/prism-line-numbers.min.js"></script>
    <script src="../assets/js/prism/prism-markup-templating.min.js"></script>
    <script src="../assets/js/prism/prism-php.min.js"></script>
    <script src="../assets/js/prism/prism-javascript.min.js"></script>
    <script src="../assets/js/prism/prism-css.min.js"></script>
    <script src="../assets/js/prism/prism-python.min.js"></script>
    <script src="../assets/js/prism/prism-bash.min.js"></script>
    <script src="../assets/js/prism/prism-sql.min.js"></script>
    <script src="../assets/js/prism/prism-json.min.js"></script>
    
    <!-- Custom Scripts (Admin versions with updated API paths) -->
    <script src="js/dark-mode.js"></script>
    <script src="js/paper-toolbar.js"></script>
    <script src="js/editor.js"></script>
    <script src="js/post-editor.js"></script>
</body>
</html>
