<template>
  <el-col>
    <el-card shadow="never" class="table-container">
      <template #header>
        <el-alert
          v-if="userInfo.NOTICE2"
          title="Info alert"
          style="margin-bottom: 10px"
          type="info"
        >
          <template #title>
            <p v-html="userInfo.NOTICE2" />
          </template>
        </el-alert>

        <PageSearch
          ref="searchRef"
          :search-config="searchConfig"
          @query-click="query"
          @reset-click="reset"
        />
        <el-button @click="exportLogs">导出</el-button>
      </template>
      <pageTable
        ref="tableRef"
        :tableLabel="tableLabels"
        :tableData="listData"
        :pagination="true"
        :current-page="pageParams.page"
        :pageSize="pageParams.pageSize"
        :total="pageParams.total"
        :choice="true"
        @handle-size="handleSize"
        @handle-current="handleCurrent"
      >
        <template #titleAdmin="slot">
          <el-row class="detail">
            <span>操作单号：</span>
            {{ slot.data.main_order_sn }}
          </el-row>
          <el-row class="detail">
            <span>平台：</span>
            {{ slot.data.platform_name }}
            <span>快递：</span>
            {{ slot.data.kuaidi_name }}
          </el-row>
          <el-row class="detail">
            <span>单号：</span>
            {{ slot.data.kuaidi_sn }}
            <span
              v-if="slot.data.kuaidi_sn"
              style="cursor: pointer"
              @click="copy(slot.data.kuaidi_sn)"
            >
              复制
            </span>
          </el-row>
        </template>

        <template #addressAdmin="slot">
          <el-row class="detail">
            <span>{{ slot.data.is_xinfeng == 1 ? "信封" : "礼品" }}：</span>
            {{ slot.data.goods_name }}
            <span>价格：</span>
            {{ slot.data.total_amount }}
            <span>订单号：</span>
            {{ slot.data.order_sn }}
          </el-row>

          <el-row class="detail">
            <span>发件人：</span>
            {{ slot.data.send_name }}
            <span>手机：</span>
            {{ slot.data.send_mobile }}
            <span>仓库：</span>
            {{
              [
                slot.data.storage_province,
                slot.data.storage_city,
                slot.data.storage_district,
                slot.data.storage_address,
              ].join(" ")
            }}
          </el-row>

          <el-row class="detail">
            <span>收件：</span>
            {{ slot.data.receive_name }}， {{ slot.data.receive_mobile }}，
            {{ [slot.data.receive_address].join(" ") }}
          </el-row>
        </template>

        <template #addressUser="slot">
          <el-row class="detail">
            <span>{{ slot.data.is_xinfeng == 1 ? "信封" : "礼品" }}：</span>
            {{ slot.data.goods_name }}
          </el-row>

          <el-row class="detail">
            <span>发件人：</span>
            {{ slot.data.send_name }}
            <span>手机：</span>
            {{ slot.data.send_mobile }}
            <span>仓库：</span>
            {{
              [
                slot.data.storage_province,
                slot.data.storage_city,
                slot.data.storage_district,
                slot.data.storage_address,
              ].join(" ")
            }}
          </el-row>

          <el-row class="detail">
            <span>收件：</span>
            {{ slot.data.receive_name }}， {{ slot.data.receive_mobile }}，
            {{ [slot.data.receive_address].join(" ") }}
          </el-row>
          <el-row class="detail">
            <span>操作单号：</span>
            {{ slot.data.main_order_sn }}
          </el-row>
        </template>

        <template #titleUser="slot">
          <el-row class="detail">
            <span>时间：</span>
            {{ slot.data.created_at }}
          </el-row>
          <el-row class="detail">
            <span>平台：</span>
            {{ slot.data.platform_name }}
            <span>快递：</span>
            {{ slot.data.kuaidi_name }}
          </el-row>
          <el-row class="detail">
            <span>单号：</span>
            {{ slot.data.kuaidi_sn }}
            <span
              v-if="slot.data.kuaidi_sn"
              style="cursor: pointer"
              @click="copy(slot.data.kuaidi_sn)"
            >
              复制
            </span>
          </el-row>
        </template>

        <template #statusAmount="slot">
          <el-col>
            <el-row class="detail" style="text-align: center">
              <el-col :span="24">
                {{ slot.data.statusStr }}
              </el-col>
              <el-col :span="24">
                {{ slot.data.total_amount }}
              </el-col>
            </el-row>
          </el-col>
        </template>

        <template #opname="slot">
          <el-button
            v-if="slot.data.status == 1 || slot.data.status == 4"
            type="primary"
            @click="queryOrderStatus(slot.data.order_sn)"
          >
            查询订单状态
          </el-button>
        </template>
      </pageTable>
    </el-card>
  </el-col>
