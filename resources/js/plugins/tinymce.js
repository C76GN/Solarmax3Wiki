export const useEditor = (customConfig = {}) => {
    // 基础配置
    const baseConfig = {
        language: 'zh_CN',
        base_url: '/tinymce',
        suffix: '.min',
        skin: 'oxide',
        height: 500,
        external_plugins: {
            'wikilink': '/tinymce/plugins/wikilink/plugin.min.js'
        },
        menubar: 'file edit view insert format tools table help',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'accordion', 'codesample', 'directionality',
            'nonbreaking', 'pagebreak', 'save', 'visualchars'
        ],
        toolbar: 'undo redo | styles | bold italic underline strikethrough | ' +
            'fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | ' +
            'outdent indent | numlist bullist | forecolor backcolor removeformat | ' +
            'charmap emoticons | paste code fullscreen preview | ' +
            'image media table link anchor | tablecontrols',
        content_style: `
            body {
                font-family: 'Segoe UI', Roboto, sans-serif;
                font-size: 16px;
                line-height: 1.8;
                padding: 1rem;
                color: #333;
                background: #fafafa;
            }
            p { margin: 0 0 1em; }
            img { max-width: 100%; height: auto; }
            pre {
                background: #282c34;
                color: #ffffff;
                padding: 10px;
                border-radius: 5px;
                overflow-x: auto;
            }
            table {
                border-collapse: collapse;
                width: 100%;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            h1, h2, h3, h4, h5, h6 {
                margin-top: 1em;
                margin-bottom: 0.5em;
                font-weight: 600;
            }
            h1 { font-size: 2em; }
            h2 { font-size: 1.5em; }
            h3 { font-size: 1.3em; }
            h4 { font-size: 1.1em; }
            blockquote {
                border-left: 3px solid #ccc;
                margin-left: 0;
                padding-left: 1em;
                color: #555;
            }
            .mce-wikilink {
                color: #0645ad;
                text-decoration: none;
                background-color: #eaf3ff;
                padding: 0 2px;
                border-radius: 2px;
            }
            .mce-wikilink:hover {
                text-decoration: underline;
            }
        `,
        branding: false,
        autosave_interval: '30s',
        autosave_prefix: '{path}{query}-{id}-',
        quickbars_selection_toolbar: 'bold italic | h2 h3 | blockquote link wikilink',
        quickbars_insert_toolbar: false,
        setup: function (editor) {
            editor.ui.registry.addButton('previewToggle', {
                icon: 'preview',
                tooltip: '预览/编辑切换',
                onAction: function () {
                    const container = editor.getContainer();
                    if (container.classList.contains('preview-mode')) {
                        container.classList.remove('preview-mode');
                        editor.setMode('design');
                    } else {
                        container.classList.add('preview-mode');
                        editor.setMode('readonly');
                    }
                }
            });
        },
        license_key: 'gpl',
        toc_header: 'h2,h3,h4',
        toc_class: 'wiki-toc',
        autosave_restore_when_empty: true,
        autosave_ask_before_unload: true,
        formats: {
            wikilink: { inline: 'span', classes: 'mce-wikilink' }
        }
    };

    // 合并自定义配置
    const init = { ...baseConfig, ...customConfig };

    // 如果提供了自定义 setup 函数，保留原始 setup 并合并
    if (customConfig.setup) {
        const baseSetup = baseConfig.setup;
        init.setup = function (editor) {
            baseSetup(editor);
            customConfig.setup(editor);
        };
    }

    return { init };
};