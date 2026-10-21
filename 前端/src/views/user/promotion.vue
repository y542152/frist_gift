<template>
  <el-col>
    <el-card shadow="never" class="table-container">
      <pageTable
        :tableLabel="tableLabels"
        :tableData="listData"
        :pagination="false"
      />
      <template #header>
        <el-alert
          v-if="userInfo.NOTICE3"
          title="Info alert"
          type="info"
          style="margin-bottom: 10px"
        >
          <template #title>
            <p v-html="userInfo.NOTICE3" />
          </template>
        </el-alert>
        <el-row>
          <el-col
            v-if="promotionUrl"
            :xs="24"
            :md="12"
            style="margin-bottom: 10px"
          >
            <el-input :value="promotionUrl">
              <template #suffix>
                <el-button text type="primary" @click="copy(promotionUrl)">
                  复制链接
                </el-button>
              </template>
              <template #prefix>我的专属链接：</template>
            </el-input>
          </el-col>
          <p v-else>专属链接获取失败</p>
          <el-col :xs="24" :md="12">
            &nbsp;&nbsp;
            <span>积分余额：{{ userInfo.point || 0 }}</span>
            &nbsp;&nbsp;
            <el-button
              v-if="userInfo.point2coinRate > 0"
              type="primary"
              @click="showTranslatePoint"
            >
              积分转金币
            </el-button>
          </el-col>
        </el-row>
      </template>
    </el-card>
  </el-col>

  <PageModal
    ref="translateRef"
    :modal-config="translateConfig"
    @submit-click="translateSubmit"
  >
    <template #header>
      <p>
        转换说明：转换比例为{{ userInfo.point2coinRate }}%。 （100积分={{
          100 * (userInfo.point2coinRate / 100)
        }}金币、1000积分={{
          1000 * (userInfo.point2coinRate / 100)
        }}金币以此类推）
      </p>

      <p v-if="translateRef.getFormData()['consumePoint'] > 0">
        兑换{{ translateRef.getFormData()["consumePoint"] }}积分可得{{
          (
            translateRef.getFormData()["consumePoint"] *
            (userInfo.point2coinRate / 100)
          ).toFixed(2)
        }}金币
      </p>

      <br />
    </template>
  </PageModal>
</template>

<script setup lang="ts">
import pageTable from "@/components/pageTable/index.vue";
import UserAPI from "@/api/user";
import { IModalConfig } from "@/components/CURD/types";
import { inject } from "vue";

const isMobile: any = inject("isMobile");
const tableLabels = ref([
  { prop: "account", label: "会员" },
  { prop: "qq", label: "QQ" },
  { prop: "registerDate", label: "注册日期" },
  { prop: "last_login_at", label: "最后登陆时间" },
  { prop: "coin", label: "金额余额" },
  { prop: "amount", label: "积分贡献" },
]);

const promotionUrl = ref("");
const listData: any = ref([]);
const fetchList = () => {
  UserAPI.getPromotionList().then((res: any) => {
    listData.value = res;
  });
};

const fetchPromotionUrl = () => {
  UserAPI.getPromotionUrl().then((res: any) => {
    promotionUrl.value = res;
  });
};

function copy(text: any) {
  const textArea = document.createElement("textarea");
  textArea.value = text;
  document.body.appendChild(textArea);
  textArea.select();
  try {
    const successful = document.execCommand("copy");
    if (successful) {
      ElMessage.success("复制成功");
    } else {
      ElMessage.error("无法复制，请手动复制链接");
    }
  } catch (err) {
    console.error("Error copying text: ", err);
  }
  document.body.removeChild(textArea);
}

const userInfo: any = ref({});
const fetchMe = () => {
  UserAPI.getInfo().then((res: any) => {
    userInfo.value = res;
  });
};

watch(isMobile, () => {
  if (isMobile.value) {
    translateConfig.width = "100%";
    translateConfig.labelPosition = "top";
    translateConfig.labelWidth = "10%";
  } else {
    translateConfig.width = "50%";
    translateConfig.labelPosition = "right";
    translateConfig.labelWidth = "30%";
  }
});

const translateRef = ref();
const translateConfig: IModalConfig<any> = reactive({
  labelWidth: isMobile.value ? "10%" : "30%",
  width: isMobile.value ? "100%" : "50%",
  labelPosition: isMobile.value ? "top" : "right",
  pageName: "积分转换",
  formItems: [
    {
      type: "input",
      label: "转换积分",
      prop: "consumePoint",
      rules: [
        {
          required: true,
          message: "请输入转换积分",
        },
      ],
    },
  ],
  component: "dialog",
  formAction: (data: any) => {
    return UserAPI.point2coin(data);
  },
});

const showTranslatePoint = () => {
  translateRef.value.setModalVisible();
};

const translateSubmit = () => {
  translateRef.value.setModalVisible();
  fetchList();
  fetchPromotionUrl();
  fetchMe();
};

onMounted(() => {
  fetchList();
  fetchPromotionUrl();
  fetchMe();
});
</script>
<style scoped>
.alert-danger {
  border-color: rgb(238, 211, 215);
  color: rgb(185, 74, 72);
  background-color: rgb(242, 222, 222);
}
</style>
