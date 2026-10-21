<template>
  <el-col>
    <el-card shadow="never" class="table-container">
      <template #header>
        <PageSearch
          ref="searchRef"
          :search-config="searchConfig"
          @query-click="query"
          @reset-click="reset"
        />
      </template>

      <pageTable
        v-loading="loading"
        :tableLabel="tableLabels"
        :tableData="listData"
        :pagination="true"
        :page-size="pageParams.pageSize"
        :current-page="pageParams.page"
        :total="pageParams.total"
        @handle-size="handleSize"
        @handle-current="handleCurrent"
      />
    </el-card>
  </el-col>
</template>

<script setup lang="ts">
import pageTable from "@/components/pageTable/index.vue";
import UserAPI from "@/api/user";
import { ISearchConfig } from "@/components/CURD/types";

const tableLabels = ref([
  { prop: "uid", label: "用户ID" },
  { prop: "account", label: "账户" },
  { prop: "last_login_at", label: "登陆时间" },
  { prop: "ip", label: "IP" },
]);

const listData: any = ref([]);
const pageParams: any = reactive({
  page: 1,
  pageSize: 10,
  total: 0,
});

const searchRef = ref();

const fetchList = (params: any = {}) => {
  const param: any = {
    page: pageParams.page,
    pageSize: pageParams.pageSize,
    ...params,
    ...searchRef.value.getQueryParams(),
  };

  UserAPI.fetchUserLoginLogs(param).then((res: any) => {
    listData.value = res.items;
    pageParams.total = res.total;
  });
};

const handleSize = (size: number): void => {
  pageParams.pageSize = size;
  query();
};
const handleCurrent = (current: number): void => {
  pageParams.page = current;
  query();
};

const query = () => {
  fetchList();
};

const reset = () => {
  pageParams.page = 1;
  fetchList();
};

const loading = ref(false);

const searchConfig: ISearchConfig = {
  pageName: "搜索",
  formItems: [
    {
      type: "input",
      label: "用户UID",
      prop: "uid",
      width: "200px",
    },
  ],
};

onMounted(() => {
  fetchList();
});
</script>
