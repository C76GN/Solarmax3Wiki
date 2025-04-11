import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
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
    // 添加缺少的图标
    faFolderOpen, faUserShield, faPlusCircle, faTags, faClipboardList,
    faFolder, faTag, faComment, faQuestionCircle, faArrowRight
} from '@fortawesome/free-solid-svg-icons';
import { faGithub } from '@fortawesome/free-brands-svg-icons';
import { faFileAlt as faFileAltRegular } from '@fortawesome/free-regular-svg-icons';

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
    // 添加缺少的图标
    faFolderOpen, faUserShield, faPlusCircle, faTags, faClipboardList,
    faFolder, faTag, faComment, faQuestionCircle, faArrowRight,
    faGithub,
    faFileAltRegular
);

export { FontAwesomeIcon };