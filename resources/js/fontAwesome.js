import { library } from '@fortawesome/fontawesome-svg-core';
import { faSearch, faUser, faBell } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

// 将图标添加到库中
library.add(faSearch, faUser, faBell);

// 导出 FontAwesomeIcon 组件
export { FontAwesomeIcon };
