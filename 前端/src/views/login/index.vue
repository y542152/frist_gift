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
            {{
              model == "register"
                ? "注册"
                : model == "reset"
                  ? "找回密码"
                  : $t("login.login")
            }}
          </h2>

          <template v-if="model == 'login'">
            <!-- 用户名 -->
            <el-form-item prop="username">
              <div class="input-wrapper">
                <el-icon class="mx-2">
                  <el-icon><Iphone /></el-icon>
                </el-icon>
                <el-input
                  ref="username"
                  v-model="loginData.username"
                  :placeholder="$t('login.username')"
                  name="username"
                  size="large"
                  class="h-[48px]"
                />
              </div>
            </el-form-item>

            <!-- 密码 -->
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
          </template>

          <template v-else-if="model == 'register'">
            <!-- 用户名 -->
            <el-form-item prop="username">
              <div class="input-wrapper">
                <el-icon class="mx-2">
                  <el-icon><Iphone /></el-icon>
                </el-icon>
                <el-input
                  ref="username"
                  v-model="loginData.username"
                  placeholder="请输入用户名"
                  name="username"
                  size="large"
                  class="h-[48px]"
                />
              </div>
            </el-form-item>

            <!-- 用户名 -->
            <el-form-item prop="QQ">
              <div class="input-wrapper">
                <el-icon class="mx-2">QQ</el-icon>
                <el-input
                  ref="qq"
                  v-model="loginData.qq"
                  placeholder="请输入QQ号码"
                  name="qq"
                  size="large"
                  class="h-[48px]"
                />
              </div>
            </el-form-item>

            <el-form-item prop="QQ邮箱">
              <div class="input-wrapper">
                <el-icon class="mx-2">
                  <el-icon><Message /></el-icon>
                </el-icon>
                <el-input
                  ref="qqemail"
                  :value="loginData.qq ? loginData.qq + '@qq.com' : ''"
                  placeholder="QQ邮箱"
                  :disabled="true"
                  name="qqemail"
                  size="large"
                  class="h-[48px]"
                />
              </div>
            </el-form-item>

            <!-- 密码 -->
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
                    placeholder="请输入密码"
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
              <el-form-item prop="password">
                <div class="input-wrapper">
                  <el-icon class="mx-2">
                    <Lock />
                  </el-icon>
                  <el-input
                    v-model="loginData.password1"
                    placeholder="确认密码"
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
          </template>

          <template v-else-if="model == 'reset'">
            <el-form-item v-if="resetStep == 1" prop="account">
              <div class="input-wrapper">
                <el-icon class="mx-2">
                  <el-icon><User /></el-icon>
                </el-icon>
                <el-input
                  v-model="resetData.account"
                  placeholder="请输入账号"
                  size="large"
                  class="h-[48px]"
                />
              </div>
            </el-form-item>
            <el-form-item v-if="resetStep == 2" prop="email">
              <div class="input-wrapper">
                <el-icon class="mx-2">
                  <el-icon><Message /></el-icon>
                </el-icon>
                <el-input
                  v-model="resetData.resetEmail"
                  placeholder="请输入QQ邮箱"
                  size="large"
                  class="h-[48px]"
                />
              </div>
            </el-form-item>
          </template>

          <!-- 登录按钮 -->
          <el-button
            v-if="model == 'login' || model == 'register'"
            :loading="loading"
            type="primary"
            size="large"
            class="w-full"
            @click.prevent="handleLoginSubmit"
          >
            {{ model == "register" ? "注册" : $t("login.login") }}
          </el-button>

          <template v-if="model == 'reset'">
            <el-button
              v-if="resetStep == 1"
              :loading="loading"
              type="primary"
              size="large"
              class="w-full"
              @click.prevent="resetNext"
            >
              找回密码
            </el-button>

            <el-button
              v-if="resetStep == 2"
              :loading="loading"
              type="primary"
              size="large"
              class="w-full"
              @click.prevent="sendEmail"
            >
              发送邮件
            </el-button>
          </template>

          <el-col
            :span="24"
            class="flex-x-between w-full py-1"
            style="text-align: right"
          >
            <el-link
              v-if="model != 'login'"
              type="primary"
              @click="changeModel('login')"
            >
              已有账号
            </el-link>

            <el-link
              v-if="model != 'register'"
              type="primary"
              @click="changeModel('register')"
            >
              免费注册
            </el-link>

            <el-link
              v-if="model != 'reset'"
              type="primary"
              @click="changeModel('reset')"
            >
              忘记密码？
            </el-link>
          </el-col>
        </el-form>
      </div>
    </div>

    <!-- ICP备案 -->
    <!-- <div class="absolute bottom-0 w-full text-center text-12px">
      <p>
        Copyright © 2021 - 2024 youlai.tech All Rights Reserved.
        <a
          href="http://beian.miit.gov.cn/"
          target="_blank"
          rel="noopener noreferrer"
          class="hover:underline"
        >
          皖ICP备20006496号-2
        </a>
      </p>
    </div> -->
  </div>
