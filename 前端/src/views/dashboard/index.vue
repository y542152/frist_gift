<template>
  <div class="dashboard-container">
    <el-card shadow="never">
      <el-row justify="space-between">
        <el-col :span="18" :xs="24">
          <div class="flex h-full items-center">
            <img
              v-if="userStore.user.avatar"
              class="w-20 h-20 mr-5 rounded-full"
              :src="userStore.user.avatar"
              @click="handleOpenUserProfile"
            />
            <img
              v-else
              class="w-20 h-20 mr-5 rounded-full"
              src="https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png"
              @click="handleOpenUserProfile"
            />
            <el-upload
              ref="upload"
              class="upload-demo"
              :action="uploadUrl"
              :on-success="handleAvatarSuccess"
              :before-upload="beforeAvatarUpload"
              :show-file-list="false"
              :limit="1"
              :multiple="false"
              :headers="headers"
              :on-exceed="handleExceed"
              :auto-upload="true"
            >
              <template #trigger>
                <el-button id="upload" type="primary" style="display: none">
                  select file
                </el-button>
              </template>
            </el-upload>

            <div>
              <p>{{ greetings }}</p>
              <p>金币余额：{{ userInfo.coin || 0 }}</p>
              <el-button
                type="primary"
                :size="isMobile ? 'small' : 'default'"
                @click="showCharge"
              >
                支付宝充值
              </el-button>
              <el-button
                type="success"
                :size="isMobile ? 'small' : 'default'"
                @click="showWechatCharge"
              >
                微信充值
              </el-button>
            </div>
          </div>
        </el-col>

        <el-col :span="6" :xs="24">
          <div class="flex h-full items-center justify-around">
            <el-col>
              <p>上次登录时间: {{ userInfo.loginInfo?.last_login_at || "" }}</p>
              <p>上次登录IP: {{ userInfo.loginInfo?.ip || "" }}</p>
            </el-col>
          </div>
        </el-col>
      </el-row>
    </el-card>

    <br />
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

  <el-dialog
    v-model="qrVisible"
    title="扫码支付"
    :width="isMobile ? ' 100%' : '40%'"
    destroy-on-close
  >
    <Vue3NextQrcode v-loading="!qr" :text="qr" style="text-align: center" />
    <el-col style="text-align: center">
      <span>使用支付宝扫一扫支付</span>
    </el-col>
  </el-dialog>

  <el-dialog
    v-model="dialogVisible"
    :title="currentDetail.payType == 'wechatpay' ? '微信充值' : '充值'"
    :style="isMobile ? 'width: 100%' : 'width: 600px'"
    destroy-on-close
  >
    <el-col v-if="currentDetail.payType == 'alipay'" :span="24">
      <pageForm
        ref="ruleFormDialog"
        :label-width="'120px'"
        :data="items"
        :field="currentDetail"
        :rules="chargeRules"
      >
        <template #payType="slot">
          <el-radio-group v-model="slot.data[slot.prop]">
            <el-radio
              v-for="(info, key) in slot.item.options"
              :key="key"
              :label="info.value"
            >
              <el-row>
                <el-col
                  style="
                    display: flex;
                    align-items: center;
                    flex: auto;
                    margin-right: 5px;
                  "
                >
                  {{ info.label }}
                </el-col>
                <el-col style="display: flex; align-items: center; flex: auto">
                  <img width="16" src="../../assets/images/alipay.png" />
                </el-col>
              </el-row>
            </el-radio>
          </el-radio-group>
        </template>
        <template #money="slot">
          <el-col :span="24">
            <div class="skl_moneyBox">
              <a
                v-for="(info, key) in slot.item.options"
                :key="key"
                :style="isMobile ? '  width: 50%;' : ' width: 20%;'"
                :class="
                  currentDetail.money == info.value ? 'skl_selectli' : 'skl'
                "
                @click="setMoneyOptionManual(info.value)"
              >
                ￥ {{ info.label }} 元
              </a>
            </div>

            <el-input
              v-model="diyMoney"
              style="width: 100px"
              placeholder="自定义金额"
              @click="setDiyMoney"
            />
          </el-col>
        </template>
      </pageForm>
      <el-col style="text-align: center">
        <el-button type="primary" @click="chargeSubmit">提交</el-button>
      </el-col>
    </el-col>

    <el-col v-else :span="24">
      <pageForm
        ref="ruleFormDialog"
        :label-width="currentDetail.wechatPayStep == 1 ? '120px' : '0px'"
        :data="wechatItems"
        :field="wechatPayDetail"
        :rules="wechatChargeRules"
      >
        <template #wechatpay>
          <el-col
            :span="24"
            style="justify-content: center; text-align: center"
          >
            <img :src="userInfo.WECHAT_PAY_QR" width="200" />
            <p class="notice">
              微信扫码付款后，下一步填写微信转账单号即可自动识别到账！
            </p>
          </el-col>
        </template>
      </pageForm>
      <el-col
        v-if="currentDetail.wechatPayStep == 0"
        style="text-align: center"
      >
        <el-button type="primary" @click="next">下一步</el-button>
      </el-col>

      <el-col
        v-if="currentDetail.wechatPayStep == 1"
        style="text-align: center"
      >
        <el-col :span="24">
          <span class="notice">
            请填写正确的转账单号和金额，以免影响自动到账！
          </span>
        </el-col>

        <el-button type="primary" @click="prev">上一步</el-button>
        <el-button type="primary" @click="wechatChargeSubmit">提交</el-button>
      </el-col>
    </el-col>
  </el-dialog>
