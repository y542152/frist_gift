import "core-js/stable"; // polyfill for ECMAScript
import "regenerator-runtime/runtime"; // polyfill for async/await

import { createApp } from "vue";
import App from "./App.vue";
import setupPlugins from "@/plugins";

// 本地SVG图标
import "virtual:svg-icons-register";

// 样式
import "element-plus/theme-chalk/dark/css-vars.css";
import "@/styles/index.scss";
import "uno.css";
import "animate.css";

// 1. 引入你需要的组件
import { DatePicker } from "vant";
import { ActionSheet } from "vant";
import { Calendar } from "vant";
import { Cell } from "vant";
// 2. 引入组件样式
import "vant/lib/index.css";

const app = createApp(App);
// 注册插件
app.use(setupPlugins);
app.use(DatePicker);
app.use(ActionSheet);
app.use(Calendar);
app.use(Cell);
app.mount("#app");
