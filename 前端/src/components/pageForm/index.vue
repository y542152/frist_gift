<template>
  <el-form
    ref="ruleForm"
    v-loading="loading"
    :inline="props.inline"
    :model="field"
    :rules="props.rules"
    :disabled="props.disabled"
    :readonly="props.readonly"
    :label-width="props.labelWidth"
    :label-position="props.labelPosition"
    :scroll-to-error="true"
  >
    <template v-for="item in props.data">
      <!-- 复选 -->
      <el-form-item
        v-if="item.type === 'checkbox'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-checkbox-group
          v-model="field[item.prop]"
          :placeholder="item.placeholder"
        >
          <el-checkbox
            v-for="(option, index) in item.options"
            :key="index"
            :label="option.label"
            :name="option.value"
            :disabled="option.disabled"
            @change="(val) => handleCheckedCitiesChange(val, option.label)"
          />
        </el-checkbox-group>
      </el-form-item>
      <!-- 用于筛选 -->
      <el-form-item
        v-if="item.type === 'screen'"
        :key="item.prop"
        :prop="item.prop"
      >
        <el-checkbox v-model="field[item.prop]" :label="item.label" />
      </el-form-item>
      <!-- switch -->
      <el-form-item
        v-if="item.type === 'switch'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-switch
          v-model="field[item.prop]"
          :active-value="item.ASC"
          :inactive-value="item.DESC"
        />
      </el-form-item>
      <!-- switch文字描述 -->
      <el-form-item
        v-if="item.type === 'swactiv'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-switch
          v-model="field[item.prop]"
          class="mb-2"
          style="--el-switch-on-color: #13ce66; --el-switch-off-color: #ff4949"
          :active-text="item.activtext"
          :inactive-text="item.inactivetext"
          :active-value="item.ASC"
          :inactive-value="item.DESC"
        />
      </el-form-item>
      <!-- 时间选择 -->
      <el-form-item
        v-if="item.type === 'time'"
        :key="item.prop"
        :label="item.label"
        label-position="top"
        :prop="item.prop"
        :style="`width: ${item.width};`"
      >
        <el-date-picker
          v-model="field[item.prop]"
          :type="item.timeType || 'datetimerange'"
          range-separator="-"
          :value-format="item.valueFormat || 'YYYY-MM-DD'"
          :format="item.formatItme || 'YYYY-MM-DD'"
          :placeholder="item.placeholder"
          start-placeholder="开始时间"
          end-placeholder="结束时间"
          unlink-panels
        />
      </el-form-item>
      <el-form-item
        v-if="item.type === 'times'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
        label-position="top"
        :style="`width: ${item.width};`"
      >
        <el-date-picker
          v-model="field[item.prop]"
          :type="item.timeType || 'datetimerange'"
          range-separator="-"
          :value-format="item.valueFormat || 'YYYY-MM-DD HH:mm:ss'"
          :format="item.formatItme || 'YYYY-MM-DD HH:mm:ss'"
          :placeholder="item.placeholder"
          start-placeholder="开始时间"
          end-placeholder="结束时间"
        />
      </el-form-item>
      <el-form-item
        v-if="item.type === 'timefine'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
        label-position="top"
      >
        <el-date-picker
          v-model="field[item.prop]"
          :type="item.timeType || 'datetimerange'"
          range-separator="-"
          :value-format="item.valueFormat || 'YYYY-MM-DD HH:mm:ss'"
          :format="item.formatItme || 'YYYY-MM-DD HH:mm:ss'"
          :placeholder="item.placeholder"
          start-placeholder="开始时间"
          end-placeholder="结束时间"
        />
      </el-form-item>
      <el-form-item
        v-if="item.type === 'timeChange'"
        :key="item.prop"
        :label="item.label"
        label-position="top"
        :prop="item.prop"
      >
        <el-date-picker
          v-model="field[item.prop]"
          :type="item.timeType || 'datetimerange'"
          range-separator="-"
          :value-format="item.valueFormat || 'YYYY-MM-DD'"
          :format="item.formatItme || 'YYYY-MM-DD'"
          :placeholder="item.placeholder"
          start-placeholder="开始时间"
          end-placeholder="结束时间"
          unlink-panels
          @change="(val) => handelSelect(val, item.prop)"
        />
      </el-form-item>
      <!-- 单选框 -->
      <el-form-item
        v-if="item.type === 'radio'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-radio-group
          v-if="item.change"
          v-model="field[item.prop]"
          :disabled="item.disabled"
          :readonly="item.readonly"
          @change="(val) => handelChange(val, item.prop)"
        >
          <el-radio
            v-for="(option, index) in item.options"
            :key="index"
            :label="option.value"
          >
            {{ option.label }}
          </el-radio>
        </el-radio-group>
        <el-radio-group v-else v-model="field[item.prop]">
          <el-radio
            v-for="(option, index) in item.options"
            :key="index"
            :label="option.value"
          >
            {{ option.label }}
          </el-radio>
        </el-radio-group>
      </el-form-item>
      <!-- select选择器 -->
      <el-form-item
        v-if="item.type === 'select'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-select
          v-if="item.change"
          v-model="field[item.prop]"
          v-selePrinting:[item.popperClass]="selePrinting"
          :popper-class="item.popperClass"
          :placeholder="item.placeholder"
          :multiple="item.multiple"
          :filterable="item.filterable"
          :loading="item.loading"
          :disabled="item.disabled"
          :readonly="item.readonly"
          :clearable="item.clearable ? false : true"
          :input-style="`width: ${item.width}px;`"
          :style="`width: ${item.width};`"
          @change="(val) => handelSelect(val, item.prop)"
          @keyup.enter="getDataByKeywords(val, item.prop)"
        >
          <el-option
            v-for="(option, index) in item.options"
            :key="index"
            :label="option.label"
            :value="option.value"
          />
        </el-select>

        <el-select
          v-else-if="item.filterable"
          v-model="field[item.prop]"
          v-selePrinting:[item.popperClass]="selePrinting"
          :input-style="`width: ${item.width}px;`"
          :popper-class="item.popperClass"
          :placeholder="item.placeholder"
          :multiple="item.multiple"
          :filterable="item.filterable"
          :remote="item.remote"
          :remote-method="
            (val: any) =>
              typeof item.onRemote === 'function'
                ? item.onRemote(val, item.prop)
                : remoteMethod(val, item.prop)
          "
          clearable
          :style="`width: ${item.width};`"
          @change="(val) => handelSelect(val, item.prop)"
          @keyup.enter="getDataByKeywords(val, item.prop)"
        >
          <el-option
            v-for="(option, index) in item.options"
            :key="index"
            :label="option.label"
            :value="option.value"
          />
        </el-select>

        <el-select
          v-else
          v-model="field[item.prop]"
          v-selePrinting:[item.popperClass]="selePrinting"
          :style="`width: ${item.width};`"
          :popper-class="item.popperClass"
          :placeholder="item.placeholder"
          :multiple="item.multiple"
          :disabled="item.disabled"
          :readonly="item.readonly"
          clearable
        >
          <el-option
            v-for="(option, index) in item.options"
            :key="index"
            :label="option.label"
            :value="option.value"
          />
        </el-select>
      </el-form-item>
      <!-- 联及选择器 -->
      <el-form-item
        v-if="item.type === 'cascader'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-cascader
          ref="cascader"
          v-model="field[item.prop]"
          :options="item.options"
          :props="item.props"
          :placeholder="item.placeholder"
          clearable
          collapse-tags
          collapse-tags-tooltip
          @change="onCas"
        />
      </el-form-item>
      <!-- 动态联级选择器 -->
      <el-form-item
        v-if="item.type === 'trendsCascader'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-cascader
          ref="refSubCat"
          v-model="field[item.prop]"
          :options="item.options"
          :props="props.cascaderProps"
          :placeholder="item.placeholder"
          clearable
          collapse-tags
          collapse-tags-tooltip
          @change="handleCascader"
        />
      </el-form-item>
      <!-- 树状选择器 -->
      <el-form-item
        v-if="item.type === 'treeSelect'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-tree-select
          v-model="field[item.prop]"
          :data="item.data"
          :render-after-expand="false"
          show-checkbox
        />
      </el-form-item>
      <!-- 图片文本框 -->
      <el-form-item
        v-if="item.type === 'upload'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <pageUpload
          v-model:foo="field[item.prop]"
          :action="item.action"
          :limit="item.limit"
          :model="item.model"
          :datas="item.datas"
          :list-type="item.listType"
          :image-url="field[item.prop]"
          :disabled="item.disabled"
        />
      </el-form-item>

      <el-form-item
        v-if="item.type === 'uploadImg'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <uploadImage
          v-model:file-list="field[item.prop]"
          :action="item.action"
          :limit="item.limit"
          :datas="item.datas"
          :headers="item.headers"
          :disabled="item.disabled"
          :viod="item.viod"
          :showUploadBtn="item.showUploadBtn"
          :max-size="item.maxSize"
          :multiple="item.multiple"
          :accept="item.accept"
        />
        <template v-if="item.notice">
          <br />
          <p style="width: 100%">
            {{ item.notice }}
          </p>
        </template>
      </el-form-item>

      <!-- 普通文本框 -->
      <el-form-item
        v-if="item.type === 'input'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-input
          v-model.trim="field[item.prop]"
          :placeholder="item.placeholder"
          :style="`width: ${item.width};`"
          suffix-icon="xxxx"
          :maxlength="item.max"
          :disabled="item.disabled"
          :readonly="item.readonly"
          :show-password="item.password"
          @keyup.enter="getDataByKeywords(val, item.prop)"
        >
          <template v-if="item.append" #append>
            <span @click="item.append.click()">{{ item.append.text() }}</span>
          </template>
        </el-input>
        <p v-if="item.notice" class="notice">
          {{ item.notice }}
        </p>
      </el-form-item>
      <el-form-item
        v-if="item.type === 'input-number'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-input-number
          v-model.trim="field[item.prop]"
          :placeholder="item.placeholder"
          :style="`width: ${item.width};`"
          suffix-icon="xxxx"
          :maxlength="item.max"
          :disabled="item.disabled"
          :readonly="item.readonly"
          :show-password="item.password"
          @keyup.enter="getDataByKeywords(val, item.prop)"
        >
          <template v-if="item.append" #append>
            <span @click="item.append.click()">{{ item.append.text() }}</span>
          </template>
        </el-input-number>
        <p v-if="item.notice" class="notice">
          {{ item.notice }}
        </p>
      </el-form-item>
      <el-form-item
        v-if="item.type === 'plain'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        {{ field[item.prop] }}
      </el-form-item>
      <!-- 富文本 -->
      <el-form-item
        v-if="item.type === 'textarea'"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <el-input
          v-model="field[item.prop]"
          :placeholder="item.placeholder"
          :style="`width: ${item.width};`"
          suffix-icon="xxxx"
          :disabled="item.disabled"
          :readonly="item.readonly"
          type="textarea"
          :show-word-limit="item.limit"
          :maxlength="item.maxlength"
          :autosize="item.autosize"
        />
      </el-form-item>

      <el-form-item
        v-if="item.slotName"
        :key="item.prop"
        :label="item.label"
        :prop="item.prop"
      >
        <template #default>
          <slot
            :name="item.slotName"
            :data="field"
            :prop="item.prop"
            :item="item"
          />
        </template>
      </el-form-item>
    </template>
  </el-form>
