<template>
  <el-config-provider :locale="locale" :size="size">
    <!-- 开启水印 -->
    <el-watermark
      v-if="watermarkEnabled"
      :font="{ color: fontColor }"
      :content="defaultSettings.watermarkContent"
      :z-index="9999"
      class="wh-full"
    >
      <router-view />
    </el-watermark>
    <!-- 关闭水印 -->
    <router-view v-else />
    <a
      class="icp-footer"
      href="https://beian.miit.gov.cn/"
      target="_blank"
      rel="noopener noreferrer"
    >
      备案号：鄂ICP备2025091005号-1
    </a>
  </el-config-provider>
  <kf v-if="showKfComponent" />
</template>

<script setup lang="ts">
import { useAppStore, useSettingsStore } from "@/store";
import defaultSettings from "@/settings";
import { ThemeEnum } from "@/enums/ThemeEnum";
import { SizeEnum } from "@/enums/SizeEnum";
import kf from "@/components/kf.vue";
import { useRoute } from "vue-router";
import { provide, ref, computed, onMounted, onBeforeUnmount } from "vue";

const appStore = useAppStore();
const settingsStore = useSettingsStore();

const locale = computed(() => appStore.locale);
const size = computed(() => appStore.size as SizeEnum);
const watermarkEnabled = computed(() => settingsStore.watermarkEnabled);

const route = useRoute();
const showKfComponent = ref(false);
const isDarkPage = computed(
  () =>
    route.path === "/login" ||
    route.path === "/adminLogin" ||
    route.path.startsWith("/emailReset")
);

const screenWidth = ref(window.innerWidth);
const isMobile = computed(() => screenWidth.value <= 768);

const handleResize = () => {
  screenWidth.value = window.innerWidth;
};

onMounted(() => {
  window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", handleResize);
});

// 提供数据给后代组件
provide("screenWidth", screenWidth);
provide("isMobile", isMobile);

watch(
  () => route.path,
  () => {
    showKfComponent.value =
      route.path != "/login" &&
      route.path != "/adminLogin" &&
      !route.path.startsWith("/admin") &&
      !route.path.startsWith("/emailReset");
  }
);

// 明亮/暗黑主题水印字体颜色适配
const fontColor = computed(() => {
  return settingsStore.theme === ThemeEnum.DARK
    ? "rgba(255, 255, 255, .15)"
    : "rgba(0, 0, 0, .15)";
});
</script>

<style lang="scss">
.icp-footer {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 20;
  height: 28px;
  font-size: 12px;
  line-height: 28px;
  color: var(--el-text-color-secondary);
  text-align: center;
  text-decoration: none;

  &:hover {
    text-decoration: underline;
  }
}

.app-main {
  box-sizing: border-box;
  padding-bottom: 28px;
}
</style>
