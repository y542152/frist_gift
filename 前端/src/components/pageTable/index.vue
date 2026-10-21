<template>
  <div v-if="operate" class="flex align_center operate">
    <div class="count">已选择 {{ listArr.length }} 项</div>
    <el-row class="mb-4">
      <el-button
        v-for="(item, index) in operateArr"
        :key="index"
        v-hasPerm="item.hasPerm"
        :type="item.type"
        :text="item.text"
        :size="item.size"
        :plain="item.plain"
        @click="btnClick(item)"
      >
        {{ item.name }}
      </el-button>
    </el-row>
  </div>
  <el-table
    ref="tableRef"
    v-loading="props.loading"
    :data="props.tableData"
    style="width: 100%"
    row-key="id"
    :header-row-style="{ backgroundColor: 'grey' }"
    border
    :height="props.height"
    :tree-props="props.treeProps"
    class-name="el-tables"
    element-loading-text="加载中..."
    :element-loading-spinner="svg"
    element-loading-svg-view-box="-10, -10, 50, 50"
    :row-class-name="rowClassName"
    :select-on-indeterminate="true"
    :default-expand-all="props.defaultExpandAll"
    :header-cell-style="{
      textAlign: 'center',
      backgroundColor: props.headerColor,
    }"
    :cell-style="{ textAlign: 'center' }"
    @selection-change="handleSelectionChange"
    @row-click="oncellClick"
    @expand-change="expandChange"
  >
    <!-- 选择 -->
    <el-table-column
      v-if="choice"
      type="selection"
      width="50"
      :selectable="selected"
    />
    <el-table-column v-if="expand" type="expand" width="50" fixed>
      <template #default="scope">
        <slot name="expand" :data="scope.row" :index="scope.$index" />
      </template>
    </el-table-column>
    <!--序号 -->
    <el-table-column
      v-if="serialNo"
      type="index"
      width="80"
      label="序号"
      fixed
    />
    <template v-for="item in props.tableLabel" :key="item.prop">
      <!-- 图片 -->
      <el-table-column
        v-if="item.type === 'img'"
        :key="item.id"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        show-overflow-tooltip
      >
        <template #default="scope">
          <img :src="scope.row[item.prop]" style="width: 100%" />
        </template>
      </el-table-column>
      <!-- 缺货 -->
      <el-table-column
        v-else-if="item.type === 'quhuo'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        show-overflow-tooltip
      >
        <template #default="scope">
          <span v-if="scope.row.sortOutState === 3" class="quehuo">缺货</span>
        </template>
      </el-table-column>
      <!-- 发货数据标红 -->
      <el-table-column
        v-else-if="item.type === 'quhuos'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        show-overflow-tooltip
      >
        <template #default="scope">
          <span
            v-if="
              scope.row.preDeliveryAmount * 1 > scope.row.orderMoney * 1 &&
              item.prop === 'deliveryDifference'
            "
            class="quehuo"
          >
            {{ scope.row.deliveryDifference }}
          </span>
          <span
            v-if="
              scope.row.preDeliveryAmount * 1 > scope.row.orderMoney * 1 &&
              item.prop === 'sortOutCount'
            "
            class="quehuo"
          >
            {{ scope.row.sortOutCount }}
          </span>
          <span
            v-if="
              scope.row.preDeliveryAmount * 1 > scope.row.orderMoney * 1 &&
              item.prop === 'preDeliveryAmount'
            "
            class="quehuo"
          >
            {{ scope.row.preDeliveryAmount }}
          </span>
        </template>
      </el-table-column>

      <!-- 操作 -->
      <el-table-column
        v-else-if="item.type === 'opt'"
        :width="isMobile && !item.width ? '200px' : item.width"
        :prop="item.prop"
        :label="item.label"
        :fixed="item.fixed"
        auto-width
        show-overflow-tooltip
      >
        <template #default="scope">
          <slot
            :name="item.slot_name"
            :data="scope.row"
            :index="scope.$index"
            :prop="item.prop"
            :item="item"
          />
        </template>
      </el-table-column>
      <!-- 时间 -->
      <el-table-column
        v-else-if="item.type === 'time'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        show-overflow-tooltip
      >
        <template #default="scope">
          <span>
            {{
              scope.row[item.prop] !== null
                ? getTime(scope.row[item.prop])
                : "-"
            }}
          </span>
        </template>
      </el-table-column>
      <el-table-column
        v-else-if="item.type === 'times'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        show-overflow-tooltip
      >
        <template #default="scope">
          <span>
            {{
              scope.row[item.prop] !== null
                ? getTimes(scope.row[item.prop])
                : "-"
            }}
          </span>
        </template>
      </el-table-column>
      <!-- datePicker -->
      <el-table-column
        v-else-if="item.type === 'datePicker'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        show-overflow-tooltip
      >
        <template #default="scope">
          <el-date-picker
            v-model="scope.row[item.prop]"
            type="date"
            placeholder="请选择时间"
            value-format="YYYY-MM-DD"
            format="YYYY-MM-DD"
            :disabled="item.disabled || scope.row.disabled"
          />
        </template>
      </el-table-column>
      <el-table-column
        v-else-if="item.type === 'datetime'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        show-overflow-tooltip
      >
        <template #default="scope">
          <el-date-picker
            v-model="scope.row[item.prop]"
            type="datetime"
            placeholder="请选择时间"
            value-format="YYYY-MM-DD HH:mm:ss"
            format="YYYY-MM-DD HH:mm:ss"
            style="width: 170px"
          />
        </template>
      </el-table-column>
      <!-- 可编辑表格 -->
      <el-table-column
        v-else-if="item.type === 'input'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        show-overflow-tooltip
      >
        <template #default="scope">
          <el-input
            v-model="scope.row[item.prop]"
            :type="item.types"
            :disabled="
              item.disabled ||
              scope.row.disabled ||
              (scope.row.isEdit === false ? !scope.row.isEdit : false)
            "
            @blur="blurClick(scope.row, scope.$index, scope)"
          >
            <template v-if="item.baseUnitName" #append>
              /{{ scope.row[item.baseUnitName] }}
            </template>
          </el-input>
        </template>
      </el-table-column>
      <el-table-column
        v-else-if="item.type === 'inputs'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        show-overflow-tooltip
      >
        <template #default="scope">
          <el-input
            v-model="scope.row[item.prop]"
            :type="item.types"
            :disabled="
              scope.row.sortOutState === 2 || scope.row.sortOutState === 3
            "
            @blur="blurClick(scope.row, scope.$index)"
          />
        </template>
      </el-table-column>

      <!-- Switch 开关 -->
      <el-table-column
        v-else-if="item.type === 'switch'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        show-overflow-tooltip
      >
        <template #default="scope">
          <el-switch
            v-model="scope.row[item.prop]"
            :active-value="item.active"
            :inactive-value="item.inactive"
          />
        </template>
      </el-table-column>

      <!-- 表格点击 -->
      <el-table-column
        v-else-if="item.type === 'button'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        show-overflow-tooltip
      >
        <template #default="scope">
          <el-button type="text" @click="onButton(scope)">
            {{ scope.row[item.prop] }}
          </el-button>
        </template>
      </el-table-column>

      <!-- 表格点击 -->
      <el-table-column
        v-else-if="item.type === 'inputNumber'"
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        show-overflow-tooltip
      >
        <template #default="scope">
          <el-input-number
            v-model="scope.row[item.prop]"
            :min="item.min"
            :disabled="item.disabled"
          />
        </template>
      </el-table-column>

      <!-- 普通列表 -->
      <el-table-column
        v-else
        :prop="item.prop"
        :label="item.label"
        :width="isMobile && !item.width ? '200px' : item.width"
        :fixed="item.fixed"
        class-name="operation"
        show-overflow-tooltip
      >
        <!-- show-overflow-tooltip 内容换行 -->
        <template #default="scope">
          <slot
            name="opt"
            :prop="item.prop"
            :data="scope.row"
            :index="scope.$index"
            :item="item"
          >
            <span v-if="item.enum">{{ enumFilter(scope.row, item.type) }}</span>
            <span v-else-if="item.edit" @click?="clickBatch(scope.row)">
              {{ scope.row.pici }}
            </span>
            <span
              v-else-if="item.color"
              class="color"
              @click="clickBatch(scope.row[item.prop])"
            >
              {{ scope.row[item.prop] }}
            </span>
            <span
              v-else-if="item.colorDetal"
              class="color"
              @click="clickBatch(scope.row)"
            >
              {{ scope.row[item.prop] }}
            </span>
            <span v-else-if="item.percentage">{{ scope.row[item.prop] }}%</span>
            <span v-else>{{ scope.row[item.prop] }}</span>
          </slot>
        </template>
      </el-table-column>
    </template>
  </el-table>
  <!-- 分页 -->

  <slot name="summarize" />

  <div
    v-if="props.pagination"
    class="demo-pagination-block"
    :class="{ paginationAmount: props.totalAmount > 0 }"
  >
    <div v-if="props.totalAmount > 0" class="totalAmount">
      总金额：
      <span>￥{{ props.totalAmount }}</span>
      {{ props.currentUnitName }}
    </div>

    <el-pagination
      :current-page="props.currentPage"
      :page-size="props.pageSize"
      :page-sizes="props.pageSizes"
      background
      :layout="
        isMobile
          ? 'total, prev, pager, next'
          : 'total, sizes, prev, pager, next, jumper'
      "
      :total="props.total"
      @size-change="handleSizeChange"
      @current-change="handleCurrentChange"
    />
  </div>
