<template>
  <el-col>
    <el-card shadow="never" class="table-container">
      <el-tabs v-model="chargeType" class="demo-tabs">
        <el-tab-pane label="微信充值" name="wechatpay">
          <pageTable
            :tableLabel="tableLabelsWechat"
            :tableData="listData"
            :pagination="true"
            :current-page="pageParams.page"
            :pageSize="pageParams.pageSize"
            :total="pageParams.total"
            @handle-size="handleSize"
            @handle-current="handleCurrent"
          >
            <template #opname="slot">
              <el-button
                v-if="slot.data.status == 0"
                type="primary"
                plain
                @click="pass(slot.data.out_trade_no)"
              >
                通过
              </el-button>
              <el-button
                v-if="slot.data.status == 0"
                type="primary"
                plain
                @click="reject(slot.data.out_trade_no)"
              >
                拒绝
              </el-button>
            </template>
          </pageTable>
        </el-tab-pane>
        <el-tab-pane label="支付宝充值" name="other">
          <pageTable
            :tableLabel="tableLabelsAlipay"
            :tableData="listData"
            :pagination="true"
            :current-page="pageParams.page"
            :pageSize="pageParams.pageSize"
            :total="pageParams.total"
            @handle-size="handleSize"
            @handle-current="handleCurrent"
          />
        </el-tab-pane>
      </el-tabs>
      <template #header>
        <PageSearch
          ref="searchRef"
          :search-config="searchConfigWechat"
          @query-click="query"
          @reset-click="reset"
        />
      </template>
    </el-card>
  </el-col>
</template>

<script setup lang="ts">
import pageTable from "@/components/pageTable/index.vue";
import UserAPI from "@/api/user";
import { ISearchConfig } from "@/components/CURD/types";
const chargeType = ref("wechatpay"); // wechatpay | other
import { inject } from "vue";

const isMobile = inject("isMobile") as Ref<boolean>;

const tableLabelsWechat = ref([
  { prop: "uid", label: "用户ID", width: 120 },
  { prop: "out_trade_no", label: "订单号", width: 340 },
  { prop: "total_amount", label: "金额" },
  { prop: "statusStr", label: "状态", width: 200 },
  { prop: "created_at", label: "时间" },
  {
    label: "操作",
    type: "opt",
    prop: "opname",
    slot_name: "opname",
    width: 300,
    fixed: computed(() => {
      return isMobile.value ? false : "right";
    }),
  },
]);

const tableLabelsAlipay = ref([
  { prop: "uid", label: "用户ID", width: 120 },
  { prop: "out_trade_no", label: "订单号", width: 340 },
  { prop: "total_amount", label: "金额" },
  { prop: "statusStr", label: "状态", width: 200 },
  { prop: "created_at", label: "时间" },
]);

const listData: any = ref([]);
const pageParams: any = reactive({
  page: 1,
  pageSize: 20,
  total: 0,
});

const fetchList = () => {
  const params = searchRef.value.getQueryParams();
  const param: any = {
    page: pageParams.page,
    pageSize: pageParams.pageSize,
    ...params,
  };

  if (chargeType.value === "wechatpay") {
    UserAPI.getWechatPayAuditList(param).then((res: any) => {
      listData.value = res.items;
      pageParams.total = res.total;
    });
  } else {
    UserAPI.getAliPayAuditList(param).then((res: any) => {
      listData.value = res.items;
      pageParams.total = res.total;
    });
  }
};

watch(
  () => chargeType.value,
  () => {
    listData.value = [];
    reset();
  }
);

const handleSize = (size: number): void => {
  pageParams.pageSize = size;
  fetchList();
};
const handleCurrent = (current: number): void => {
  pageParams.page = current;
  fetchList();
};

const reset = () => {
  pageParams.page = 1;
  fetchList();
};

const pass = (id: any) => {
  UserAPI.wechatPayPass(id).then(() => {
    ElMessage.success("操作成功");
    fetchList();
  });
};
const reject = (id: any) => {
  UserAPI.wechatPayReject(id).then(() => {
    ElMessage.success("操作成功");
    fetchList();
  });
};
const searchRef = ref();
const searchConfigWechat: ISearchConfig = {
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
      label: "状态",
      prop: "status",
      width: "200px",
      initialValue: null,
      options: [
        {
          label: "待审核",
          value: 0,
        },
        {
          label: "通过",
          value: 1,
        },
        {
          label: "拒绝",
          value: 2,
        },
      ],
    },
  ],
};
const query = () => {
  fetchList(searchRef.value.getQueryParams());
};

onMounted(() => {
  fetchList();
});
</script>
