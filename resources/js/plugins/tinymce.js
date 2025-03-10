export const useEditor = () => {
    const init = {
        language: 'zh_CN',
        base_url: '/tinymce', // 自托管 TinyMCE 资源路径
        skin: 'oxide',
        height: 500,
        menubar: 'file edit view insert format tools table help',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'accordion', 'codesample', 'directionality',
            'nonbreaking', 'pagebreak', 'save', 'visualchars'
        ],
        toolbar: 'undo redo | bold italic underline strikethrough | ' +
            'fontfamily fontsize blocks | alignleft aligncenter alignright alignjustify | ' +
            'outdent indent | numlist bullist | forecolor backcolor removeformat | ' +
            'charmap emoticons | paste code fullscreen preview | ' +
            'image media table link anchor | tablecontrols | accordion codesample directionality nonbreaking pagebreak save visualchars | help',
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
        `,
        branding: false,
        setup: (editor) => {
            editor.on('init', () => console.log('TinyMCE 初始化完成'));
            editor.addShortcut('ctrl+s', '保存文章', () => console.log('保存文章'));
            editor.on('change', () => console.log('内容发生变化'));
        },
        license_key: 'gpl' // 适用于自托管版本
    };
    return { init };
};
