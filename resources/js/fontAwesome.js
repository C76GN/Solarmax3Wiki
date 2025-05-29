import { library } from '@fortawesome/fontawesome-svg-core'; // 导入 Font Awesome 图标库核心
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'; // 导入 Font Awesome Vue 组件

// 导入实心图标 (Solid Icons)
import {
    faSearch, faUser, faBell, faUserGroup, faBarsStaggered,
    faXmark, faLinkSlash, faDownload, faEdit, faHistory,
    faPlus, faSave, faExclamationTriangle, faExclamationCircle,
    faLock, faEye, faPen, faBold, faItalic, faStrikethrough,
    faCode, faHeading, faListUl, faListOl, faQuoteLeft,
    faFileCode, faLink, faImage, faUndo, faRedo, faTimes,
    faInfoCircle, faTrash, faFile, faReply, faUsers, faTable,
    faColumns, faGripLines, faTrashAlt, faMinus, faUnderline,
    faHighlighter, faAlignCenter, faAlignLeft, faAlignRight,
    faAlignJustify, faPaperPlane, faSpinner, faWindowMinimize,
    faWindowMaximize, faComments, faArrowLeft, faCheck,
    faFolderOpen, faUserShield, faPlusCircle, faTags, faClipboardList,
    faFolder, faTag, faComment, faQuestionCircle, faArrowRight,
    faArrowUp, faArrowDown, faEraser, faChevronDown,
    faEyeSlash,
    faExternalLinkAlt,
    faSyncAlt,
    faCommentDots,
    faCommentSlash,
    faUserEdit,
    faCheckCircle
} from '@fortawesome/free-solid-svg-icons';

// 导入品牌图标 (Brand Icons)
import { faGithub } from '@fortawesome/free-brands-svg-icons';

// 导入常规图标 (Regular Icons)
// faFileAltRegular 是一个别名，因为 faFileAlt 也在实心图标中
import { faFileAlt as faFileAltRegular } from '@fortawesome/free-regular-svg-icons';
// 再次导入实心版本，确保在 library.add 中引用正确
import { faFileAlt } from '@fortawesome/free-solid-svg-icons';

// 将所有导入的图标添加到 Font Awesome 图标库中
// 这样在应用程序中就可以通过名称使用这些图标
library.add(
    faSearch, faUser, faBell, faUserGroup, faBarsStaggered,
    faXmark, faLinkSlash, faDownload, faEdit, faHistory,
    faPlus, faSave, faExclamationTriangle, faExclamationCircle,
    faLock, faEye, faPen, faBold, faItalic, faStrikethrough,
    faCode, faHeading, faListUl, faListOl, faQuoteLeft,
    faFileCode, faLink, faImage, faUndo, faRedo, faTimes,
    faInfoCircle, faTrash, faFile, faReply, faUsers, faTable,
    faColumns, faGripLines, faTrashAlt, faMinus, faUnderline,
    faHighlighter, faAlignCenter, faAlignLeft, faAlignRight,
    faAlignJustify, faPaperPlane, faSpinner, faWindowMinimize,
    faWindowMaximize, faComments, faArrowLeft, faCheck,
    faFolderOpen, faUserShield, faPlusCircle, faTags, faClipboardList,
    faFolder, faTag, faComment, faQuestionCircle, faArrowRight,
    faArrowUp, faArrowDown, faEraser, faChevronDown,
    faEyeSlash,
    faExternalLinkAlt,
    faSyncAlt,
    faCommentDots,
    faCommentSlash,
    faUserEdit,
    faCheckCircle,
    faGithub, // 添加品牌图标
    faFileAltRegular, // 添加常规图标
    faFileAlt // 添加实心图标
);

// 导出 FontAwesomeIcon 组件，以便在 Vue 模板中使用
export { FontAwesomeIcon };