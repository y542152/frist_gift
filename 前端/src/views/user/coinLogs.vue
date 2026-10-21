<template>
  <div class="dashboard-container">
    <el-card shadow="never" class="table-container">
      <el-tabs v-model="logType" class="demo-tabs">
        <el-tab-pane label="金币记录" name="coin">
          <PageSearch
            ref="searchRefCoin"
            :search-config="searchConfig"
            @query-click="query"
            @reset-click="reset"
          />
          <pageTable
            :tableLabel="tableLabelsCoin"
            :tableData="listDataCoin"
            :pagination="true"
            :page-size="pageParamsCoin.pageSize"
            :current-page="pageParamsCoin.page"
            :total="pageParamsCoin.total"
            @handle-size="handleSizeCoin"
            @handle-current="handleCurrentCoin"
          />
        </el-tab-pane>
        <el-tab-pane label="积分记录" name="point">
          <PageSearch
            ref="searchRefPoint"
            :search-config="searchConfig"
            @query-click="query"
            @reset-click="reset"
          />
          <pageTable
            :tableLabel="tableLabelsPoint"
            :tableData="listDataPoint"
            :pagination="true"
            :page-size="pageParamsPoint.pageSize"
            :current-page="pageParamsPoint.page"
            :total="pageParamsPoint.total"
            @handle-size="handleSizePoint"
            @handle-current="handleCurrentPoint"
          />
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import UserAPI from "@/api/user";
import { IModalConfig, ISearchConfig } from "@/components/CURD/types";
import { useUserStore } from "@/store/modules/user";
import PageSearch from "@/components/CURD/PageSearch.vue";

const dialogVisible = ref(false);
const userStore = useUserStore();
const date: Date = new Date();

const formData: any = computed(() => {
  return chargeRef.value?.getFormData();
});

const currentDetail: any = reactive({
  payType: "alipay",
});

const settingInfo: any = ref({});
const fetchSetting = () => {
  UserAPI.getSettingInfo().then((res: any) => {
    settingInfo.value = res;
  });
};

const chargeRef = ref();
const chargeConfig: IModalConfig<any> = reactive({
  labelWidth: "200px",
  width: "50%",
  pageName: "充值",
  formItems: [
    {
      type: "opt",
      slotName: "money",
      label: "金额",
      prop: "money",
      options: [
        {
          label: "0.01",
          value: 0.01,
        },
        {
          label: "50",
          value: 50,
        },
        {
          label: "100",
          value: 100,
        },
        {
          label: "200",
          value: 200,
        },
        {
          label: "300",
          value: 300,
        },
        {
          label: "500",
          value: 500,
        },
        {
          label: "1000",
          value: 1000,
        },
      ],
      rules: [
        {
          required: true,
          message: "请选择充值金额",
        },
      ],
    },
    {
      type: computed(() => {
        return formData.payType === "wechatpay" ? "input" : "";
      }),
      prop: "orderNo",
      label: "转账单号",
    },
    {
      type: "radio",
      label: "支付方式",
      prop: "payType",
      initialValue: "alipay",
      options: [
        {
          label: "支付宝",
          value: "alipay",
        },
        {
          label: "微信支付",
          value: "wechatpay",
        },
      ],
      rules: [
        {
          required: true,
          message: "请选择支付方式",
        },
      ],
    },
  ],
  component: "dialog",
  customFormAction: true,
});

