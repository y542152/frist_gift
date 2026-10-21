<template>
  <el-col>
    <el-card shadow="never" class="table-container">
      <pageTable
        :serialNo="true"
        :tableLabel="tableLabels"
        :tableData="userAddress"
      >
        <template #opname="slot">
          <el-button
            v-if="slot.data.status == 1"
            type="primary"
            plain
            @click="changeStatus(slot.data.id, 0)"
          >
            启用
          </el-button>
          <el-button
            v-else
            type="primary"
            plain
            @click="changeStatus(slot.data.id, 1)"
          >
            禁用
          </el-button>
        </template>
      </pageTable>
      <template #header>
        <div class="flex-x-between">
          <div>
            <el-button icon="refresh" @click="reset">重置</el-button>
            <el-button type="primary" @click="addAddress()">
              <template #icon><Plus /></template>
              新增地址
            </el-button>
          </div>
        </div>
      </template>
    </el-card>
  </el-col>
  <PageModal
    ref="addressAddRef"
    :modal-config="addressAddConfig"
    @submit-click="done"
  />
</template>

<script setup lang="ts">
import PageModal from "@/components/CURD/PageModal.vue";
import pageTable from "@/components/pageTable/index.vue";
import { IModalConfig } from "@/components/CURD/types";
import AddresstAPI from "@/api/address";
import { inject } from "vue";

const isMobile: any = inject("isMobile");

const addressAddRef = ref();
const tableLabels = ref([
  { prop: "name", label: "发货人姓名" },
  { prop: "address", label: "发货地址" },
  { prop: "phone", label: "发货人电话" },
  { prop: "statusStr", label: "状态" },
  {
    label: "操作",
    type: "opt",
    prop: "opname",
    slot_name: "opname",
    width: 200,
  },
]);

const userAddress: any = ref([]);
const fetchUserAddress = () => {
  AddresstAPI.getUserAddressList().then((res: any) => {
    userAddress.value = res;
    userAddress.value.forEach((item: any) => {
      item.statusStr = item.status == 0 ? "正常 " : "禁用";
      item.address = [
        item.province,
        item.city,
        item.district,
        item.address,
      ].join(" ");
    });
  });
};

const placeOptions = ref([]);

watch(isMobile, () => {
  if (isMobile.value) {
    addressAddConfig.width = "100%";
    addressAddConfig.labelPosition = "top";
    addressAddConfig.labelWidth = "10%";
  } else {
    addressAddConfig.width = "50%";
    addressAddConfig.labelPosition = "right";
    addressAddConfig.labelWidth = "30%";
  }
});

const addressAddConfig: IModalConfig<any> = reactive({
  labelWidth: isMobile.value ? "10%" : "30%",
  width: isMobile.value ? "100%" : "50%",
  labelPosition: isMobile.value ? "top" : "right",
  pageName: "添加地址",
  formItems: [
    {
      type: "input",
      label: "发货人名称",
      prop: "name",
      rules: [
        {
          type: "string",
          required: true,
          message: "请填写发货人名称",
        },
      ],
    },
    {
      type: "input",
      label: "发货人电话",
      prop: "phone",
      rules: [
        {
          type: "string",
          required: true,
          message: "请填写发货人电话",
          max: 11,
        },
      ],
    },
    {
      type: "cascader",
      label: "地址",
      prop: "placeIds",
      rules: [
        {
          required: true,
          message: "请选择地址",
        },
      ],
      props: {
        value: "id",
        label: "cname",
        children: "children",
      },
      options: computed(() => placeOptions.value),
    },
    {
      type: "input",
      label: "详细地址",
      rules: [
        {
          type: "string",
          required: true,
          message: "请填写详细地址",
        },
      ],
      prop: "detailAddress",
    },
  ],
  component: "dialog",
  beforeSubmit: function (data: any) {
    const checkedNode = addressAddRef.value.getCheckedNodes();
    console.log(checkedNode);
    data.address = [...checkedNode[0].pathLabels, data.detailAddress].join(" ");
    data.cityId = data.placeIds[2];
  },
  formAction: function (data: any): Promise<any> {
    return AddresstAPI.addAddress(data);
  },
});

const addAddress = () => {
  if (placeOptions.value.length <= 0) {
    AddresstAPI.getPlaceOptions().then((res: any) => {
      console.log(res);
      placeOptions.value = res;
      console.log(placeOptions.value);
    });
  }

  addressAddRef.value.setModalVisible();
};

const done = () => {
  setTimeout(() => {
    fetchUserAddress();
  }, 200);
};

const reset = () => {
  fetchUserAddress();
};

const changeStatus = (id: any, status: any) => {
  AddresstAPI.setAddressStatus(id, status).then((res: any) => {
    ElMessage.success("操作成功");
    done();
  });
};

onMounted(() => {
  fetchUserAddress();
});
</script>
