<template>
  <el-col>
    <el-card shadow="never" class="table-container">
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
      >
        <template #opname="slot">
          <el-col :span="24" style="z-index: 101; position: relative">
            <el-button text type="primary" @click="showSetting(slot.data)">
              <img width="16" src="../../assets/images/set2.gif" />
            </el-button>
            <el-button text type="primary" @click="showCoinModify(slot.data)">
              <img width="16" src="../../assets/images/pic10.gif" />
            </el-button>
            <el-button
              text
              type="primary"
              @click="showPasswordModify(slot.data)"
            >
              <img width="16" src="../../assets/images/mainmenu.png" />
            </el-button>

            <el-button
              v-if="slot.data.is_delete == 0"
              text
              type="primary"
              @click="handleDelete(slot.data.uid)"
            >
              <img width="16" src="../../assets/images/pic12.gif" />
            </el-button>
          </el-col>
        </template>
      </pageTable>
      <template #header>
        <PageSearch
          ref="searchRef"
          :search-config="searchConfig"
          @query-click="query"
          @reset-click="reset"
        />

        <el-button type="primary" @click="create">创建新用户</el-button>
      </template>
    </el-card>
  </el-col>

  <PageModal
    ref="updateRef"
    :modal-config="updateInfoConfig"
    @submitClick="done"
  >
    <template #uid="slot">
      {{ slot.formData.uid }}
    </template>
    <template #account="slot">
      {{ slot.formData.account }}
    </template>
    <template #qq="slot">
      {{ slot.formData.qq }}
    </template>
    <template #qqemail="slot">
      {{ slot.formData.qqemail }}
    </template>
  </PageModal>

  <PageModal
    ref="modifyCoinConfigRef"
    :modal-config="modifyCoinConfig"
    @submitClick="done"
  >
    <template #currentCoin="slot">
      {{ slot.formData.currentCoin }}
    </template>
  </PageModal>

  <PageModal
    ref="modifyPasswordConfigRef"
    :modal-config="modifyPasswordConfig"
    @submitClick="done"
  >
    <template #currentCoin="slot">
      {{ slot.formData.currentCoin }}
    </template>
  </PageModal>

  <PageModal
    ref="createUserInfoConfigRef"
    :modal-config="createUserInfoConfig"
    @submitClick="done"
  >
    <template #uid="slot">
      {{ slot.formData.uid }}
    </template>
    <template #account="slot">
      {{ slot.formData.account }}
    </template>
    <template #qq="slot">
      {{ slot.formData.qq }}
    </template>
    <template #qqemail="slot">
      {{ slot.formData.qqemail }}
    </template>
  </PageModal>
</template>

<script setup lang="ts">
import pageTable from "@/components/pageTable/index.vue";
import { IModalConfig } from "@/components/CURD/types";
import UserAPI from "@/api/user";
import PageModal from "@/components/CURD/PageModal.vue";
import { ISearchConfig } from "@/components/CURD/types";
import PageSearch from "@/components/CURD/PageSearch.vue";
import { inject } from "vue";

const isMobile = inject("isMobile") as Ref<boolean>;

const tableLabels = ref([
  { prop: "uid", label: "ID", width: 80 },
  { prop: "account", label: "手机", width: 180 },
  { prop: "qq", label: "QQ", width: 180 },
  { prop: "profit_rate", label: "利润金额", width: 150 },
  { prop: "invite_uid", label: "推荐人ID", width: 100 },
  { prop: "coin", label: "金币余额", width: 150 },
  { prop: "point", label: "积分余额", width: 150 },
  { prop: "is_disabledStr", label: "是否禁用", width: 100 },
  { prop: "is_deleteStr", label: "是否删除", width: 100 },
  { prop: "login_count", label: "登录次数", width: 120 },
  { prop: "created_at", label: "注册时间", width: 180 },
  {
    label: "操作",
    type: "opt",
    prop: "opname",
    slot_name: "opname",
    width: 280,
    fixed: computed(() => {
      return isMobile.value ? false : "right";
    }),
  },
]);

const listData: any = ref([]);
const pageParams: any = reactive({
  page: 1,
  pageSize: 10,
  total: 0,
});

const fetchList = (params: any = {}) => {
  const param: any = {
    page: pageParams.page,
    pageSize: pageParams.pageSize,
    ...params,
  };

  if (!(param.status >= 1 && param.status <= 4)) {
    delete param.status;
  }

  UserAPI.fetchUserList(param).then((res: any) => {
    listData.value = res.items;
    pageParams.total = res.total;

    listData.value.forEach((item: any) => {
      item.is_xinfengStr = item.is_xinfeng == 1 ? "是" : "否";
      item.profit_rateStr = item.profit_rate > 0 ? item.profit_rate + "%" : 0;
      item.reward_rateStr = item.reward_rate > 0 ? item.reward_rate + "%" : 0;
    });
  });
};

