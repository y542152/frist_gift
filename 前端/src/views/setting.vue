<template>
  <div class="dashboard-container">
    <h3>设置</h3>

    <el-tabs v-model="activeName" class="demo-tabs">
      <el-tab-pane label="基本信息配置" name="0">
        <PageForm
          ref="modifyFormRef"
          :label-width="isMobile ? '140px' : '200px'"
          :data="modifyFormData1"
          :field="modifyDialog.currentDetail"
          :label-position="isMobile ? 'top' : 'right'"
        >
          <template #notice1>
            <div
              :style="
                isMobile
                  ? 'border: 1px solid #ccc; width: 100%'
                  : 'border: 1px solid #ccc; width: 50%'
              "
            >
              <Toolbar
                style="border-bottom: 1px solid #ccc"
                :editor="editorRef1"
                :defaultConfig="toolbarConfig"
                :mode="mode"
              />
              <Editor
                v-model="modifyDialog.currentDetail.NOTICE1"
                style="height: 200px; overflow-y: hidden"
                :defaultConfig="editorConfig"
                :mode="mode"
                @onCreated="handleCreated1"
              />
            </div>
          </template>
          <template #notice2>
            <div
              :style="
                isMobile
                  ? 'border: 1px solid #ccc; width: 100%'
                  : 'border: 1px solid #ccc; width: 50%'
              "
            >
              <Toolbar
                style="border-bottom: 1px solid #ccc"
                :editor="editorRef2"
                :defaultConfig="toolbarConfig"
                :mode="mode"
              />
              <Editor
                v-model="modifyDialog.currentDetail.NOTICE2"
                style="height: 200px; overflow-y: hidden"
                :defaultConfig="editorConfig"
                :mode="mode"
                @onCreated="handleCreated2"
              />
            </div>
          </template>
          <template #notice3>
            <div
              :style="
                isMobile
                  ? 'border: 1px solid #ccc; width: 100%'
                  : 'border: 1px solid #ccc; width: 50%'
              "
            >
              <Toolbar
                style="border-bottom: 1px solid #ccc"
                :editor="editorRef3"
                :defaultConfig="toolbarConfig"
                :mode="mode"
              />
              <Editor
                v-model="modifyDialog.currentDetail.NOTICE3"
                style="height: 200px; overflow-y: hidden"
                :defaultConfig="editorConfig"
                :mode="mode"
                @onCreated="handleCreated3"
              />
            </div>
          </template>
        </PageForm>
      </el-tab-pane>
      <el-tab-pane label="公告配置" name="1">
        <PageForm
          ref="modifyFormRef"
          :label-width="isMobile ? '100px' : '200px'"
          :data="modifyFormData2"
          :field="modifyDialog.currentDetail"
          :label-position="isMobile ? 'top' : 'right'"
        >
          <template #notice1>
            <div
              :style="
                isMobile
                  ? 'border: 1px solid #ccc; width: 100%'
                  : 'border: 1px solid #ccc; width: 50%'
              "
            >
              <Toolbar
                style="border-bottom: 1px solid #ccc"
                :editor="editorRef1"
                :defaultConfig="toolbarConfig"
                :mode="mode"
              />
              <Editor
                v-model="modifyDialog.currentDetail.NOTICE1"
                style="height: 200px; overflow-y: hidden"
                :defaultConfig="editorConfig"
                :mode="mode"
                @onCreated="handleCreated1"
              />
            </div>
          </template>
          <template #notice2>
            <div
              :style="
                isMobile
                  ? 'border: 1px solid #ccc; width: 100%'
                  : 'border: 1px solid #ccc; width: 50%'
              "
            >
              <Toolbar
                style="border-bottom: 1px solid #ccc"
                :editor="editorRef2"
                :defaultConfig="toolbarConfig"
                :mode="mode"
              />
              <Editor
                v-model="modifyDialog.currentDetail.NOTICE2"
                style="height: 200px; overflow-y: hidden"
                :defaultConfig="editorConfig"
                :mode="mode"
                @onCreated="handleCreated2"
              />
            </div>
          </template>
          <template #notice3>
            <div
              :style="
                isMobile
                  ? 'border: 1px solid #ccc; width: 100%'
                  : 'border: 1px solid #ccc; width: 50%'
              "
            >
              <Toolbar
                style="border-bottom: 1px solid #ccc"
                :editor="editorRef3"
                :defaultConfig="toolbarConfig"
                :mode="mode"
              />
              <Editor
                v-model="modifyDialog.currentDetail.NOTICE3"
                style="height: 200px; overflow-y: hidden"
                :defaultConfig="editorConfig"
                :mode="mode"
                @onCreated="handleCreated3"
              />
            </div>
          </template>
        </PageForm>
      </el-tab-pane>
    </el-tabs>

    <el-button type="primary" @click="submit">保存</el-button>
  </div>
