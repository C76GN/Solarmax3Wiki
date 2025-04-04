// 新建文件: resources/js/utils/contentProcessor.js

/**
 * 处理由 TipTap 编辑器生成的内容，使其在前端正确显示
 * @param {string} content 需要处理的 HTML 内容
 * @param {Object} options 处理选项
 * @returns {string} 处理后的 HTML 内容
 */
export function processTiptapContent(content, options = {}) {
    if (!content) return '';
    const {
        parseHeadings = true,      // 是否解析标题并生成 ID
        processWikiLinks = true,   // 是否处理 WikiLinks
        addTableClasses = true,    // 是否为表格添加样式类
        makeLinksExternal = false  // 是否将链接设为在新窗口打开
    } = options;
    let processedContent = content;
    // 创建临时 DOM 元素解析 HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = processedContent;
    if (processWikiLinks) {
        const wikiLinks = tempDiv.querySelectorAll('span[data-type="wiki-link"]');
        wikiLinks.forEach(link => {
            const title = link.getAttribute('data-title');
            if (title) {
                const a = document.createElement('a');
                a.href = `/wiki/search?q=${encodeURIComponent(title)}`;
                a.className = 'wiki-link';
                a.textContent = `[[${title}]]`;
                link.parentNode.replaceChild(a, link);
            }
        });
    }

    // 处理标题，为它们添加 ID 以支持 TOC 功能
    if (parseHeadings) {
        const headings = tempDiv.querySelectorAll('h1, h2, h3, h4, h5, h6');
        const headingsData = [];

        headings.forEach(heading => {
            const text = heading.textContent;
            const level = parseInt(heading.tagName.substring(1));
            const id = text.toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^\w\-]+/g, '')
                .replace(/\-\-+/g, '-')
                .replace(/^-+/, '')
                .replace(/-+$/, '');

            heading.id = id;
            headingsData.push({ id, text, level });
        });

        // 如果选项中提供了保存标题数据的函数，则调用它
        if (options.setHeadings && typeof options.setHeadings === 'function') {
            options.setHeadings(headingsData);
        }
    }

    // 为表格添加 CSS 类
    if (addTableClasses) {
        const tables = tempDiv.querySelectorAll('table');
        tables.forEach(table => {
            table.classList.add('min-w-full', 'border', 'border-gray-200');

            const ths = table.querySelectorAll('th');
            ths.forEach(th => {
                th.classList.add('px-4', 'py-2', 'border', 'bg-gray-100', 'text-left', 'font-medium');
            });

            const tds = table.querySelectorAll('td');
            tds.forEach(td => {
                td.classList.add('px-4', 'py-2', 'border');
            });
        });
    }

    // 处理外部链接
    if (makeLinksExternal) {
        const links = tempDiv.querySelectorAll('a:not(.wiki-link)');
        links.forEach(link => {
            if (!link.getAttribute('target') && !link.href.startsWith(window.location.origin)) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }
        });
    }

    return tempDiv.innerHTML;
}

/**
 * 解析 HTML 内容中的标题
 * @param {string} content HTML 内容
 * @returns {Array} 标题数组，包含 id, text, level
 */
export function parseHeadings(content) {
    if (!content) return [];

    const headings = [];
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = content;

    tempDiv.querySelectorAll('h1, h2, h3, h4, h5, h6').forEach(heading => {
        const text = heading.textContent;
        const level = parseInt(heading.tagName.substring(1));
        const id = heading.id || text.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');

        headings.push({ id, text, level });
    });

    return headings;
}