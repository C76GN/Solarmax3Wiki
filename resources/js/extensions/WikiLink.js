import { Node, mergeAttributes } from '@tiptap/core'
export const WikiLink = Node.create({
    name: 'wikiLink',
    group: 'inline',
    inline: true,
    selectable: true,
    atom: true,
    addOptions() {
        return {
            HTMLAttributes: {},
        }
    },
    addAttributes() {
        return {
            title: {
                default: null
            }
        }
    },
    parseHTML() {
        return [
            {
                tag: 'span[data-type="wiki-link"]',
                getAttrs: element => ({
                    title: element.getAttribute('data-title'),
                }),
            },
        ]
    },
    renderHTML({ node, HTMLAttributes }) {
        return [
            'span',
            mergeAttributes(
                {
                    class: 'wiki-link',
                    'data-type': 'wiki-link',
                    'data-title': node.attrs.title
                },
                this.options.HTMLAttributes,
                HTMLAttributes
            ),
            `[[${node.attrs.title}]]`
        ]
    },
    addCommands() {
        return {
            setWikiLink: (title) => ({ chain }) => {
                return chain()
                    .insertContent({
                        type: this.name,
                        attrs: { title }
                    })
                    .run()
            }
        }
    },
    addKeyboardShortcuts() {
        return {
            'Mod-[': () => {
                const { view } = this.editor
                const { state } = view
                view.dispatch(state.tr.insertText('[['))
                return true
            }
        }
    },
    addNodeView() {
        return ({ node, HTMLAttributes, getPos }) => {
            const dom = document.createElement('span');
            dom.textContent = `[[${node.attrs.title}]]`;
            dom.classList.add('wiki-link');
            +           // 改进点击事件处理，使用正确的路由
                dom.addEventListener('click', (e) => {
                    +               e.preventDefault();
                    window.location.href = `/wiki/search?q=${encodeURIComponent(node.attrs.title)}`;
                });
            return { dom };
        };
    }
})