</template>

<script setup lang="ts">
import UserAPI from "@/api/user";
import pageForm from "@/components/pageForm/index.vue";
import { ISearchConfig } from "@/components/CURD/types";
import { useUserStore } from "@/store/modules/user";
import { Vue3NextQrcode } from "vue3-next-qrcode";
import PageSearch from "@/components/CURD/PageSearch.vue";
import { isAdmin } from "@/utils/auth";
import { inject } from "vue";

const isMobile = inject("isMobile") as Ref<boolean>;

import type { UploadInstance, UploadProps, UploadRawFile } from "element-plus";

const upload = ref<UploadInstance>();

let uploadUrl = import.meta.env.VITE_APP_API_URL + "/api/upload";
if (import.meta.env.VITE_APP_API_URL.indexOf("/api") > -1) {
  uploadUrl = import.meta.env.VITE_APP_API_URL + "/upload";
}
const headers = {
  token: localStorage.getItem(isAdmin() ? "admin-token" : "token"),
};

const handleExceed: UploadProps["onExceed"] = (files) => {
  console.log(files, "onExceed");
  upload.value!.clearFiles();
  const file = files[0] as UploadRawFile;
  upload.value!.handleStart(file);
};

const handleOpenUserProfile = () => {
  document.getElementById("upload")!.click();
};

const handleAvatarSuccess: UploadProps["onSuccess"] = (
  response,
  uploadFile
) => {
  upload.value!.clearFiles();
  if (response.data?.url) {
    UserAPI.updateAvatar({ avatar: response.data.url }).then(() => {
      ElMessage.success("上传成功");
      fetchMe();
    });
  } else {
    ElMessage.error("上传失败");
  }
};

const beforeAvatarUpload: UploadProps["beforeUpload"] = (rawFile) => {
  if (rawFile.type !== "image/jpeg" && rawFile.type !== "image/png") {
    console.log(rawFile.type);
    ElMessage.error("头像格式需为JPG/PNG!");
    return false;
  } else {
  }
  return true;
};