const searchRef = ref();

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

const updateRef = ref();
const showSetting = (item: any) => {
  updateRef.value.setFormData({
    account: item.account,
    qq: item.qq,
    is_xinfeng: item.is_xinfeng,
    reward_rate: item.reward_rate,
    profit_rate: item.profit_rate,
    trust_level: item.trust_level,
    invite_uid: item.invite_uid,
    qqemail: item.qqemail,
    uid: item.uid,
  });
  updateRef.value.setModalVisible();
};

const showCoinModify = (item: any) => {
  modifyCoinConfigRef.value.setFormData({
    currentCoin: item.coin,
    coin: 0,
    type: "1",
    uid: item.uid,
  });
  modifyCoinConfigRef.value.setModalVisible();
};

const updateInfoConfig: IModalConfig<any> = reactive({
  labelWidth: isMobile.value ? "10%" : "30%",
  width: isMobile.value ? "100%" : "50%",
  labelPosition: isMobile.value ? "top" : "right",
  pageName: "编辑用户信息",
  formItems: [
    {
      label: "uid",
      prop: "uid",
      hidden: true,
    },
    {
      type: "custom",
      label: "账户",
      prop: "account",
    },
    {
      type: "custom",
      label: "QQ",
      prop: "qq",
    },
    {
      type: "custom",
      label: "QQ邮箱",
      prop: "qqemail",
    },
    {
      type: "input",
      label: "利润金额",
      prop: "profit_rate",
      rules: [
        {
          required: true,
          message: "请填写利润金额",
        },
      ],
    },
    {
      type: "input-number",
      label: "上线佣金比例",
      prop: "reward_rate",
      rules: [
        {
          required: true,
          message: "请填写推广抽佣比例，数值应在0到100之间",
        },
      ],
    },
    {
      type: "radio",
      label: "是否可购买信封",
      prop: "is_xinfeng",
      options: [
        {
          label: "否",
          value: 0,
        },
        {
          label: "是",
          value: 1,
        },
      ],
      rules: [
        {
          type: "number",
          required: true,
        },
      ],
    },
    {
      type: "radio",
      label: "信任等级",
      prop: "trust_level",
      initialValue: 0,
      options: [
        {
          label: "非信任",
          value: 0,
        },
        {
          label: "一般信任",
          value: 1,
        },
        {
          label: "超级信任",
          value: 2,
        },
      ],
      rules: [
        {
          type: "number",
          required: true,
        },
      ],
    },
    {
      type: "radio",
      label: "禁用账户",
      prop: "is_disabled",
      initialValue: 0,
      options: [
        {
          label: "正常",
          value: 0,
        },
        {
          label: "禁用",
          value: 1,
        },
      ],
      rules: [
        {
          type: "number",
          required: true,
        },
      ],
    },
    {
      type: "input",
      label: "推荐人ID",
      prop: "invite_uid",
    },
  ],
  component: "dialog",
  beforeSubmit: function (data: any) {
    delete data.nickname;
  },
  formAction: function (data: any): Promise<any> {
    return UserAPI.updateUserInfo(data);
  },
});

watch(isMobile, () => {
  if (isMobile.value) {
    updateInfoConfig.width = "100%";
    updateInfoConfig.labelPosition = "top";
    updateInfoConfig.labelWidth = "10%";

    createUserInfoConfig.width = "100%";
    createUserInfoConfig.labelPosition = "top";
    createUserInfoConfig.labelWidth = "10%";

    modifyCoinConfig.width = "100%";
    modifyCoinConfig.labelPosition = "top";
    modifyCoinConfig.labelWidth = "10%";

    modifyPasswordConfig.width = "100%";
    modifyPasswordConfig.labelPosition = "top";
    modifyPasswordConfig.labelWidth = "10%";
  } else {
    updateInfoConfig.width = "50%";
    updateInfoConfig.labelPosition = "right";
    updateInfoConfig.labelWidth = "30%";

    createUserInfoConfig.width = "50%";
    createUserInfoConfig.labelPosition = "right";
    createUserInfoConfig.labelWidth = "30%";

    modifyCoinConfig.width = "50%";
    modifyCoinConfig.labelPosition = "right";
    modifyCoinConfig.labelWidth = "30%";

    modifyPasswordConfig.width = "50%";
    modifyPasswordConfig.labelPosition = "right";
    modifyPasswordConfig.labelWidth = "30%";
  }
});

