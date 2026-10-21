import type { App } from "vue";
import {
  createRouter,
  createWebHashHistory,
  type RouteRecordRaw,
} from "vue-router";
import { usePermissionStore } from "@/store";
import { isAdmin } from "@/utils/auth";

export const Layout = () => import("@/layout/index.vue");

const isA = isAdmin();
// 静态路由
export const constantRoutes: RouteRecordRaw[] = [
  {
    path: "/redirect",
    component: Layout,
    meta: { hidden: true },
    children: [
      {
        path: "/redirect/:path(.*)",
        component: () => import("@/views/redirect/index.vue"),
      },
    ],
  },
  {
    path: "/login",
    component: () => import("@/views/login/index.vue"),
    meta: { hidden: true },
  },
  {
    path: "/emailReset/:resetStr",
    component: () => import("@/views/reset.vue"),
    meta: { hidden: true },
  },
  {
    path: "/adminLogin",
    component: () => import("@/views/login/admin.vue"),
    meta: { hidden: true },
  },
  {
    path: isA ? "/admin" : "/",
    name: "/",
    component: Layout,
    redirect: isA ? "/admin/dashboard" : "/dashboard",
    meta: { title: "用户中心", hidden: isA },
    children: [
      {
        path: "dashboard",
        component: () => import("@/views/dashboard/index.vue"),
        // 用于 keep-alive 功能，需要与 SFC 中自动推导或显式声明的组件名称一致
        // 参考文档: https://cn.vuejs.org/guide/built-ins/keep-alive.html#include-exclude
        name: "Dashboard",
        meta: {
          title: "dashboard",
          affix: true,
          keepAlive: true,
        },
      },
      {
        path: "401",
        component: () => import("@/views/error/401.vue"),
        meta: { hidden: true },
      },
      {
        path: "404",
        component: () => import("@/views/error/404.vue"),
        meta: { hidden: true },
      },
    ],
  },
  {
    path: isA ? "/admin/gift" : "/gift",
    name: "/address",
    component: Layout,
    meta: { title: "发货地址", hidden: isA },
    children: [
      {
        path: "address",
        name: "发货地址",
        component: () => import("@/views/gift/address.vue"),
        meta: { title: "发货地址", icon: "el-icon-Place" },
      },
    ],
  },
  {
    path: isA ? "/admin/gift" : "/gift",
    name: "/gift",
    component: Layout,
    meta: { title: "购买管理", icon: "el-icon-ShoppingBag" },
    children: [
      {
        path: "sendXingfeng",
        name: "发信封",
        component: () => import("@/views/gift/sendXingfeng.vue"),
        meta: { title: "发信封", icon: "el-icon-Sell", hidden: isA },
      },
      {
        path: "send",
        name: "发礼品",
        component: () => import("@/views/gift/send.vue"),
        meta: { title: "发礼品", icon: "el-icon-Goods", hidden: isA },
      },
      {
        path: "sendLogs",
        name: "已发记录",
        component: () => import("@/views/gift/sendLogs.vue"),
        meta: { title: "已发记录", icon: "el-icon-Files" },
      },
    ],
  },
  // {
  //   path: isA ? "/admin/gift" : "/gift",
  //   name: "/gift",
  //   component: Layout,
  //   meta: { title: "已发记录", icon: "el-icon-Files", hidden: !isA },
  //   children: [
  //     {
  //       path: "sendLogs",
  //       name: "已发记录",
  //       component: () => import("@/views/gift/sendLogs.vue"),
  //       meta: { title: "已发记录", icon: "el-icon-Files" },
  //     },
  //   ],
  // },
  {
    path: isA ? "/admin/promotion" : "/promotion",
    name: "/promotion",
    component: Layout,
    meta: { title: "我的推广", hidden: isA },
    children: [
      {
        path: "mine",
        name: "我的推广",
        component: () => import("@/views/user/promotion.vue"),
        meta: { title: "我的推广", icon: "el-icon-Promotion" },
      },
    ],
  },
  {
    path: isA ? "/admin/users" : "/users",
    name: "/users",
    component: Layout,
    meta: { title: "用户管理", hidden: !isA, icon: "el-icon-User" },
    children: [
      {
        path: "users",
        name: "用户列表",
        component: () => import("@/views/user/list.vue"),
        meta: { title: "用户列表", icon: "el-icon-User" },
      },
      {
        path: "loginLogs",
        name: "用户登录记录",
        component: () => import("@/views/user/loginLogs.vue"),
        meta: { title: "用户登录记录", icon: "el-icon-Files" },
      },
      {
        path: "chargeOrders",
        name: "用户充值订单",
        component: () => import("@/views/user/chargeOrders.vue"),
        meta: { title: "用户充值订单", icon: "el-icon-Files" },
      },
      {
        path: "coinLogs",
        name: "用户金额流水",
        component: () => import("@/views/user/coinLogs.vue"),
        meta: { title: "用户金额流水", icon: "el-icon-Files" },
      },
    ],
  },
  {
    path: isA ? "/admin/setting" : "/setting",
    name: "/setting",
    component: Layout,
    meta: { title: "设置", hidden: !isA },
    children: [
      {
        path: "setting",
        name: "设置",
        component: () => import("@/views/setting.vue"),
        meta: { title: "设置", icon: "el-icon-Setting" },
      },
    ],
  },
];

/**
 * 创建路由
 */
const router = createRouter({
  history: createWebHashHistory(),
  routes: constantRoutes,
  // 刷新时，滚动条位置还原
  scrollBehavior: () => ({ left: 0, top: 0 }),
});

// 全局注册 router
export function setupRouter(app: App<Element>) {
  app.use(router);
  const permissionStore = usePermissionStore();
  permissionStore.routes = constantRoutes;
}

/**
 * 重置路由
 */
export function resetRouter() {
  router.replace({ path: isA ? "/adminLogin" : "/login" });
}

export default router;
