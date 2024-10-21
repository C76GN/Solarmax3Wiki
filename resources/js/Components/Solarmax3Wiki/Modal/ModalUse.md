# Modal.vue 组件使用说明

### 组件简介：

`<Modal>` 是一个基于 `Vue 3` 的弹窗组件，支持动态宽度、响应式设计和自定义位置。弹窗能够通过点击背景或按下键盘的 `Esc` 键关闭。该组件还支持动画过渡效果。

---

### Props 说明：

1. **`show`** (Boolean) - 控制弹窗是否显示。`true` 为显示，`false` 为隐藏。默认值为 `false`。
2. **`maxWidth`** (String) - 控制弹窗的最大宽度。可选值包括：
    - `sm` (小)
    - `md` (中)
    - `lg` (大)
    - `xl` (超大)
    - `2xl` (默认值，极大)
3. **`closeable`** (Boolean) - 是否允许通过点击背景或按下 `Esc` 键关闭弹窗。默认值为 `true`。
4. **`position`** (String) - 设置弹窗在页面中的预设位置。可选值包括：
    - `center` (居中，默认)
    - `top-center`
    - `bottom-center`
    - `top-left`
    - `top-right`
    - `bottom-left`
    - `bottom-right`
5. **`customPosition`** (Object) - 自定义弹窗的位置，使用绝对定位，格式为 `{ top: '10px', left: '20px' }`。默认值为 `null`。

---

### Emits 说明：

1. **close** - 当用户点击背景或者按下 `Esc` 键关闭弹窗时触发。

---

### 在父组件中如何使用 `<Modal>` 组件？

### 1. 引入并使用组件：

在父组件中，确保 `Modal` 组件被正确引入。然后可以通过绑定 `show` prop 控制弹窗的显示与隐藏，并通过监听 `close` 事件来处理关闭逻辑。

### 2. 示例代码：

```

<template>
  <div>
    <!-- 打开弹窗按钮 -->
    <button @click="showModal = true">打开弹窗</button>

    <!-- 弹窗组件 -->
    <Modal
      :show="showModal" <!-- 控制弹窗是否显示 -->
      @close="showModal = false" <!-- 监听close事件，将showModal 的值改为 false，关闭弹窗 -->
      maxWidth="lg" <!-- 最大宽度 -->
      position="center" <!-- 弹窗位置 -->
      :closeable="true" <!-- 是否允许通过背景或按键关闭 -->
    >
      <!-- 弹窗内容插槽 -->
      <CommunityContent />
    </Modal>
  </div>
</template>

<script setup>
// 导入ref
import { ref } from 'vue';
// 导入Modal组件
import Modal from '@/Components/Solarmax3Wiki/Modal/Modal.vue';
// 导入插槽内容，也就是你希望弹窗内显示的内容
import CommunityContent from '@/Components/Solarmax3Wiki/Modal/ModalContent/CommunityContent.vue'; // 示例的弹窗内容
// 控制弹窗是否显示
const showModal = ref(false);
</script>

```

### 3. 在父组件中如何控制弹窗？

- **显示弹窗**：通过 `showModal = true` 来打开弹窗。
- **关闭弹窗**：可以通过点击背景、按 `Esc` 键、或者点击按钮来关闭弹窗。关闭时，`close` 事件会被触发，自动将 `showModal` 的值改为 `false`，关闭弹窗。

### 4. 弹窗参数的自定义：

- **宽度设置**：可以通过 `maxWidth` prop 设置弹窗宽度，例如 `maxWidth="lg"`。
- **位置设置**：通过 `position` prop 预设弹窗显示位置，例如 `position="top-center"`。
- **自定义位置**：如果需要精确控制弹窗的位置，可以传入自定义位置对象：

```

<Modal
  :show="showModal"
  @close="showModal = false"
  maxWidth="lg"
  :customPosition="{ top: '100px', left: '50px' }"
  :closeable="true"
/>

```

### 5. 插槽的使用：

- `Modal` 组件使用 `slot` 来动态传递内容，因此你可以通过传递任意内容组件（例如 `CommunityContent`）作为弹窗的主体。

### 6. 样式响应：

- 弹窗会根据设备屏幕大小自动缩放，例如在小屏幕设备上缩放到 80%。
- 组件会根据窗口大小自适应宽度，确保弹窗内容在各种设备上显示良好。

### 7. 事件说明：

- 点击背景、按下 `Esc` 键，或者显式调用 `close()` 方法，都会触发 `close` 事件。通过监听这个事件，父组件可以实现关闭弹窗的逻辑。

---