<template>
  <div class="main-im">
    <div v-if="showQQ" id="open_im" class="open-im" @click="show">&nbsp;</div>
    <div v-else id="im_main" class="im_main">
      <div id="close_im" class="close-im" @click="show">
        <a href="javascript:void(0);" title="点击关闭">&nbsp;</a>
      </div>
      <div class="im-title">在线咨询</div>
      <div class="im-tel im-tb">
        <div>QQ客服</div>
        <div class="tel-num">
          <a
            target="_blank"
            :href="
              'http://wpa.qq.com/msgrd?v=3&amp;uin=' +
              setting.KF_QQ +
              '&amp;site=qq&amp;menu=yes'
            "
          >
            <img
              border="0"
              :src="'http://wpa.qq.com/pa?p=2:' + setting.KF_QQ + ':51'"
              alt="QQ客服"
              title="QQ客服"
            />
          </a>
        </div>
      </div>
      <div class="im-tel im-tb" />
      <div class="im-tb">
        <img class="weixing-ma" :src="setting.KF_WECHAT_QR" />
        <div class="weixing-txt">微信客服</div>
      </div>
    </div>
  </div>
</template>
<script lang="ts" setup>
import UserAPI from "@/api/user";

const showQQ = ref(true);
const show = () => {
  showQQ.value = !showQQ.value;
};
const setting = reactive({
  KF_QQ: "",
  KF_WECHAT_QR: "",
});
onMounted(() => {
  UserAPI.getUserSettingInfo().then((res: any) => {
    setting.KF_QQ = res.KF_QQ;
    setting.KF_WECHAT_QR = res.KF_WECHAT_QR;
  });
});
</script>
<style scoped>
/* 主要css样式*/
.main-im {
  position: fixed;
  right: 10px;
  top: 40%;
  z-index: 100;
  height: 272px;
}
.main-im .im_main {
  background: #f9fafb;
  border: 1px solid #dddddd;
  border-radius: 10px;
  background: #f9fafb;
}
.main-im .im_main .im-title {
  color: #0484cd;
  text-align: center;
  width: 109px;
  height: auto;
  padding-bottom: 5px;
  font-weight: bold;
  font-size: 14px;
  margin-top: 6px;
}
.main-im .im_main .im-tb {
  border-top: 1px solid #dddddd;
}
.main-im .im_main .im-tel {
  color: #000000;
  text-align: center;
  width: 109px;
  height: auto;
  padding-bottom: 10px;
}
.main-im .im_main .im-tel div {
  font-weight: bold;
  font-size: 12px;
  margin-top: 6px;
}
.main-im .im_main .im-tel .tel-num {
  font-family: Arial;
  font-weight: bold;
  color: #e66d15;
}
.main-im .im_main .weixing-ma {
  width: 100px;
  height: 100px;
  padding: 5px;
}
.main-im .im_main .weixing-txt {
  width: 100px;
  margin: 0 auto;
  text-align: center;
}

.main-im .close-im {
  position: absolute;
  right: 10px;
  top: -12px;
  z-index: 100;
  width: 24px;
  height: 24px;
}
.main-im .close-im a {
  display: block;
  width: 24px;
  height: 24px;
  background: url("@/assets/images/close_im.png")
    no-repeat left top;
}
.main-im .close-im a:hover {
  text-decoration: none;
}
.main-im .open-im {
  cursor: pointer;
  width: 40px;
  height: 133px;
  background: url("@/assets/images/open_im.png")
    no-repeat left top;
}
</style>