</template>

<script setup lang="ts">
import UserAPI from "@/api/user";
import PageForm from "@/components/pageForm/index.vue";
import "@wangeditor/editor/dist/css/style.css"; // 引入 css
import { Editor, Toolbar } from "@wangeditor/editor-for-vue";
import { inject } from "vue";

const isMobile: any = inject("isMobile");

const mode = ref("default");

// 编辑器实例，必须用 shallowRef
const editorRef1 = shallowRef();
const editorRef2 = shallowRef();
const editorRef3 = shallowRef();
const activeName = ref("0");

const toolbarConfig: any = {
  toolbarKeys: [],
};
const editorConfig = { placeholder: "请输入内容..." };

toolbarConfig.toolbarKeys = [
  "bold",
  "underline",
  "italic",
  "through",
  "code",
  "sub",
  "sup",
  "clearStyle",
  "color",
  "bgColor",
  "fontSize",
  "fontFamily",
  "indent",
  "delIndent",
  "justifyLeft",
  "justifyRight",
  "justifyCenter",
  "justifyJustify",
  "lineHeight",
  "viewImageLink",
  "imageWidth30",
  "imageWidth50",
  "imageWidth100",
  "divider",
  "emotion",
  "insertLink",
  "editLink",
  "unLink",
  "viewLink",
  "codeBlock",
  "blockquote",
  "headerSelect",
  "header1",
  "header2",
  "header3",
  "header4",
  "header5",
  "todo",
  "redo",
  "undo",
  "fullScreen",
  "enter",
  "bulletedList",
  "numberedList",
  "insertTable",
  "deleteTable",
  "insertTableRow",
  "deleteTableRow",
  "insertTableCol",
  "deleteTableCol",
  "tableHeader",
  "tableFullWidth",
  "codeSelectLang",
];

const modifyDialog: any = reactive({
  currentDetail: {
    NOTICE1: "",
    NOTICE2: "",
    NOTICE3: "",
  },
});

const handleCreated1 = (editor: any) => {
  editorRef1.value = editor; // 记录 editor 实例，重要！
};
const handleCreated2 = (editor: any) => {
  editorRef2.value = editor; // 记录 editor 实例，重要！
};
const handleCreated3 = (editor: any) => {
  editorRef3.value = editor; // 记录 editor 实例，重要！
};

onBeforeUnmount(() => {
  if (editorRef1.value == null) return;
  editorRef1.value.destroy();

  if (editorRef2.value == null) return;
  editorRef2.value.destroy();

  if (editorRef3.value == null) return;
  editorRef3.value.destroy();
});

let uploadUrl = import.meta.env.VITE_APP_API_URL + "/api/upload";
if (import.meta.env.VITE_APP_API_URL.indexOf("/api") > -1) {
  uploadUrl = import.meta.env.VITE_APP_API_URL + "/upload";
}

