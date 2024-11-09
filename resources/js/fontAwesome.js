import { library } from '@fortawesome/fontawesome-svg-core';
import { faSearch, faUser, faBell, faUserGroup, faBarsStaggered, faXmark, faLinkSlash,faDownload } from '@fortawesome/free-solid-svg-icons';
import { faGithub } from '@fortawesome/free-brands-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';


// 将图标添加到库中
library.add(faSearch, faUser, faBell, faUserGroup, faBarsStaggered, faXmark, faGithub, faLinkSlash,faDownload);

// 导出 FontAwesomeIcon 组件
export { FontAwesomeIcon };