</template>

<script lang="ts" setup>
import { getTime, enumFilter, getTimes } from "@/utils/common";
import { inject } from "vue";

const isMobile = inject("isMobile") as Ref<boolean>;
const listArr: any = ref([]);
const tableRef = ref();
const props = defineProps({
  // 表格标题
  tableLabel: {
    type: Object,
    required: true,
    default: () => {},
  },
  // 表格数据
  tableData: {
    type: Array,
    required: true,
    default: () => [],
  },
  // 是否需要序号
  serialNo: {
    type: Boolean,
    default: false,
  },
  // 是否需要选择框
  choice: {
    type: Boolean,
    default: false,
  },
  // 展开列
  expand: {
    type: Boolean,
    default: false,
  },
  // 是否需要顶部操作栏
  operate: {
    type: Boolean,
    default: false,
  },
  operateArr: {
    type: Object,
    default: () => {},
  },
  // 分页页数
  currentPage: {
    type: Number,
    default: 1,
  },
  // 分页条数
  pageSize: {
    type: Number,
    default: 10,
  },
  // 分页数据总条数
  total: {
    type: Number,
    default: 0,
  },
  // 总金额
  totalAmount: {
    type: Number,
    default: 0,
  },
  currentUnitName: {
    type: String,
    default: "",
  },
  // 是否需要分页
  pagination: {
    type: Boolean,
    default: false,
  },
  // 是否加载
  loading: {
    type: Boolean,
    default: false,
  },
  // 是否需要设置表格高度
  height: {
    type: String || Number,
    default: "auto",
  },
  treeProps: {
    type: Object,
    default: () => {},
  },
  // 每页数量选择
  pageSizes: {
    type: Array,
    default: () => [10, 20, 30, 40, 50],
  },
  disabledRowIds: {
    type: Array,
    default: () => [],
  },
  disabledKey: {
    type: String,
    default: "id",
  },
  defaultExpandAll: {
    type: Boolean,
    default: false,
  },
  headerColor: {
    type: String,
    default: "#e9e9e9",
  },
});