</template>

<script lang="ts" setup>
import { debounce } from "@/utils/common";
import uploadImage from "@/components/Upload/ImageUpload.vue";
const refSubCat: any = ref();

const props = defineProps({
  // 表单数据
  data: {
    type: Object,
    required: true,
    default: () => {},
  },
  viod: {
    type: Boolean,
    default: false,
  },
  // 表单v-model的值
  field: {
    type: Object,
    required: true,
    default: () => ({}),
  },
  // 行内表单-横向
  inline: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  readonly: {
    type: Boolean,
    default: false,
  },
  // 表单验证
  rules: {
    type: Object,
    default: () => {},
  },
  // 设置表单el-form-item宽度
  labelWidth: {
    type: String,
    default: "",
  },

  labelPosition: {
    type: String,
    default: "right",
  },
  loading: {
    type: Boolean,
    default: false,
  },
  // 联级动态
  cascaderProps: {
    type: Object,
    default: () => {},
  },
});

const { field } = toRefs(props);
const ruleForm = ref();
const cascader = ref();

// 注册emit
const emit = defineEmits([
  "onCas",
  "onEnter",
  "selePrinting",
  "update:fileList",
  "handleCheckedCitiesChange",
  "handelChange",
  "handelSelect",
  "update:foo",
  "remoteMethod",
  "handleCascader",
  "getDataByKeywords",
]);

