// FileName: /var/www/Solarmax3Wiki/resources/js/plugins/tinymce.js
export const useEditor = () => {
    const init = {
        language: 'zh_CN',
        base_url: '/tinymce',
        skin: 'oxide',
        // 删除 content_css 配置行
        height: 500,
        menubar: 'file edit view insert format tools table help',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'wikilink'
        ],
        toolbar: 'undo redo | bold italic underline strikethrough | ' +
            'fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | ' +
            'outdent indent | numlist bullist | forecolor backcolor removeformat | ' +
            'charmap emoticons | code fullscreen preview | ' +
            'image media table link anchor | wikilink | help',
        // 直接在这里定义编辑器内容样式
        content_style: `
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                font-size: 16px;
                line-height: 1.6;
                padding: 1rem;
            }
            p { margin: 0 0 1em; }
            img { max-width: 100%; height: auto; }
        `,
        branding: false,
        promotion: false,
        setup: (editor) => {
            editor.on('init', () => {
                // 编辑器初始化时的处理
            });
        },
        auto_destroy: false,
        remove_instance_on_destroy: false,
        license_key: 'gpl'
    }
    return {
        init
    }
}