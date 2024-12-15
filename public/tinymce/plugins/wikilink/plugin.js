tinymce.PluginManager.add('wikilink', function (editor) {
    // 注册工具栏按钮
    editor.ui.registry.addButton('wikilink', {
        text: 'Wiki链接',
        tooltip: '插入Wiki页面链接',
        onAction: function () {
            showWikiLinkDialog();
        }
    });

    // 注册快捷键
    editor.addShortcut('Meta+L', 'Insert wiki link', function () {
        showWikiLinkDialog();
    });

    function showWikiLinkDialog() {
        editor.windowManager.open({
            title: '插入Wiki链接',
            body: {
                type: 'panel',
                items: [{
                    type: 'input',
                    name: 'pageTitle',
                    label: '页面标题',
                    placeholder: '输入页面标题...'
                }]
            },
            buttons: [
                {
                    type: 'cancel',
                    text: '取消'
                },
                {
                    type: 'submit',
                    text: '插入',
                    primary: true
                }
            ],
            initialData: {
                pageTitle: editor.selection.getContent({ format: 'text' })
            },
            onSubmit: function (api) {
                const data = api.getData();
                if (data.pageTitle.trim()) {
                    editor.insertContent(`[[${data.pageTitle.trim()}]]`);
                }
                api.close();
            }
        });
    }

    // 添加 Wiki 链接样式
    editor.on('init', function () {
        editor.dom.addStyle(
            `span.mce-wikilink {
                color: #2563eb;
                text-decoration: none;
                border-bottom: 1px dashed #2563eb;
                cursor: pointer;
            }
            span.mce-wikilink:hover {
                border-bottom-style: solid;
            }`
        );
    });

    // 为 Wiki 链接添加特殊样式
    editor.on('BeforeSetContent', function (e) {
        e.content = e.content.replace(
            /\[\[([^\]]+)\]\]/g,
            '<span class="mce-wikilink" data-wiki-link="$1">$1</span>'
        );
    });

    // 保存时恢复 Wiki 链接语法
    editor.on('GetContent', function (e) {
        e.content = e.content.replace(
            /<span class="mce-wikilink" data-wiki-link="([^"]+)">[^<]*<\/span>/g,
            '[[$1]]'
        );
    });
});