// 表单单选
const handelChange = (val?: any, type?: any) => {
  emit("handelChange", val, type);
};

// 联级返回名字
const handleCascader = () => {
  emit("handleCascader", refSubCat.value[0].getCheckedNodes()[0].pathLabels);
};

// 表单select监听
const handelSelect = (val?: any, type?: any) => {
  emit("handelSelect", val, type);
};

// 表单select监听
const getDataByKeywords = (val?: any, type?: any) => {
  emit("getDataByKeywords", val, type);
};

// 表单远程搜索
const remoteMethod = (val: any, type?: any) => {
  emit("remoteMethod", val, type);
};
// 选择
const handleCheckedCitiesChange = (val: any, type?: any) => {
  emit("handleCheckedCitiesChange", val, type);
};
// 表单验证
const validateForm = () => {
  return ruleForm.value.validate((valid: boolean) => {
    return valid;
  });
};

watchEffect(() => {
  document.onkeydown = function (e) {
    // 兼容FF和IE和Opera
    const theEvent = window.event || e;
    const code = theEvent.keyCode || theEvent.which || theEvent.charCode;
    if (code === 13) {
      onEnter();
    }
  };
});
const onEnter = debounce(() => {
  emit("onEnter");
});

// 表单重置
const resetForm = () => {
  console.log(ruleForm.value, "resetForm");
  ruleForm.value.resetFields();
};

const selePrinting = debounce(() => {
  emit("selePrinting");
});

const onCas = (row: any) => {
  const nodesInfo = cascader.value[0]?.getCheckedNodes();
  emit("onCas", row, nodesInfo);
};
defineExpose({ resetForm, validateForm, ruleForm: ruleForm.value });
</script>

<style scoped>
.notice {
  width: 100%;
  color: #838383;
}
</style>