</template>

<script setup lang="ts">
// 外部库和依赖
import { LocationQuery, useRoute } from "vue-router";

// 内部依赖
import { useUserStore } from "@/store";
import { type LoginData } from "@/api/auth";
import router from "@/router";

// 类型定义
import type { FormInstance } from "element-plus";

// 导入 login.scss 文件
import "@/styles/login.scss";
import UserAPI from "@/api/user";

// 使用导入的依赖和库
const userStore = useUserStore();

const route = useRoute();
// 窗口高度
const { height } = useWindowSize();
// 国际化 Internationalization
const { t } = useI18n();

// 是否显示 ICP 备案信息
const icpVisible = ref(true);
// 按钮 loading 状态
const loading = ref(false);
// 是否大写锁定
const isCapslock = ref(false);
// 登录表单ref
const loginFormRef = ref<FormInstance>();

const resetStep = ref(1);
const resetData = reactive({
  resetEmail: "",
  account: "",
});
const resetNext = () => {
  loading.value = true;
  UserAPI.resetCheck({
    account: resetData.account,
  })
    .then(() => {
      resetStep.value = 2;
    })
    .finally(() => {
      loading.value = false;
    });
};

const model = ref("login"); // login | register

const loginData = ref<LoginData>({
  username: "",
  password: "",
  password1: "",
});

const loginRules = computed(() => {
  return {
    username: [
      {
        required: true,
        trigger: "blur",
        message: t("login.message.username.required"),
      },
    ],
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
    captchaCode: [
      {
        required: true,
        trigger: "blur",
        message: t("login.message.captchaCode.required"),
      },
    ],
  };
});

/** 登录表单提交 */
function handleLoginSubmit() {
  loginFormRef.value?.validate((valid: boolean) => {
    if (valid) {
      loading.value = true;
      if (model.value === "login") {
        userStore
          .login(loginData.value)
          .then((res: any) => {
            const { path, queryParams } = parseRedirect();
            router.push({ path: path, query: queryParams });
          })
          .finally(() => {
            loading.value = false;
          });
      } else {
        const params: any = { ...loginData.value };
        if (inviteUid.value > 0) {
          params.inviteUid = inviteUid.value;
        }
        console.log("params", params);
        userStore
          .register(params)
          .then(() => {
            ElMessage.success("注册成功，为您切换到登录页面");
            model.value = "login";
          })
          .finally(() => {
            loading.value = false;
          });
      }
    }
  });
}

/** 解析 redirect 字符串 为 path 和  queryParams */
function parseRedirect(): {
  path: string;
  queryParams: Record<string, string>;
} {
  const query: LocationQuery = route.query;
  const redirect = (query.redirect as string) ?? "/";

  const url = new URL(redirect, window.location.origin);
  const path = url.pathname;
  const queryParams: Record<string, string> = {};

  url.searchParams.forEach((value, key) => {
    queryParams[key] = value;
  });

  return { path, queryParams };
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

const inviteUid: any = ref(0);
const changeModel = (newM: any) => {
  model.value = newM;
};

const sendEmail = () => {
  if (resetData.resetEmail && resetData.account) {
    loading.value = true;
    UserAPI.reset1({
      email: resetData.resetEmail,
      account: resetData.account,
    })
      .then(() => {
        ElMessage.success("邮件发送成功，请查收");
      })
      .finally(() => {
        loading.value = false;
      });
  } else {
    ElMessage.error("请输入账号与邮箱");
  }
};

watch(
  () => model.value,
  () => {
    if (model.value === "reset") {
      resetStep.value = 1;
    }
  }
);

onMounted(() => {
  const { query }: any = useRoute();

  if (query != null && query.inviteUid > 0) {
    inviteUid.value = query?.inviteUid;
    model.value = "register";
  }
});
</script>

<style scoped>
.py-1 a {
  padding-left: 10px;
}
</style>