const modifyCoinConfigRef = ref();
const modifyCoinConfig: IModalConfig<any> = reactive({
  labelWidth: isMobile.value ? "10%" : "30%",
  width: isMobile.value ? "100%" : "50%",
  labelPosition: isMobile.value ? "top" : "right",
  pageName: "加减用户余额",
  formItems: [
    {
      label: "uid",
      prop: "uid",
      hidden: true,
    },
    {
      type: "custom",
      label: "当前金币余额",
      prop: "currentCoin",
    },
    {
      type: "radio",
      label: "操作",
      prop: "type",
      options: [
        {
          label: "增加",
          value: "1",
        },
        {
          label: "减少",
          value: "2",
        },
      ],
    },
    {
      type: "input",
      label: "操作金额",
      prop: "coin",
    },
  ],
  component: "dialog",
  formAction: function (data: any): Promise<any> {
    return UserAPI.updateUserCoin(data);
  },
});

const modifyPasswordConfigRef = ref();
const modifyPasswordConfig: IModalConfig = {
  labelWidth: isMobile.value ? "10%" : "30%",
  width: isMobile.value ? "100%" : "50%",
  labelPosition: isMobile.value ? "top" : "right",
  pageName: "修改密码",
  formAction: (data) => {
    return UserAPI.updatePassword(data);
  },
  formItems: [
    {
      prop: "uid",
      label: "用户ID",
      type: "input",
      attrs: {
        disabled: true,
      },
      initialValue: "", // 初始化值
    },
    {
      prop: "password",
      label: "新密码",
      type: "input",
      attrs: {
        type: "password",
        placeholder: "请输入新密码",
      },
      rules: [
        { required: true, message: "密码不能为空", trigger: "blur" },
        { min: 6, message: "密码至少6位", trigger: "blur" },
      ],
    },
  ],
};
const showPasswordModify = (item: any) => {
  modifyPasswordConfigRef.value.setFormData({
    uid: item.uid,
  });
  modifyPasswordConfigRef.value.setModalVisible();
};

const searchConfig: ISearchConfig = {
  pageName: "搜索",
  formItems: [
    {
      type: "input",
      label: "用户搜索手机号/QQ",
      prop: "searchWord",
      width: "350px",
      placeholder: "请输入用户手机号/QQ",
    },
    {
      type: "input",
      label: "用户搜索ID",
      prop: "uid",
      width: "250px",
      placeholder: "请输入用户ID",
    },
    {
      type: "select",
      label: "是否删除",
      prop: "isDelete",
      width: "200px",
      initialValue: null,
      options: [
        {
          label: "全部",
          value: null,
        },
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
      type: "select",
      label: "是否禁用",
      prop: "is_disabled",
      width: "200px",
      initialValue: null,
      options: [
        {
          label: "全部",
          value: null,
        },
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
  ],
};

const reset = () => {
  pageParams.page = 1;
  fetchList(searchRef.value.getQueryParams());
};

const done = () => {
  fetchList(searchRef.value.getQueryParams());
};

const loading = ref(false);
function handleDelete(id: number) {
  ElMessageBox.confirm("确认删除此用户?", "警告", {
    confirmButtonText: "确定",
    cancelButtonText: "取消",
    type: "warning",
  }).then(
    () => {
      loading.value = true;
      UserAPI.deleteUser(id)
        .then(() => {
          ElMessage.success("删除成功");
          reset();
        })
        .finally(() => (loading.value = false));
    },
    () => {}
  );
}

const createUserInfoConfigRef = ref();
const createUserInfoConfig: IModalConfig<any> = reactive({
  labelWidth: isMobile.value ? "10%" : "30%",
  width: isMobile.value ? "100%" : "50%",
  labelPosition: isMobile.value ? "top" : "right",
  pageName: "创建新用户",
  formItems: [
    {
      type: "input",
      label: "手机",
      prop: "account",
      rules: [
        {
          required: true,
          message: "请填写手机",
        },
      ],
    },
    {
      type: "input",
      label: "QQ",
      prop: "qq",
      rules: [
        {
          required: true,
          message: "请填写QQ",
        },
      ],
    },
    {
      type: "input",
      label: "密码",
      prop: "password",
      placeholder: "请输入初始密码",
      rules: [
        {
          required: true,
          message: "请输入初始密码",
        },
      ],
    },
    {
      type: "input",
      label: "初始金币余额",
      prop: "coin",
      rules: [
        {
          required: true,
          message: "请填写金币余额",
        },
      ],
    },
  ],
  component: "dialog",
  formAction: function (data: any): Promise<any> {
    return UserAPI.createUser(data);
  },
});

const create = () => {
  createUserInfoConfigRef.value.setModalVisible();
};

onMounted(() => {
  fetchList(searchRef.value.getQueryParams());
});
</script>