</template>

<script setup lang="ts">
import PageSearch from "@/components/CURD/PageSearch.vue";
import pageTable from "@/components/pageTable/index.vue";
import GiftAPI from "@/api/gift";
import { exportData, getDateTime } from "@/utils/common";
import UserAPI from "@/api/user";
import { useUserStore } from "@/store";
import { isAdmin } from "@/utils/auth";
import moment from "moment";
import { inject } from "vue";

const isMobile = inject("isMobile") as Ref<boolean>;

let roles = isAdmin() ? "ADMIN" : "USER";

const tableLabels = computed(() => {
  if (roles == "ADMIN") {
    return [
      {
        prop: "operate_uid",
        label: "UID",
        width: 140,
      },
      {
        prop: "title",
        type: "opt",
        slot_name: "titleAdmin",
        label: "快递名称/快递单号",
        width: "420px",
      },
      {
        prop: "title",
        type: "opt",
        slot_name: "addressAdmin",
        label: "发货信息/收货信息",
        width: "700px",
      },
      { prop: "created_at", label: "下单时间", width: "160px" },
      { prop: "statusStr", label: "状态", width: "150px" },
      {
        label: "操作",
        type: "opt",
        prop: "opname",
        slot_name: "opname",
        width: 150,
      },
    ];
  }
  return [
    {
      prop: "title",
      type: "opt",
      slot_name: "titleUser",
      label: "下单时间/快递名称/快递单号",
      width: "400px",
    },
    {
      prop: "title",
      type: "opt",
      slot_name: "addressUser",
      label: "发货信息/收货信息",
      width: "500px",
    },
    {
      prop: "status",
      label: "状态/金额",
      type: "opt",
      slot_name: "statusAmount",
    },
    {
      label: "操作",
      type: "opt",
      prop: "opname",
      slot_name: "opname",
      width: 150,
    },
  ];
});

const tableRef = ref();

const exportLogs = () => {
  const params: any = { ...searchRef.value.getQueryParams() };
  const rows: any = tableRef.value.getSelectionRows();
  if (rows.length > 0) {
    const ids: any = [];
    rows.forEach((item: any) => {
      ids.push(item.id);
    });
    params.ids = ids.join(",");
  }
  const date = getDateTime();
  exportData(GiftAPI.exportLogs, params, "购买记录" + date + ".xls");
};

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

  if (isMobile.value) {
    param.created_at = param.created_at1.split("/");
  }
  delete param.created_at1;

  if (!(param.status >= 1 && param.status <= 4)) {
    delete param.status;
  }

  GiftAPI.logs(param).then((res: any) => {
    listData.value = res.items;
    pageParams.total = res.total;
    listData.value.forEach((item: any) => {
      switch (item.status) {
        case 1:
          item.statusStr = "待取号";
          break;
        case 2:
          item.statusStr = "已出单（待揽件）";
          break;
        case 3:
          item.statusStr = "已出单（已揽件）";
          break;
        case 4:
          item.statusStr = "取号失败";
          break;
        case 5:
          item.statusStr = "已取消";
          break;
      }

      item.is_xinfengStr = item.is_xinfeng == 1 ? "信封" : "礼品";

      item.total_amount =
        item.total_amount > 0 ? item.total_amount + "元" : item.total_amount;

      item.receive_address = [
        item.receive_province,
        item.receive_city,
        item.receive_district,
        item.receive_address,
      ].join(" ");
    });
  });
};

const searchRef = ref();