// 注册emit
const emit = defineEmits([
  "oncellClick",
  "onButton",
  "expandChange",
  "handleSelect",
  "handleSize",
  "handleCurrent",
  "blurClick",
  "clickBatch",
]);

// 注册emit
const expandChange = (expandedRows: any, expanded: any) => {
  emit("expandChange", expandedRows, expanded);
};

const btnClick = (item: any) => {
  if (item.click && typeof item.click === "function") {
    item.click();
  } else {
    console.log(item.clickName);
    emit(item.clickName, listArr.value);
  }
};

const handleSelectionChange = (list: any) => {
  listArr.value = list;
  emit("handleSelect", list);
};

const rowClassName = (row: any) => {
  return props.disabledRowIds.indexOf(row.row[props.disabledKey]) === -1
    ? ""
    : "disabled-row";
};

// 禁用表格
// const selected = (row:any, index:any) => {
//   if (row.disabledSelection) {
//     return false
//   }
// }

const onButton = (row: any) => {
  emit("onButton", row);
};
const blurClick = (val: any, index: any, scope?: any) => {
  emit("blurClick", val, index, scope);
};
const clickBatch = (val: any) => {
  emit("clickBatch", val);
};

const handleSizeChange = (val: any) => {
  emit("handleSize", val);
};
const handleCurrentChange = (val: any) => {
  emit("handleCurrent", val);
};

const oncellClick = (row: any, column: any, cell: any) => {
  emit("oncellClick", row, column, cell);
};

const getSelectionRows = () => {
  return tableRef.value.getSelectionRows();
};

//  抛出子组件实例
defineExpose({ tableRef, getSelectionRows });
</script>

<style lang="scss" scoped>
.demo-pagination-block {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  margin-top: 20px;

  .totalAmount {
    font-weight: normal;
    color: var(--el-text-color-regular);
  }
}

.paginationAmount {
  justify-content: space-between;
}

.data_count {
  font-size: 14px;
  color: #606266;
}

.space {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 20px;
}

.quehuo {
  color: red;
}

.count {
  padding-right: 20px;
}

.operate {
  margin-bottom: 10px;
}
</style>
<style>
.disabled-row {
  pointer-events: none;
  opacity: 0.5;
}

.color {
  color: #409eff;
  cursor: pointer;
}
</style>
