<template>
  <el-col>
    <el-card shadow="never" class="table-container">
      <pageTable
        :serialNo="true"
        :tableLabel="tableLabels"
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
    </el-card>
  </el-col>
</template>

<script setup lang="ts">
import pageTable from "@/components/pageTable/index.vue";
import UserAPI from "@/api/user";
import { useUserStore } from "@/store/modules/user";
const userStore = useUserStore();

const tableLabels = computed(() => {
  const temp: any = [
    { prop: "out_trade_no", label: "订单号" },
    { prop: "statusStr", label: "状态" },
    { prop: "total_amount", label: "金额" },
  ];
  if (userStore.user.roles.indexOf("ADMIN") >= 0) {
    temp.push({ prop: "trust_levelStr", label: "申请时信任等级" });
    temp.push({
      label: "操作",
      type: "opt",
      prop: "opname",
      slot_name: "opname",
      width: 200,
    });
  }
  return temp;
});

const listData: any = ref([]);
const pageParams: any = reactive({
  page: 1,
  pageSize: 20,
  total: 0,
});

const fetchList = (params: any = {}) => {
  const param: any = {
    page: pageParams.page,
    pageSize: pageParams.pageSize,
    ...params,
  };

  UserAPI.getWechatPayAuditList(param).then((res: any) => {
    listData.value = res.items;
    pageParams.total = res.total;
  });
};

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

onMounted(() => {
  fetchList();
});
</script>
