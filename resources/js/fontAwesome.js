import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faSearch, faUser, faBell, faUserGroup, faBarsStaggered,
    faXmark, faLinkSlash, faDownload, faEdit, faHistory,
    faPlus, faSave, faExclamationTriangle, faExclamationCircle, // 确保导入了 faExclamationCircle
    faLock, faEye, faPen, faBold, faItalic, faStrikethrough,
    faCode, faHeading, faListUl, faListOl, faQuoteLeft,
    faFileCode, faLink, faImage, faUndo, faRedo, faTimes,
    faInfoCircle, faTrash, faFile, faReply, faUsers, faTable,
    faColumns, faGripLines, faTrashAlt, faMinus, faUnderline,
    faHighlighter, faAlignCenter, faAlignLeft, faAlignRight,
    faAlignJustify, faPaperPlane, faSpinner, faWindowMinimize,
    faWindowMaximize, faComments, faArrowLeft, faCheck, // 确保导入了 faCheck
    faFolderOpen, faUserShield, faPlusCircle, faTags, faClipboardList,
    faFolder, faTag, faComment, faQuestionCircle, faArrowRight,
    faArrowUp, faArrowDown, faEraser, faChevronDown,
    faEyeSlash,
    faExternalLinkAlt,
    faSyncAlt,
    faCommentDots,
    faCommentSlash,
    faUserEdit,
    faCheckCircle // 新增导入 faCheckCircle
} from '@fortawesome/free-solid-svg-icons';
import { faGithub } from '@fortawesome/free-brands-svg-icons';
import { faFileAlt as faFileAltRegular } from '@fortawesome/free-regular-svg-icons';
import { faFileAlt } from '@fortawesome/free-solid-svg-icons'; // 确保导入了实心 faFileAlt

library.add(
    faSearch, faUser, faBell, faUserGroup, faBarsStaggered,
    faXmark, faLinkSlash, faDownload, faEdit, faHistory,
    faPlus, faSave, faExclamationTriangle, faExclamationCircle, // 确保添加了 faExclamationCircle
    faLock, faEye, faPen, faBold, faItalic, faStrikethrough,
    faCode, faHeading, faListUl, faListOl, faQuoteLeft,
    faFileCode, faLink, faImage, faUndo, faRedo, faTimes,
    faInfoCircle, faTrash, faFile, faReply, faUsers, faTable,
    faColumns, faGripLines, faTrashAlt, faMinus, faUnderline,
    faHighlighter, faAlignCenter, faAlignLeft, faAlignRight,
    faAlignJustify, faPaperPlane, faSpinner, faWindowMinimize,
    faWindowMaximize, faComments, faArrowLeft, faCheck, // 确保添加了 faCheck
    faFolderOpen, faUserShield, faPlusCircle, faTags, faClipboardList,
    faFolder, faTag, faComment, faQuestionCircle, faArrowRight,
    faArrowUp, faArrowDown, faEraser, faChevronDown,
    faEyeSlash,
    faExternalLinkAlt,
    faSyncAlt,
    faCommentDots,
    faCommentSlash,
    faUserEdit,
    faCheckCircle, // 新增添加到库
    faGithub,
    faFileAltRegular,
    faFileAlt // 确保添加了实心 faFileAlt
);

export { FontAwesomeIcon };