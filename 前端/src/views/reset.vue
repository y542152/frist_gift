<template>
  <div class="login-container">
    <!-- 登录表单 -->
    <div class="login-content">
      <div class="login-box">
        <el-form
          ref="loginFormRef"
          :model="loginData"
          :rules="loginRules"
          class="login-form"
        >
          <h2 class="text-xl font-medium text-center flex-center relative">
            重置密码
          </h2>

          <el-tooltip
            :visible="isCapslock"
            :content="$t('login.capsLock')"
            placement="right"
          >
            <el-form-item prop="password">
              <div class="input-wrapper">
                <el-icon class="mx-2">
                  <Lock />
                </el-icon>
                <el-input
                  v-model="loginData.password"
                  :placeholder="$t('login.password')"
                  type="password"
                  name="password"
                  size="large"
                  class="h-[48px] pr-2"
                  show-password
                  @keyup="checkCapslock"
                  @keyup.enter="handleLoginSubmit"
                />
              </div>
            </el-form-item>
          </el-tooltip>

          <el-tooltip
            :visible="isCapslock"
            :content="$t('login.capsLock')"
            placement="right"
          >
            <el-form-item prop="password1">
              <div class="input-wrapper">
                <el-icon class="mx-2">
                  <Lock />
                </el-icon>
                <el-input
                  v-model="loginData.password1"
                  placeholder="请确认密码"
                  type="password"
                  name="password1"
                  size="large"
                  class="h-[48px] pr-2"
                  show-password
                  @keyup="checkCapslock"
                  @keyup.enter="handleLoginSubmit"
                />
              </div>
            </el-form-item>
          </el-tooltip>

          <!-- 登录按钮 -->
          <el-button
            :loading="loading"
            type="primary"
            size="large"
            class="w-full"
            @click.prevent="handleLoginSubmit"
          >
            重置密码
          </el-button>
        </el-form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// 外部库和依赖
import { LocationQuery, useRoute } from "vue-router";

// 内部依赖
import { useSettingsStore, useUserStore } from "@/store";
import UserApi from "@/api/user";
import router from "@/router";
import defaultSettings from "@/settings";
import { ThemeEnum } from "@/enums/ThemeEnum";

// 类型定义
import type { FormInstance } from "element-plus";

// 导入 login.scss 文件
import "@/styles/login.scss";

// 使用导入的依赖和库
const settingsStore = useSettingsStore();
// 窗口高度
const { height } = useWindowSize();
// 国际化 Internationalization
const { t } = useI18n();

// 是否暗黑模式
const isDark = ref(settingsStore.theme === ThemeEnum.DARK);
// 是否显示 ICP 备案信息
const icpVisible = ref(true);
// 按钮 loading 状态
const loading = ref(false);
// 是否大写锁定
const isCapslock = ref(false);
// 验证码图片Base64字符串
const captchaBase64 = ref();
// 登录表单ref
const loginFormRef = ref<FormInstance>();
const loginData = reactive({
  password: "",
  password1: "",
  resetStr: "",
});

const loginRules = computed(() => {
  return {
    password: [
      {
        required: true,
        trigger: "blur",
        message: t("login.message.password.required"),
      },
      {
        min: 6,
        message: t("login.message.password.min"),
        trigger: "blur",
      },
    ],
    password1: [
      {
        required: true,
        trigger: "blur",
        message: "请输入确认密码",
      },
      {
        min: 6,
        message: t("login.message.password.min"),
        trigger: "blur",
      },
    ],
  };
});

/** 登录表单提交 */
function handleLoginSubmit() {
  loginFormRef.value?.validate((valid: boolean) => {
    if (valid) {
      loading.value = true;
      UserApi.reset2({ ...loginData })
        .then((res: any) => {
          ElMessage.success("密码重置成功");
          setTimeout(() => {
            router.push({ path: "/login" });
          }, 1000);
        })
        .finally(() => {
          loading.value = false;
        });
    }
  });
}

/** 根据屏幕宽度切换设备模式 */
watchEffect(() => {
  if (height.value < 600) {
    icpVisible.value = false;
  } else {
    icpVisible.value = true;
  }
});

/** 检查输入大小写 */
function checkCapslock(event: KeyboardEvent) {
  // 防止浏览器密码自动填充时报错
  if (event instanceof KeyboardEvent) {
    isCapslock.value = event.getModifierState("CapsLock");
  }
}

onMounted(() => {
  const route = useRoute();
  if (route.params.resetStr) {
    loginData.resetStr = route.params.resetStr as string;
  } else {
    ElMessage.error("重置密码链接无效");
  }
});
</script>