const translateRef = ref();
const translateConfig: IModalConfig<any> = reactive({
  labelWidth: "200px",
  width: "50%",
  pageName: "充值",
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

const qr = ref("");
const qrVisible = ref(false);
const orderNo = ref("");

let timer: any = null;
const loopCount = ref(0);

const logType = ref("coin"); // coin | point

const tableLabelsCoin = ref([
  { prop: "uid", label: "用户UID", width: "100px" },
  { prop: "nickname", label: "用户名" },
  { prop: "amount", label: "变更金额" },
  { prop: "new_amount", label: "变更后余额" },
  { prop: "created_at", label: "时间" },
  { prop: "channelStr", label: "渠道" },
  { prop: "out_trade_no", label: "操作单号" },
  { prop: "desc", label: "操作描述" },
]);

const tableLabelsPoint = ref([
  { prop: "uid", label: "用户UID", width: "100px" },
  { prop: "nickname", label: "用户名" },
  { prop: "amount", label: "变更金额" },
  { prop: "new_amount", label: "变更后余额" },
  { prop: "created_at", label: "时间" },
  { prop: "channelStr", label: "渠道" },
  { prop: "out_trade_no", label: "操作单号" },
  { prop: "desc", label: "操作描述" },
]);

const listDataCoin: any = ref([]);
const listDataPoint: any = ref([]);
const pageParamsCoin: any = reactive({
  page: 1,
  pageSize: 10,
  total: 0,
});

const pageParamsPoint: any = reactive({
  page: 1,
  pageSize: 10,
  total: 0,
});

const handleSizeCoin = (size: number): void => {
  pageParamsCoin.pageSize = size;
  fetchCoinLogs();
};
const handleCurrentCoin = (current: number): void => {
  pageParamsCoin.page = current;
  fetchCoinLogs();
};

const handleSizePoint = (size: number): void => {
  pageParamsPoint.pageSize = size;
  fetchPointLogs();
};
const handleCurrentPoint = (current: number): void => {
  pageParamsPoint.page = current;
  fetchPointLogs();
};

const fetchCoinLogs = () => {
  const params = searchRefCoin.value.getQueryParams();
  if (!params.uid) {
    delete params.uid;
  }
  if (!params.type) {
    delete params.type;
  }
  if (!params.channel) {
    delete params.channel;
  }
  UserAPI.coinLogs({
    page: pageParamsCoin.page,
    pageSize: pageParamsCoin.pageSize,
    ...params,
  }).then((res: any) => {
    listDataCoin.value = res.items;
    pageParamsCoin.total = res.total;
  });
};

const fetchPointLogs = () => {
  const params = searchRefPoint.value.getQueryParams();
  if (!params.uid) {
    delete params.uid;
  }
  if (!params.type) {
    delete params.type;
  }
  if (!params.channel) {
    delete params.channel;
  }
  UserAPI.pointLogs({
    page: pageParamsPoint.page,
    pageSize: pageParamsPoint.pageSize,
    ...params,
  }).then((res: any) => {
    listDataPoint.value = res.items;
    pageParamsPoint.total = res.total;
  });
};

const searchRefPoint = ref();
const searchRefCoin = ref();
const searchConfig: ISearchConfig = {
  pageName: "搜索",
  formItems: [
    {
      type: "input",
      label: "用户UID",
      prop: "uid",
      width: "200px",
    },
    {
      type: "select",
      label: "操作",
      prop: "type",
      width: "200px",
      options: [
        {
          label: "收入",
          value: "in",
        },
        {
          label: "支出",
          value: "out",
        },
      ],
    },
    {
      type: "select",
      label: "渠道",
      prop: "channel",
      width: "200px",
      options: computed(() => {
        if (logType.value === "point") {
          return [
            {
              label: "下级充值奖励",
              value: "cr",
            },
            {
              label: "积分转换为金币",
              value: "ptc",
            },
          ];
        } else {
          return [
            {
              label: "充值",
              value: "charge",
            },
            {
              label: "积分转换为金币",
              value: "ptc",
            },
            {
              label: "购买",
              value: "buy",
            },
          ];
        }
      }),
    },
  ],
};

const query = () => {
  if (logType.value === "point") {
    fetchPointLogs();
  } else {
    fetchCoinLogs();
  }
};

const reset = () => {
  if (logType.value === "point") {
    pageParamsPoint.page = 1;
    pageParamsPoint.total = 0;
    fetchPointLogs();
  } else {
    pageParamsCoin.page = 1;
    pageParamsCoin.total = 0;
    fetchCoinLogs();
  }
};

watch(
  () => formData,
  () => {
    if (formData.payType === "alipay") {
      chargeConfig.formItems = [
        {
          type: "custom",
          label: "金额",
          prop: "money",
          options: [
            {
              label: "0.01",
              value: 0.01,
            },
            {
              label: "50",
              value: 50,
            },
            {
              label: "100",
              value: 100,
            },
            {
              label: "200",
              value: 200,
            },
            {
              label: "300",
              value: 300,
            },
            {
              label: "500",
              value: 500,
            },
            {
              label: "1000",
              value: 1000,
            },
          ],
          rules: [
            {
              required: true,
              message: "请选择充值金额",
            },
          ],
        },
        {
          type: "radio",
          label: "支付方式",
          prop: "payType",
          initialValue: "alipay",
          options: [
            {
              label: "支付宝",
              value: "alipay",
            },
            {
              label: "微信支付",
              value: "wechatpay",
            },
          ],
          rules: [
            {
              required: true,
              message: "请选择支付方式",
            },
          ],
        },
      ];
    } else {
      chargeConfig.formItems = [
        {
          type: "custom",
          label: "金额",
          prop: "money",
          options: [
            {
              label: "0.01",
              value: 0.01,
            },
            {
              label: "50",
              value: 50,
            },
            {
              label: "100",
              value: 100,
            },
            {
              label: "200",
              value: 200,
            },
            {
              label: "300",
              value: 300,
            },
            {
              label: "500",
              value: 500,
            },
            {
              label: "1000",
              value: 1000,
            },
          ],
          rules: [
            {
              required: true,
              message: "请选择充值金额",
            },
          ],
        },
        {
          type: "input",
          prop: "orderNo",
          label: "转账单号",
        },
        {
          type: "radio",
          label: "支付方式",
          prop: "payType",
          initialValue: "alipay",
          options: [
            {
              label: "支付宝",
              value: "alipay",
            },
            {
              label: "微信支付",
              value: "wechatpay",
            },
          ],
          rules: [
            {
              required: true,
              message: "请选择支付方式",
            },
          ],
        },
      ];
    }
  }
);

watch(
  () => qrVisible.value,
  () => {
    if (qrVisible.value) {
      timer = setInterval(() => {
        if (loopCount.value >= 6) {
          clearInterval(timer);
        } else {
          loopCount.value++;
          const money =
            diyMoney.value > 0 ? diyMoney.value : currentDetail.money;
          UserAPI.getPayResult(orderNo.value, money)
            .then((res: any) => {
              if (res === true) {
                ElMessage.success("充值成功");
                qrVisible.value = false;
                fetchMe();
              }
            })
            .catch((error: any) => {
              console.log("error", error);
            });
        }
      }, 5000);
      console.log("开启");
    } else {
      if (timer != null) {
        clearInterval(timer);
      }
      console.log("关闭");
    }
  }
);

const userInfo: any = ref({});
const fetchMe = () => {
  UserAPI.getInfo().then((res: any) => {
    userInfo.value = res;
  });
};
onMounted(() => {
  fetchMe();
  fetchSetting();
  fetchCoinLogs();
  fetchPointLogs();
});
</script>

<style lang="scss" scoped>
.dashboard-container {
  position: relative;
  padding: 24px;
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
