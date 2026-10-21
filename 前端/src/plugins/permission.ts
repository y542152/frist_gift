import type { NavigationGuardNext, RouteLocationNormalized } from "vue-router";
import NProgress from "@/utils/nprogress";
import { isAdmin, isLogin } from "@/utils/auth";

import router from "@/router";
import { useUserStore } from "@/store";

export function setupPermission() {
  // 白名单路由
  const whiteList = ["/login", "/adminLogin", "/emailReset"];

  router.beforeEach(async (to, from, next) => {
    console.log(from.path, to.path);
    NProgress.start();
    if (isLogin()) {
      // 管理页删除dashboard
      if (to.path === "/admin/dashboard") {
        next({ path: "/admin/gift/sendLogs" });
        NProgress.done();
      } else if (to.path === "/login" || to.path === "/adminLogin") {
        // 如果已登录，跳转到首页
        next({ path: to.path == "/adminLogin" ? "/admin/gift/sendLogs" : "/" });
        NProgress.done();
      } else {
        const userStore = useUserStore();
        await userStore.getUserInfo();
        next();
        NProgress.done();
      }
    } else {
      // 未登录
      if (whiteList.includes(to.path) || to.path.startsWith("/emailReset")) {
        next(); // 在白名单，直接进入
      } else {
        // 不在白名单，重定向到登录页
        redirectToLogin(to, next);
        NProgress.done();
      }
    }
  });

  router.afterEach(() => {
    NProgress.done();
  });
}

/** 重定向到登录页 */
function redirectToLogin(
  to: RouteLocationNormalized,
  next: NavigationGuardNext
) {
  const params = new URLSearchParams(to.query as Record<string, string>);
  const queryString = params.toString();
  const redirect = queryString ? `${to.path}?${queryString}` : to.path;
  const isA = isAdmin();
  next(
    isA
      ? `/adminLogin?redirect=${encodeURIComponent(redirect)}`
      : `/login?redirect=${encodeURIComponent(redirect)}`
  );
}

/** 判断是否有权限 */
export function hasAuth(
  value: string | string[],
  type: "button" | "role" = "button"
) {
  const { roles, perms } = useUserStore().user;

  // 超级管理员 拥有所有权限
  if (type === "button" && roles.includes("ROOT")) {
    return true;
  }

  const auths = type === "button" ? perms : roles;
  return typeof value === "string"
    ? auths.includes(value)
    : value.some((perm) => auths.includes(perm));
}
