<template>
  <!-- v-model:file-list="fileList" -->
  <!-- list-type="picture-card" -->
  <el-upload
    ref="upload"
    :action="props.action"
    :limit="props.limit"
    :data="props.datas"
    :headers="props.headers"
    :on-success="handleAvatarSuccess"
    :on-preview="handlePictureCardPreview"
    :on-remove="handleRemove"
    :disabled="props.disabled"
    :before-upload="beforeAvatarUpload"
    :on-change="handleChange"
    :on-exceed="handleExceed"
    :class="{ imgdis: uploadDisabled }"
    :show-file-list="false"
    :multiple="multiple"
    :auto-upload="false"
  >
    <Draggable
      :list="fileList"
      :animation="100"
      item-key="id"
      class="list-group"
      :force-fallback="true"
      ghost-class="ghost"
    >
      <template #item="{ element }">
        <div v-if="isPdf" class="groupBox" @click.stop>
          <div class="priviewImg" style="z-index: 1000">
            <el-icon :size="20" @click.stop="preview(element.url)">
              <ZoomIn />
            </el-icon>
            <el-icon
              v-if="!props.disabled"
              class="left"
              :size="20"
              @click.stop="detele(element.url)"
            >
              <Delete />
            </el-icon>
          </div>
          <iframe
            :src="element.url"
            style="
              max-width: 548px;
              max-height: 548px;
              opacity: 1;
              z-index: 100;
            "
          />
        </div>
        <div v-else class="groupBox" @click.stop>
          <img :src="element.url" style="height: 100px" />
          <div class="priviewImg">
            <el-icon :size="20" @click.stop="preview(element.url)">
              <ZoomIn />
            </el-icon>
            <el-icon
              v-if="!props.disabled"
              class="left"
              :size="20"
              @click.stop="detele(element.url)"
            >
              <Delete />
            </el-icon>
          </div>
        </div>
      </template>
    </Draggable>
    <video
      v-if="props.viod === true && dialogImageUrl"
      ref="video"
      :src="dialogImageUrl"
      class="avatar video-avatar videoBox"
      style="width: 100%"
      controls
    />
    <div
      v-if="fileList.length < props.limit && props.boxIconStatus"
      class="boxIcon"
      @click="clearList"
    >
      <el-icon>
        <Plus />
      </el-icon>
    </div>
  </el-upload>

  <el-dialog v-model="dialogVisible">
    <div v-if="props.isPdf === true">
      <iframe
        alt="Preview Image"
        :src="dialogImageUrl"
        style="width: 100%; min-height: 500px; opacity: 1; z-index: 100"
      />
    </div>
    <div v-else>
      <img
        v-if="props.viod === false"
        w-full
        :src="dialogImageUrl"
        alt="Preview Image"
      />
      <!-- {{ fileList }} -->
      <video
        v-if="props.viod === true"
        style="width: 50%"
        :src="fileList[0]?.videoUrl"
        controls
      />
    </div>
  </el-dialog>

  <el-image-viewer
    v-if="showImgae"
    :url-list="[previewList]"
    fit="contain"
    :hide-on-click-modal="true"
    @close="showImgae = false"
  />
</template>

<script lang="ts" setup>
import Draggable from "vuedraggable";
import { Plus } from "@element-plus/icons-vue";
import type { UploadProps } from "element-plus";
import { ElMessage } from "element-plus";

