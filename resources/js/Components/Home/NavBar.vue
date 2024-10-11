<template>
  <!-- 设置背景和内边距 -->
  <nav class="bg-camp-blue p-4">
    <!-- Flexbox 容器，子元素均匀分布并垂直居中，宽度为100% -->
    <div class="flex justify-between items-center w-full">

      <!-- 左侧导航 -->
      <!-- Flexbox 容器，子元素垂直居中，水平间距为4个单位 -->
      <div class="flex items-center space-x-4">
        <!-- 动态生成导航链接，使用 link.name 作为唯一键，链接地址为 link.url，样式为 linkClass -->
        <a v-for="link in navLinks" :key="link.name" :href="link.url" :class="linkClass">{{ link.name }}</a>
      </div>

      <!-- 右侧导航 -->
      <!-- Flexbox 容器，子元素垂直居中，水平间距为4个单位 -->
      <div class="flex items-center space-x-4">
        <!-- 语言切换，文本样式为 linkClass -->
        <a href="#" :class="linkClass">中/英</a>
        <!-- 点击按钮时，将 showDownloadModal 的值设置为 true，显示窗口 -->
        <button @click="showDownloadModal = true" :class="linkClass">游戏下载</button>
        <!-- 点击按钮时，将 showCommunityModal 的值设置为 true，显示窗口 -->
        <button @click="showCommunityModal = true" :class="linkClass">社群</button>
        <!-- 检查用户是否登陆 -->
        <template v-if="$page.props.auth.user">
          <!-- 渲染一个指向用户仪表盘的链接，文本样式为 linkClass -->
          <Link href="/dashboard" :class="linkClass">{{ $page.props.auth.user.name }}</Link>
        </template>
        <template v-else>
          <!-- 渲染一个指向登陆的链接 -->
          <Link href="/login" :class="linkClass">登录</Link>
          <!-- 通过 canRegister 动态控制注册功能 -->
          <Link v-if="canRegister" href="/register" :class="linkClass">注册</Link>
        </template>
      </div>
    </div>

    <!-- 游戏下载弹出窗口 -->
    <!-- 如果 showDownloadModal 为 true，显示 PopUp 组件 -->
    <PopUp v-if="showDownloadModal" @close="showDownloadModal = false" title="游戏下载">
      <ul>
        <!-- 半成品 -->
        <li><a href="#" class="text-blue-500 hover:underline">推荐的版本！</a></li>
        <li><a href="#" class="text-blue-500 hover:underline">全部版本</a></li>
      </ul>
    </PopUp>

    <!-- 社群弹出窗口 -->
    <!-- 如果 showCommunityModal 为 true，显示 PopUp 组件 -->
    <PopUp v-if="showCommunityModal" @close="showCommunityModal = false" title="社群">
      <ul>
        <li>
          <!-- Flexbox 容器，子元素垂直居中，设置最小高度 -->
          <div class="flex items-center min-h-10">
            <p class="text-zinc-950 mr-2 font-bold min-w-60">官方群：736250242</p>
            <!-- qq加群组件 -->
            <a target="_blank"
              href="https://qm.qq.com/cgi-bin/qm/qr?k=2LWqL9kEm4RCkBfmR7amYbXiFFqsXT_1&jump_from=webapi&authKey=cW8TXI2GV09Y6ThMrVIKTlNc3M/vqnR8dJe0jKsa2wPITtrVMkqCVO4UdQTXcFjT">
              <img border="0" src="//pub.idqqimg.com/wpa/images/group.png" alt="Solarmax3官方群" title="Solarmax3官方群">
            </a>
          </div>
        </li>
        <li>
          <div class="flex items-center min-h-10">
            <p class="text-zinc-950 mr-2 font-bold min-w-60">官方二群：1050145891</p>
            <a target="_blank"
              href="https://qm.qq.com/cgi-bin/qm/qr?k=c03YJdaeRp_vt5kLMHE3blBU0AFNC229&jump_from=webapi&authKey=eZFY2Bj9dDmFzY3g4EcrWLYJImwzkYshNX2i4oJO4ykRHrWmbnER/tjbxeidy7Sy">
              <img border="0" src="//pub.idqqimg.com/wpa/images/group.png" alt="Solarmax3官方群2群" title="Solarmax3官方群2群">
            </a>
          </div>
        </li>
        <li>
          <div class="flex items-center min-h-10">
            <p class="text-zinc-950 mr-2 font-bold min-w-60">官方三群：100027488</p>
            <a target="_blank"
              href="https://qm.qq.com/cgi-bin/qm/qr?k=-JwoTrXvEfq5-Y9m4QBjK8oA5nFmZuaw&jump_from=webapi&authKey=/icy1FKgNZ7PcQbyqW9JlsgGzcRcG/T5/nt7sQgELRWFKyd5guZojsAvv+XrPcsn">
              <img border="0" src="//pub.idqqimg.com/wpa/images/group.png" alt="Solarmax3官方群3群" title="Solarmax3官方群3群">
            </a>
          </div>
        </li>
        <li>
          <div class="flex items-center min-h-10">
            <p class="text-zinc-950 mr-2 font-bold min-w-60">自制总群：145820245</p>
            <a target="_blank"
              href="https://qm.qq.com/cgi-bin/qm/qr?k=pq1UZ2v341izbq7YNnfoi-Bln56ijiE9&jump_from=webapi&authKey=okwwpDvumG0ra+tApNZpwleNAZ3wIFC4r+SConqxjCCZVEdQ/JO7VOzRokHV7YwM">
              <img border="0" src="//pub.idqqimg.com/wpa/images/group.png" alt="Solarmax自制总群" title="Solarmax自制总群">
            </a>
          </div>
        </li>
      </ul>
    </PopUp>
  </nav>
</template>

<script setup>
import { ref } from 'vue';  // 导入 ref，用于创建响应式变量
import { Link } from '@inertiajs/inertia-vue3'; // 导入 Inertia.js 的 Link 组件，用于无刷新的页面导航
import PopUp from '@/Components/Home/PopUp.vue';  // 导入 PopUp 组件，用于显示弹出窗口

const navLinks = [
  { name: 'Solarmax3Wiki Unofficial', url: '#' },// 第一个链接，指向首页
  { name: '游戏维基', url: '#' }, // 第二个链接，指向游戏维基页面
  { name: '游戏版本', url: '#' }, // 第三个链接，指向游戏版本页面
  { name: '自制制作', url: '#' }, // 第四个链接，指向自制制作页面
  { name: '攻略专区', url: '#' }, // 第五个链接，指向攻略专区
  { name: '论坛', url: '#' }  // 第六个链接，指向论坛页面
];

// 定义链接样式类
const linkClass = 'text-camp-black hover:text-white'; // 使用 Tailwind CSS 样式，设置默认颜色和悬停时的颜色

// 定义响应式变量，用于控制弹出窗口的显示
const showDownloadModal = ref(false); // 用于控制游戏下载弹出窗口的状态
const showCommunityModal = ref(false);  // 用于控制社群弹出窗口的状态

// 定义组件接收的 props
const props = defineProps({
  canRegister: {  // 是否可以注册
    type: Boolean,  // 类型为布尔值
    default: false, // 默认值
  },
});
</script>

<style scoped>
/* 可选：自定义样式 */
</style>
