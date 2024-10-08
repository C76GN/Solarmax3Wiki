<template>
  <nav class="bg-sky-900 p-4">
    <div class="flex justify-between items-center w-full">
      <!-- 左侧导航 -->
      <div class="flex items-center space-x-4">
        <a href="#" class="text-white font-bold text-lg">Solarmax3Wiki Unofficial</a>
        <a href="#" :class=linkClass>游戏版本</a>
        <a href="#" :class=linkClass>游戏内容</a>
        <a href="#" :class=linkClass>自制制作</a>
        <a href="#" :class=linkClass>攻略专区</a>
        <a href="#" :class=linkClass>论坛</a>
      </div>

      <!-- 右侧导航：登录和注册 -->
      <div class="flex items-center space-x-4">
        <!-- 判断用户是否已经登录 -->
        <template v-if="$page.props.auth.user">
          <Link href="/dashboard" :class=linkClass>Dashboard</Link>
        </template>
        <template v-else>
          <Link href="/login" :class=linkClass>登录</Link>
          <Link v-if="canRegister" href="/register" :class=linkClass>注册</Link>
        </template>
        <a href="#" :class=linkClass>中/英</a>
        <button @click="showDownloadModal = true" :class=linkClass>游戏下载</button>
        <button @click="showCommunityModal = true" :class=linkClass>社群</button>
      </div>
    </div>

    <!-- 游戏下载弹出窗口 -->
    <PopUp v-if="showDownloadModal" @close="showDownloadModal = false" title="游戏下载">
      <ul>
        <li><a href="#" class="text-blue-500 hover:underline">推荐的版本！</a></li>
        <li><a href="#" class="text-blue-500 hover:underline">全部版本</a></li>
      </ul>
    </PopUp>

    <!-- 社群弹出窗口 -->
    <PopUp v-if="showCommunityModal" @close="showCommunityModal = false" title="社群">
      <ul>
        <li>
          <div class="flex items-center min-h-10">
            <p class="text-zinc-950 mr-2 font-bold min-w-60">官方群：736250242</p>
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
import { ref } from 'vue';
import { Link } from '@inertiajs/inertia-vue3';  // 引入 Inertia.js 的 Link 组件
import PopUp from '@/Components/Home/PopUp.vue';  // 引入弹窗组件

const linkClass = 'text-gray-300 hover:text-white';
const showDownloadModal = ref(false);
const showCommunityModal = ref(false);

// 使用 defineProps 获取 props
const props = defineProps({
  canRegister: {
    type: Boolean,
    default: true,  // 默认显示注册按钮
  },
});

// 设置组件名称
defineOptions({
  name: 'NavBar',
});
</script>

<style scoped>
/* 可选：自定义样式 */
</style>