const searchConfig = computed(() => {
  const userStore = useUserStore();

  const items: any = {
    pageName: "搜索",
    formItems: [],
  };

  if (userStore.user.roles?.includes("ADMIN")) {
    items.formItems = [
      {
        type: "input",
        label: "用户UID",
        prop: "uid",
        width: "200px",
      },
      {
        type: "input",
        label: "订单号",
        prop: "order_sn",
        width: "200px",
      },
      {
        type: "input",
        label: "快递名称",
        prop: "kuaidi_name",
        width: "200px",
      },
      {
        type: "input",
        label: "快递单号",
        prop: "kuaidi_sn",
        width: "200px",
      },
      {
        type: "select",
        label: "状态",
        prop: "is_cancel",
        width: "200px",
        options: [
          {
            label: "未取消",
            value: 0,
          },
          {
            label: "已取消",
            value: 1,
          },
        ],
      },
    ];
  } else {
    items.formItems = [
      {
        type: "input",
        label: "订单号",
        prop: "order_sn",
        width: "200px",
      },
      {
        type: "input",
        label: "快递单号",
        prop: "kuaidi_sn",
        width: "200px",
      },
    ];
  }

  if (isMobile.value) {
    items.formItems.push({
      type: "calendar",
      label: "下单时间",
      prop: "created_at1",
      width: "400px",
      minDate: new Date(2024, 0, 1),
      maxDate: new Date(),
    });
  } else {
    items.formItems.push({
      type: "date",
      label: "下单时间",
      prop: "created_at",
      width: "400px",
      initialValue: [
        moment().add(-10, "days").format("YYYY-MM-DD"),
        moment().add(0, "days").format("YYYY-MM-DD"),
      ],
    });
  }

  return items;
});
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
const handleSize = (size: number): void => {
  pageParams.pageSize = size;
  query();
};
const handleCurrent = (current: number): void => {
  pageParams.page = current;
  query();
};

const query = () => {
  fetchList(searchRef.value.getQueryParams());
};

const queryOrderStatus = (order_sn: any) => {
  GiftAPI.queryOrderStatus(order_sn).then((res) => {
    if (!res) {
      ElMessage.error("订单已取消");
    } else {
      ElMessage.success("取号成功");
    }
    reset();
  });
};

const reset = () => {
  pageParams.page = 1;
  const params: any = {};
  const date = new Date();
  const year = date.getFullYear(); // 获取年份
  const month = String(date.getMonth() + 1).padStart(2, "0"); // 获取月份 (0-11) + 1
  const day = String(date.getDate()).padStart(2, "0"); // 获取日期

  const formattedDate = `${year}-${month}-${day}`;
  const search: any = {
    created_at1: `${formattedDate}/${formattedDate}`,
    created_at: [formattedDate, formattedDate],
  };

  searchRef.value.setFormData({ ...search });

  fetchList({
    ...params,
    ...searchRef.value.getQueryParams(),
  });
};

const userInfo: any = ref({});
const fetchMe = () => {
  UserAPI.getInfo().then((res: any) => {
    userInfo.value = res;
  });
};

onMounted(() => {
  const route = useRoute();
  const params: any = {};
  if (route.query.ids) {
    params.ids = route.query.ids;
  }

  const search: any = {};

  if (route.query.mainOrderSn) {
    search.order_sn = route.query.mainOrderSn;
  }

  const date = new Date();
  const year = date.getFullYear(); // 获取年份
  const month = String(date.getMonth() + 1).padStart(2, "0"); // 获取月份 (0-11) + 1
  const day = String(date.getDate()).padStart(2, "0"); // 获取日期

  const formattedDate = `${year}-${month}-${day}`;
  search.created_at1 = `${formattedDate}/${formattedDate}`;
  search.created_at = [formattedDate, formattedDate];
  searchRef.value.setFormData({ ...search });

  fetchList({
    ...params,
    ...searchRef.value.getQueryParams(),
  });

  fetchMe();
});
</script>

<style scoped>
.alert-danger {
  border-color: rgb(238, 211, 215);
  color: rgb(185, 74, 72);
  background-color: rgb(242, 222, 222);
}
.detail span {
  font-weight: bold;
}

.detail span:not(:first-child) {
  padding-left: 8px;
}
</style>
