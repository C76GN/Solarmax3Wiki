import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// ✔️ Solid 图标
import {
    faSearch, faUser, faBell, faUserGroup, faBarsStaggered,
    faXmark, faLinkSlash, faDownload, faEdit, faHistory,
    faPlus, faSave, faExclamationTriangle, faExclamationCircle,
    faLock, faEye, faPen, faBold, faItalic, faStrikethrough,
    faCode, faHeading, faListUl, faListOl, faQuoteLeft,
    faFileCode, faLink, faImage, faUndo, faRedo, faTimes,
    faInfoCircle, faTrash, faFile
} from '@fortawesome/free-solid-svg-icons'

// ✔️ Brands 图标
import { faGithub } from '@fortawesome/free-brands-svg-icons'

// ✔️ Regular 图标（用于 far 风格的 file-alt）
import { faFileAlt as faFileAltRegular } from '@fortawesome/free-regular-svg-icons'

// 注册图标
library.add(
    // solid
    faSearch, faUser, faBell, faUserGroup, faBarsStaggered,
    faXmark, faLinkSlash, faDownload, faEdit, faHistory,
    faPlus, faSave, faExclamationTriangle, faExclamationCircle,
    faLock, faEye, faPen, faBold, faItalic, faStrikethrough,
    faCode, faHeading, faListUl, faListOl, faQuoteLeft,
    faFileCode, faLink, faImage, faUndo, faRedo, faTimes,
    faInfoCircle, faTrash, faFile,

    // brands
    faGithub,

    // regular
    faFileAltRegular
)

export { FontAwesomeIcon }