const dialogVisible = ref(false);
const userStore = useUserStore();
const date: Date = new Date();
const greetings = computed(() => {
  const hours = date.getHours();
  if (hours >= 6 && hours < 8) {
    return "晨起披衣出草堂，轩窗已自喜微凉🌅！";
  } else if (hours >= 8 && hours < 12) {
    return "上午好，" + userStore.user.nickname + "！";
  } else if (hours >= 12 && hours < 18) {
    return "下午好，" + userStore.user.nickname + "！";
  } else if (hours >= 18 && hours < 24) {
    return "晚上好，" + userStore.user.nickname + "！";
  } else {
    return "偷偷向银河要了一把碎星，只等你闭上眼睛撒入你的梦中，晚安🌛！";
  }
});

const currentDetail: any = reactive({
  payType: "alipay",
  wechatPayStep: 0,
});

const items: any = computed(() => {
  return [
    {
      type: "opt",
      slotName: "money",
      label: "金额",
      prop: "money",
      options: [
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
    },
    {
      type: "opt",
      slotName: "payType",
      label: "支付方式",
      prop: "payType",
      initialValue: "alipay",
      options: [
        {
          label: "支付宝",
          value: "alipay",
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
});

const wechatItems: any = computed(() => {
  if (currentDetail.wechatPayStep == 0) {
    return [
      {
        type: "opt",
        slotName: "wechatpay",
        prop: "wechatpay",
        label: "",
        width: 0,
      },
    ];
  } else {
    return [
      {
        type: "input",
        prop: "money",
        label: "金额",
        width: "80%",
      },
      {
        type: "input",
        prop: "orderNo",
        label: "转账单号",
        placeholder: "请输入微信转账单号",
        width: "80%",
      },
    ];
  }
});

const chargeRules: any = computed(() => {
  return {
    payType: [
      {
        required: true,
        message: "请选择支付方式",
      },
    ],
  };
});

const checkOrderSn = (rule: any, val: any, callback: any) => {
  var reg = /^[1-9][0-9]*$/;
  if (!reg.test(val) || val.length != 32) {
    callback(new Error("转账单号填写错误，无法提交！"));
  } else {
    callback();
  }
};

const checkNumber = (rule: any, val: any, callback: any) => {
  var reg = /^[0-9]*(\.[0-9]+)?$/;
  if (!reg.test(val)) {
    callback(new Error("金额只能填写数字和小数点"));
  } else {
    callback();
  }
};

const wechatChargeRules = {
  orderNo: [
    {
      required: true,
      message: "请输入微信转账单号",
    },
    {
      required: true,
      validator: checkOrderSn,
    },
  ],
  money: [
    {
      required: true,
      message: "请输入金额",
    },
    {
      required: true,
      validator: checkNumber,
    },
  ],
};

const qr = ref("");
const qrVisible = ref(false);
const orderNo = ref("");

const ruleFormDialog = ref();
const chargeSubmit = async () => {
  const e = await ruleFormDialog.value.validateForm();
  if (!e) {
    return;
  }
  let money = diyMoney.value > 0 ? diyMoney.value : currentDetail.money;
  money = Math.floor(money * 100) / 100;
  if (!money) {
    ElMessage.error("请选择充值金额");
    return;
  }

  UserAPI.getPayQr(money).then((res: any) => {
    qr.value = res.qr;
    orderNo.value = res.orderNo;
    qrVisible.value = true;
  });
};

const next = () => {
  currentDetail.wechatPayStep = 1;
};

const prev = () => {
  currentDetail.wechatPayStep = 0;
};

const wechatPayDetail = reactive({
  money: 0,
  orderNo: "",
});

const wechatChargeSubmit = async () => {
  const e = await ruleFormDialog.value.validateForm();
  if (!e) {
    return;
  }
  let money = wechatPayDetail.money;
  money = Math.floor(money * 100) / 100;
  if (!money) {
    ElMessage.error("请输入充值金额");
    return;
  }

  const params = {
    money: money,
    orderNo: wechatPayDetail.orderNo,
  };
  UserAPI.submitWechaPay(params).then((res: any) => {
    if (res) {
      ElMessage.success("充值成功");
      fetchMe();
      fetchCoinLogs();
      fetchPointLogs();
    } else {
      ElMessage.success("充值已提交，等待审核");
    }
    ruleFormDialog.value.resetForm();
    dialogVisible.value = false;
  });
};

const isDiy = ref(false);
const diyMoney = ref();
const setDiyMoney = () => {
  isDiy.value = true;
  currentDetail.money = 0;
};

const setMoneyOption = () => {
  isDiy.value = false;
  diyMoney.value = "";
};

const setMoneyOptionManual = (money: any) => {
  isDiy.value = false;
  diyMoney.value = "";
  currentDetail.money = money;
};

const showCharge = () => {
  currentDetail.payType = "alipay";
  dialogVisible.value = true;
};

const showWechatCharge = () => {
  currentDetail.wechatPayStep = 0;
  currentDetail.payType = "wechatpay";
  dialogVisible.value = true;
};

let timer: any = null;
const loopCount = ref(0);

const logType = ref("coin"); // coin | point

const tableLabelsCoin = ref([
  { prop: "created_at", label: "时间" },
  { prop: "channelStr", label: "渠道" },
  { prop: "amount", label: "变更金额" },
  { prop: "new_amount", label: "变更后余额" },
  { prop: "out_trade_no", label: "操作单号" },
  { prop: "desc", label: "操作描述" },
]);

const tableLabelsPoint = ref([
  { prop: "created_at", label: "时间" },
  { prop: "channelStr", label: "渠道" },
  { prop: "amount", label: "变更金额" },
  { prop: "new_amount", label: "变更后余额" },
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
  let roles = isAdmin() ? "ADMIN" : "USER";
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

  const temp: any = {
    page: pageParamsCoin.page,
    pageSize: pageParamsCoin.pageSize,
    ...params,
  };

  if (roles == "ADMIN") {
    temp.getOwn = true;
  }

  UserAPI.coinLogs(temp).then((res: any) => {
    listDataCoin.value = res.items;
    pageParamsCoin.total = res.total;
  });
};

const fetchPointLogs = () => {
  let roles = isAdmin() ? "ADMIN" : "USER";
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

  const temp: any = {
    page: pageParamsPoint.page,
    pageSize: pageParamsPoint.pageSize,
    ...params,
  };

  if (roles == "ADMIN") {
    temp.getOwn = true;
  }

  UserAPI.pointLogs(temp).then((res: any) => {
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
    const params = searchRefPoint.value.getQueryParams();
    if (params.type == "") {
      delete params.type;
    }

    if (params.channel == "") {
      delete params.channel;
    }

    fetchPointLogs(params);
  } else {
    const params = searchRefCoin.value.getQueryParams();
    if (params.type == "") {
      delete params.type;
    }

    if (params.channel == "") {
      delete params.channel;
    }
    fetchCoinLogs(params);
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
    userStore.user.avatar = res.avatar;
  });
};

onMounted(() => {
  fetchMe();
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

.notice {
  color: red;
}

.notice1 {
  color: rgb(33, 169, 248);
}

.skl_moneyBox {
  height: auto;
  float: left;
  width: auto;
  padding-top: 5px;
  padding-bottom: 5px;
}

.skl_moneyBox a {
  list-style: none;
  font-family: "微软雅黑";
  font-size: 12px;
  float: left;
  height: auto;
  margin-right: 10px;
  border: 1px double #c4deff;
  border-radius: 5px;
  text-align: center;
  padding-top: 5px;
  padding-right: 5px;
  padding-bottom: 5px;
  padding-left: 5px;
  margin-top: 3px;
  margin-bottom: 3px;
  cursor: pointer;
  color: #333;
}

.skl_selectli {
  background-image: url(../../assets/images/select.png);
  background-repeat: no-repeat;
  background-position: right bottom;
  background-color: #e1f5ff;
}
</style>