const modifyFormData1 = ref([
  {
    type: "input-number",
    prop: "point2coinRate",
    label: "积分兑换金币百分比",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "uploadImg",
    prop: "WECHAT_PAY_QR",
    action: uploadUrl,
    headers: {
      token: localStorage.getItem("admin-token"),
    },
    limit: 1,
    multiple: false,
    label: "微信支付码",
    width: "80%",
    showUploadBtn: false,
  },
  {
    type: "input",
    prop: "ALIPAY_APPID",
    label: "支付宝支付APPID",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "input",
    prop: "ALIPAY_PUBLIC_KEY",
    label: "支付宝支付公钥",
    password: true,
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "input",
    prop: "ALIPAY_PRIVATE_KEY",
    label: "支付宝支付私钥",
    password: true,
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "input",
    prop: "GIFT_API_URL",
    label: "礼物API网站",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "input",
    prop: "GIFT_API_ACCOUNT",
    label: "礼物API账号",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "input",
    password: true,
    prop: "GIFT_API_PW",
    label: "礼物API密码",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "radio",
    prop: "WECHAT_PAY_AUTO_COMPLETE",
    label: "是否开启微信自动到账",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
    options: [
      {
        label: "是",
        value: 1,
      },
      {
        label: "否",
        value: 0,
      },
    ],
  },
  {
    type: "input",
    prop: "KF_QQ",
    label: "客服QQ号",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "uploadImg",
    prop: "KF_WECHAT_QR",
    action: uploadUrl,
    headers: {
      token: localStorage.getItem("admin-token"),
    },
    accept: "image/*",
    limit: 1,
    multiple: false,
    label: "客服微信二维码",
    width: "80%",
    showUploadBtn: false,
  },
]);

const modifyFormData2 = ref([
  {
    type: "opt",
    labelPosition: "top",
    slotName: "notice1",
    prop: "NOTICE1",
    label: "下单页公告",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "opt",
    slotName: "notice2",
    prop: "NOTICE2",
    label: "已发记录公告",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
  {
    type: "opt",
    slotName: "notice3",
    prop: "NOTICE3",
    label: "我的推广公告",
    width: computed(() => {
      return isMobile.value ? "100%" : "30%";
    }),
  },
]);

const fetchSetting = () => {
  UserAPI.getSettingInfo().then((res: any) => {
    modifyDialog.currentDetail = res;
    modifyDialog.currentDetail.NOTICE1 =
      modifyDialog.currentDetail.NOTICE1 || "";
    modifyDialog.currentDetail.NOTICE2 =
      modifyDialog.currentDetail.NOTICE2 || "";
    modifyDialog.currentDetail.NOTICE3 =
      modifyDialog.currentDetail.NOTICE3 || "";
    modifyDialog.currentDetail.WECHAT_PAY_QR = modifyDialog.currentDetail
      .WECHAT_PAY_QR
      ? [
          {
            url: modifyDialog.currentDetail.WECHAT_PAY_QR,
          },
        ]
      : [];

    modifyDialog.currentDetail.KF_WECHAT_QR = modifyDialog.currentDetail
      .KF_WECHAT_QR
      ? [
          {
            url: modifyDialog.currentDetail.KF_WECHAT_QR,
          },
        ]
      : [];
  });
};

const submit = () => {
  const params = { ...modifyDialog.currentDetail };
  if (params.WECHAT_PAY_QR) {
    params.WECHAT_PAY_QR = params.WECHAT_PAY_QR[0].url;
  }
  if (params.KF_WECHAT_QR) {
    params.KF_WECHAT_QR = params.KF_WECHAT_QR[0].url;
  }
  UserAPI.updateSetting(params).then(() => {
    ElMessage.success("操作成功");
    fetchSetting();
  });
};

onMounted(() => {
  fetchSetting();
});
</script>

<style lang="scss" scoped>
.dashboard-container {
  position: relative;
  padding: 24px;
  background-color: white;
}

.center {
  display: flex;
  flex-direction: row;
  align-items: center;
}

.options {
  width: 100%;
}
</style>