// 定义初始化
const props = defineProps({
  fileList: {
    type: Array,
    default: () => [],
  },
  action: {
    type: String,
    required: true,
    default: "",
  },
  limit: {
    type: Number,
    required: true,
    default: 1,
  },
  // 大小限制 单位M
  maxSize: {
    type: Number,
    required: true,
    default: 0,
  },
  boxIconStatus: {
    type: Boolean,
    default: true,
  },
  model: {
    type: String,
    default: "",
  },
  viod: {
    type: Boolean,
    default: false,
  },
  isPdf: {
    type: Boolean,
    default: false,
  },
  datas: {
    type: Object,
    default: () => {},
  },
  headers: {
    type: Object,
    default: () => {},
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  useToken: {
    type: Boolean,
    default: false,
  },
  multiple: {
    type: Boolean,
    default: false,
  },
});

// 子组件注册
const emit = defineEmits([
  "update:fileList",
  "handleRemove",
  "handleAvatarSuccess",
]);
const uploadDisabled = ref(false);
const dialogImageUrl = ref("");
const previewList = ref();
const showImgae = ref(false);
const dialogVisible = ref(false);
const upload = ref(null);

const handleChange: UploadProps["onChange"] = (file, files) => {
  if (file.status !== "ready") return;
  // const list = files.filter(item => item.status === 'success')
  if (files?.length > props.limit - props.fileList?.length) {
    uploadDisabled.value = true;
    return false;
  } else {
    upload.value.submit();
  }
};

const clearList = () => {
  upload.value.clearFiles();
};

const preview = (str: string) => {
  if (props.viod) {
    dialogVisible.value = true;
  } else if (props.isPdf) {
    dialogVisible.value = true;
    dialogImageUrl.value = str;
  } else {
    previewList.value = str;
    showImgae.value = true;
  }
};

const detele = (str: string) => {
  const index = fileList.value.findIndex((item: any) => item.url === str);
  if (index !== -1) {
    fileList.value.splice(index, 1);
    upload.value.clearFiles();
    console.log(fileList.value);
    emit("update:fileList", fileList.value);
  } else {
    upload.value.clearFiles();
    emit("update:fileList", []);
  }
};

// 文件上传成功时的钩子
const handleAvatarSuccess: UploadProps["onSuccess"] = (
  response,
  uploadFile,
  uploadFiles
) => {
  const lisimg = uploadFiles.map((res: any) => {
    console.log("handleAvatarSuccess", res);
    return {
      url: res.response.data.url,
    };
  });
  const arr = lisimg.filter(
    (item: any) => item.url || item.videoUrl || item.uri
  );
  emit("handleAvatarSuccess", response, uploadFile, uploadFiles);
  emit(
    "update:fileList",
    [
      ...new Set(
        fileList?.value
          .concat(arr)
          .filter(Boolean)
          .map((item: any) => JSON.stringify(item))
      ),
    ].map(JSON.parse)
  );
};

// 文件列表移除文件时的钩子
const handleRemove: UploadProps["onRemove"] = (uploadFile, uploadFiles) => {
  uploadDisabled.value = false;
  emit("handleRemove", uploadFile);
};

// 点击点击文件列表中已上传的文件时的钩子
const handlePictureCardPreview: UploadProps["onPreview"] = (
  uploadFile: any
) => {
  dialogImageUrl.value = uploadFile?.videoUrl || uploadFile.url!;
  dialogVisible.value = true;
};

const beforeAvatarUpload: UploadProps["beforeUpload"] = (rawFile) => {
  if (props.maxSize > 0 && rawFile.size / 1024 / 1024 > props.maxSize) {
    ElMessage.error("超出文件大小限制" + props.maxSize + "M");
    return false;
  }
};

const handleExceed: UploadProps["onExceed"] = (files, uploadFiles) => {
  ElMessage.warning(`最大可上传${props.limit}，请重新选择！`);
  upload.value.clearFiles();
};

watchEffect(() => {
  uploadDisabled.value = false;
  if (props.fileList?.length >= props.limit) {
    uploadDisabled.value = true;
  }
  if (props.disabled === true) {
    uploadDisabled.value = true;
  }
});

const fileList = computed({
  get(row: any) {
    return props.fileList || [];
  },
  set(isCheck: any) {
    emit("update:fileList", isCheck);
  },
});
</script>
<style lang="scss" scoped>
.list-group {
  display: flex;

  img {
    width: 146px;
    height: 146px !important;
    object-fit: cover;
    border-radius: 10px;
    cursor: pointer;
    border: 1px solid #dcdfe6;
  }
}

.boxIcon {
  display: flex;
  justify-content: center;
  align-items: center;
  cursor: pointer;
  vertical-align: top;
  background-color: var(--el-fill-color-lighter);
  border: 1px dashed var(--el-border-color-darker);
  border-radius: 6px;
  box-sizing: border-box;
  width: 148px;
  height: 148px;
}

.groupBox {
  position: relative;
  width: 146px;
  height: 146px !important;
  overflow: hidden;
  margin: 0 8px 0px 0;
  .left {
    margin-left: 1em;
  }
}

.priviewImg {
  position: absolute;
  width: 100%;
  height: 100% !important;
  left: 0;
  top: 0;
  cursor: default;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  color: #fff;
  opacity: 0;
  font-size: 20px;
  border-radius: 10px;
  background-color: var(--el-overlay-color-lighter);
  transition: opacity var(--el-transition-duration);
  cursor: pointer;
  &:hover {
    opacity: 1;
  }
}
</style>
