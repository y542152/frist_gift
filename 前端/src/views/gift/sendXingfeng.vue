<template>
  <el-col>
    <el-card shadow="never" class="table-container">
      <template v-if="userInfo.NOTICE1" #header>
        <el-alert title="Info alert" type="info">
          <template #title>
            <p v-html="userInfo.NOTICE1" />
          </template>
        </el-alert>
      </template>

      <el-form :model="modifyDialog.currentDetail" :label-width="'120px'">
        <el-col :span="24" class="box">
          <p class="title">第一步：设置你的发货人信息</p>
          <el-form-item
            label="发货地址"
            :label-position="isMobile ? 'top' : 'left'"
          >
            <el-col :span="24">
              <el-row>
                <el-col
                  :xs="24"
                  :lg="10"
                  style="margin-bottom: 10px; margin-right: 5px"
                >
                  <el-select v-model="modifyDialog.currentDetail.sendAddress">
                    <el-option
                      v-for="(option, index) in userAddress"
                      :key="index"
                      :label="
                        option.name +
                        ' ' +
                        option.phone +
                        '：' +
                        [
                          option.province,
                          option.city,
                          option.district,
                          option.address,
                        ].join(' ')
                      "
                      :value="option.id"
                    />
                  </el-select>
                </el-col>
                <el-button type="success" @click="addAddress">
                  添加地址
                </el-button>
              </el-row>
            </el-col>
          </el-form-item>
        </el-col>
        <el-col :span="24" class="box">
          <p class="title">第二步：选择平台、快递、仓库和礼品</p>
          <el-form-item
            label="平台"
            :label-position="isMobile ? 'top' : 'left'"
          >
            <el-row>
              <el-button
                v-for="(item, key) in currentPlatfroms"
                :key="key"
                style="margin: 0 5px 5px 0"
                :type="
                  modifyDialog.currentDetail.platform == item ? 'success' : ''
                "
                @click="chosePlatform(item)"
              >
                {{ key }}
              </el-button>
            </el-row>
          </el-form-item>

          <el-form-item
            label="快递公司"
            :label-position="isMobile ? 'top' : 'left'"
          >
            <el-row style="width: 100%" :gutter="20">
              <el-col :xs="24" :lg="10">
                <el-select
                  v-model="modifyDialog.currentDetail.expressCompany"
                  placeholder="请选择快递公司"
                  @change="(val: any) => handelSelect(val, 'expressCompany')"
                >
                  <el-option
                    v-for="(option, index) in currentExpressCompanies"
                    :key="index"
                    :label="option.label"
                    :value="option.value"
                  />
                </el-select>
              </el-col>
            </el-row>
          </el-form-item>

          <el-form-item
            label="仓库列表"
            :label-position="isMobile ? 'top' : 'left'"
          >
            <el-row style="width: 100%" :gutter="20">
              <el-col
                :xs="24"
                :lg="10"
                style="margin-bottom: 10px; margin-right: 5px"
              >
                <el-select
                  v-model="modifyDialog.currentDetail.storage"
                  placeholder="请选择仓库"
                  @change="(val: any) => handelSelect(val, 'storage')"
                >
                  <el-option
                    v-for="(option, index) in currentExpressCompaniesStorages"
                    :key="index"
                    :label="option.label"
                    :value="option.value"
                  />
                </el-select>
              </el-col>
              <el-col :span="5">
                <el-button
                  v-if="currentGoods.goods_id > 0"
                  type="success"
                  @click="setDefault"
                >
                  选中商品设为默认
                </el-button>
              </el-col>
            </el-row>
          </el-form-item>

          <el-form-item
            v-if="Object.keys(currentExpressInfo).length > 0"
            :label-position="isMobile ? 'top' : 'left'"
            label="快递价格"
          >
            {{ currentExpressInfo.salePrice || 0 }} 元
          </el-form-item>

          <el-form-item
            label="商品列表"
            :label-position="isMobile ? 'top' : 'left'"
          >
            <el-col :span="24">
              <el-row
                v-if="currentExpressCompaniesStoragesGoods?.length > 0"
                class="item"
              >
                <el-col
                  v-for="(item, key) in currentExpressCompaniesStoragesGoods"
                  :key="key"
                  :xs="12"
                  :md="6"
                  :lg="4"
                  :style="isMobile ? 'transform:scale(0.9)' : ''"
                  :class="
                    currentGoods.goods_id == item.goods_id
                      ? 'checked pic'
                      : 'pic'
                  "
                  @click="choseGoods(item)"
                >
                  <el-image
                    style="width: 120px; height: 120px"
                    :src="item.goods_pic"
                    fit="contain"
                  />

                  <span class="demonstration">
                    {{ item.goods_name }} 价格 {{ item.price }} 元
                    <el-icon
                      v-if="currentGoods.goods_id == item.goods_id"
                      color="green"
                      size="18"
                    >
                      <Check />
                    </el-icon>
                  </span>
                </el-col>
              </el-row>
            </el-col>
          </el-form-item>
        </el-col>

        <el-col :span="24" class="box">
          <p class="title">
            第三步：填写收货地址↓↓↓支持批量下单！一行一个地址即可！每次最多500个地址
          </p>
          <table
            class="f14 r_table"
            width="100%"
            style="border: 1px solid #e3e3e3"
          >
            <tr>
              <td colspan="2">
                <div class="address">
                  <p style="padding-left: 10px; background: #f5f5f5">
                    <font color="#FF0000">格式：</font>
                    <font color="#0000FF">
                      姓名
                      <b>，</b>
                      手机
                      <b>，</b>
                      省 市 县区 详细地址
                      <b>，</b>
                      邮编
                    </font>
                    <font color="#FF0000">
                      （邮编可以为空，也可以为订单号）
                    </font>
                    <br />
                    例如：张三峰
                    <b>，</b>
                    13688888888
                    <b>，</b>
                    浙江省 杭州市 西湖区 万塘路18号黄龙时代广场A座
                    <b>，</b>
                    000000
                  </p>
                </div>
              </td>
            </tr>
          </table>
          <el-input
            id="postscript"
            v-model="modifyDialog.currentDetail.address"
            type="textarea"
            name="postscript"
            :rows="8"
            onblur="javascript:this.value=this.value.replace(',','，');"
          />

          <el-col :span="24" style="margin-top: 10px">
            <el-row>
              <el-button
                id="btn_check"
                style="margin-right: 10px"
                type="warning"
                @click="checkisnos()"
              >
                检查地址格式是否正确
              </el-button>

              <el-upload
                ref="upload"
                class="upload-demo"
                :action="uploadUrl"
                :headers="headers"
                :limit="1"
                :on-exceed="handleExceed"
                :on-success="handleSuccess"
                :auto-upload="true"
                :show-file-list="false"
              >
                <template #trigger>
                  <el-button type="danger">导入表格</el-button>
                </template>
              </el-upload>
            </el-row>
          </el-col>

          <el-col :span="24" style="width: 100%; overflow: scroll">
            <div id="cheisnos" v-html="cheisnos" />
          </el-col>

          <el-col :span="24">
            <div id="addinputarrs">{{ addinputarrs }}</div>
          </el-col>
        </el-col>
      </el-form>

      <el-col :span="24" style="margin-top: 10px; text-align: center">
        <el-button type="primary" size="large" @click="chk_submit()">
          立即下单
        </el-button>
      </el-col>
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
import GiftAPI from "@/api/gift";
import { IModalConfig } from "@/components/CURD/types";
import AddresstAPI from "@/api/address";
import UserAPI from "@/api/user";
import { genFileId } from "element-plus";
import type { UploadInstance, UploadProps, UploadRawFile } from "element-plus";
import Decimal from "decimal.js";
import { useRouter } from "vue-router";
import { inject } from "vue";

const isMobile: any = inject("isMobile");

// 多空格合并为一个空格
String.prototype.ResetBlank = function () {
  var regEx = /\s+/g;
  return this.replace(regEx, " ");
};

const router = useRouter();
const placeOptions = ref([]);
const addressAddRef = ref();

const addressAddConfig: IModalConfig<any> = reactive({
  labelWidth: "200px",
  width: "50%",
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
      placeOptions.value = res;
    });
  }

  addressAddRef.value.setModalVisible();
};

const done = () => {
  fetchUserAddress();
};

const currentPlatfroms: any = computed(() => {
  const temps: any = {
    淘宝: "is_tb",
    京东: "is_jd",
    拼多多: "is_pdd",
    抖音: "is_dy",
    快手: "is_ks",
    阿里: "is_ali",
  };
  return temps;
});

const platformRefection: any = {
  is_tb: 1,
  is_pdd: 2,
  is_jd: 3,
  is_dy: 4,
  is_ks: 5,
  is_ali: 9,
};

const platformRefectionName: any = {
  is_tb: "淘宝",
  is_pdd: "拼多多",
  is_jd: "京东",
  is_dy: "抖音",
  is_ks: "快手",
  is_ali: "阿里",
};

const userAddress: any = ref([]);
const fetchUserAddress = () => {
  AddresstAPI.getUserAddressList({ status: 0 }).then((res: any) => {
    userAddress.value = res;
    if (res.length > 0) {
      modifyDialog.currentDetail.sendAddress = res[0].id;
    }
  });
};

// 快递公司
const currentExpressCompanies: any = ref([]);
const currentExpressCompaniesReflect: any = ref({});
const currentExpressCompaniesStorages: any = ref([]);
const currentExpressCompaniesStoragesReflect: any = ref({});
const currentExpressCompaniesStoragesGoods: any = ref([]);

const modifyFormData = ref([
  {
    type: "opt",
    slotName: "sendAddress",
    prop: "sendAddress",
    label: "发货地址",
    width: "80%",
  },
  {
    type: "opt",
    slotName: "platform",
    prop: "platform",
    label: "平台",
    placeholder: "请选择平台",
    width: "80%",
  },
  {
    type: "select",
    prop: "expressCompany",
    label: "快递公司",
    placeholder: "请选择快递公司",
    options: currentExpressCompanies,
    change: true,
  },
  {
    type: "select",
    prop: "storage",
    label: "仓库列表",
    placeholder: "请选择仓库",
    options: currentExpressCompaniesStorages,
    change: true,
  },
  {
    type: "opt",
    prop: "expressPrice",
    slotName: computed(() => {
      return Object.keys(currentExpressInfo.value).length > 0
        ? "expressPrice"
        : "";
    }),
    label: "快递价格",
  },
  {
    slotName: "goods",
    prop: "goodsId",
    label: "商品列表",
  },
  {
    type: "opt",
    slotName: "address",
    prop: "address",
    label: "地址",
    width: "80%",
  },
]);

const chosePlatform = (express: any) => {
  modifyDialog.currentDetail.expressCompany = null;
  modifyDialog.currentDetail.platform = express;
  modifyDialog.currentDetail.storage = null;
  const expressCompanies: any = {};
  sendOptions?.value?.forEach((item: any) => {
    if (item[express] == 1) {
      if (!expressCompanies[item.name]) {
        expressCompanies[item.name] = [];
      }
      expressCompanies[item.name].push(item);
    }
  });
  currentExpressCompaniesReflect.value = expressCompanies;
  changeCurrentExpressCompanies();

  // 仓库清空
  currentExpressCompaniesStorages.value = [];
  modifyDialog.currentDetail.storage = null;
  // 商品清空
  currentExpressCompaniesStoragesGoods.value = [];
  currentGoods.value = {};
};

const handelSelect = (val?: any, type?: any) => {
  // 选中快递公司
  if (type === "expressCompany") {
    currentExpressCompaniesStoragesReflect.value = {};
    modifyDialog.currentDetail.storage = null;
    currentGoods.value = {};
    currentExpressCompaniesReflect.value[val].forEach((item: any) => {
      if (!currentExpressCompaniesStoragesReflect.value[item.cang.cang_id]) {
        currentExpressCompaniesStoragesReflect.value[item.cang.cang_id] = [];
      }

      currentExpressCompaniesStoragesReflect.value[item.cang.cang_id] =
        item.goods;
    });

    changeCurrentExpressCompaniesStorages();
    changeCurrentExpressCompaniesStoragesGoods();
  } else if (type === "storage") {
    currentGoods.value = {};
    changeCurrentExpressCompaniesStoragesGoods();
  }
};

const currentGoods: any = ref({});
const choseGoods = (item: any) => {
  currentGoods.value = item;
};

const rules: any = reactive<any | Object>({
  a1: [{ required: true, message: "请输入商品名称", trigger: "blur" }],
});

const modifyDialog: any = reactive({
  title: "派发",
  visible: false,
  viewType: "add", // modify | add | view
  currentDetail: {},
});

const sendOptions: any = ref([]);
const fetchSendOptions = async () => {
  sendOptions.value = await GiftAPI.getSendOptions();
};

function trim(str: any) {
  str = str.replace(/^(\s|\u00A0)+/, "");

  for (var i = str.length - 1; i >= 0; i--) {
    if (/\S/.test(str.charAt(i))) {
      str = str.substring(0, i + 1);

      break;
    }
  }

  return str;
}

function exists_multispace(str: any) {
  var rtn_result = false;

  var str_length_old = str.length;

  str = str.replace(/\  /g, " ");

  var str_length = str.length;

  if (str_length_old != str_length) {
    rtn_result = true;
  }

  return rtn_result;
}

const isEnabled = ref(-1);
const addressCheckClicked = ref(false); // 地址校验是否点击
const addressCheckPassed = ref(false); // 地址校验是否通过
const cheisnos = ref("");
const addinputarrs = ref();
function checkisnos(alreadyParse = false) {
  let isno = 1;
  if (Object.keys(currentGoods.value).length <= 0) {
    ElMessage.error("请选择商品");
    return isno;
  }
  addressCheckClicked.value = true;

  //是否错误标识
  let addtext: any = modifyDialog.currentDetail.address;

  var addtextarr: any = new Array();

  var adddan = new Array();

  addtextarr = addtext?.split("\n") || [];
  if (!addtextarr.length) {
    ElMessage.error("请输入地址");
    return;
  }

  let addstr = "";

  let addinputarr = "";

  let addsum = 0;

  // 出错的行数 用来在textarea显示错误行数
  var errorLine = [];
  // 等待批量矫正格式的地址
  var waitParse: any = {};

  // 是否已经弹出错误小心
  var isAlertError = false;

  //最多一次只能提交5000个

  if (addtextarr.length <= 5000) {
    for (let i = 0; i < addtextarr.length; i++) {
      if (addtextarr[i] && trim(addtextarr[i]) != "") {
        addtextarr[i] = addtextarr[i].replace(/\'/g, "");

        addtextarr[i] = addtextarr[i].replace(/\,/g, "，");

        //addinputarr+="<input type='hidden' name='addinputarr["+addsum+"]' value='"+addtextarr[i]+"' >";

        adddan = trim(addtextarr[i]).split("，");

        // 只有2个逗号 补充邮编
        if (adddan.length == 3) {
          addtextarr[i] = addtextarr[i] + "， 000000";
          adddan = trim(addtextarr[i]).split("，");
        }

        addsum += 1;

        //保存表单数据方便提交

        if (adddan.length != 5 && adddan.length != 4 && adddan.length != 3) {
          if (!isAlertError) {
            alert(
              "第 " +
                addsum +
                " 个收货地址格式错误，请参考格式说明中的逗号位置！"
            );
            isAlertError = true;
            errorLine.push(addsum);
          }
          isno = 0;
        }

        let shouhuodizhiKey = 0;
        if (adddan.length == 5) {
          shouhuodizhiKey = 3;
          var shouhuodizhi = adddan[3]; //获取收件地址
        } else if (adddan.length == 4) {
          shouhuodizhiKey = 2;
          shouhuodizhi = adddan[2]; //获取收件地址
        } else if (adddan.length == 3) {
          shouhuodizhiKey = 2;
          shouhuodizhi = adddan[2]; //获取收件地址
        }

        //判断地址之间空格是否是多个

        if (exists_multispace(trim(shouhuodizhi))) {
          isno = 0;
          if (!isAlertError) {
            // 多个空格合并为1个空格
            // 修改后放入原数组
            adddan[shouhuodizhiKey] = adddan[shouhuodizhiKey].ResetBlank();
            addtextarr[i] = adddan.join("，");
            isno = 1;

            //获取收件地址
            var shouhuodizhi = adddan[shouhuodizhiKey];
            if (exists_multispace(trim(shouhuodizhi))) {
              alert(
                "第 " + addsum + " 个收货地址中有连续两个空格，请仔细检查！"
              );
              isAlertError = true;
              errorLine.push(addsum);
              isno = 0;
            }
          }
        }

        var shdz_array = trim(shouhuodizhi).split(" ");

        if (shdz_array.length < 4) {
          isno = 0;
          if (!isAlertError) {
            // 省市区空格分隔
            if (
              alreadyParse == false &&
              !(shdz_array.length == 1 && shdz_array[0] == "")
            ) {
              waitParse[i] = shouhuodizhi;
            } else {
              alert("第 " + addsum + " 个收货地址格式错误，请仔细检查！");
              isAlertError = true;
              errorLine.push(addsum);
              isno = 0;
            }
          }
        }

        addstr += "<tr><td class='addyes'>" + addsum + "</td>";
        for (let j = 0; j < adddan.length; j++) {
          var regex = /^\d+\./g;
          var m = adddan[j].match(regex);
          if (m != null) {
            adddan[j] = adddan[j].replace(m[0], "");
          }

          //如果不是四个淘宝的逗号格式提示错误

          if (adddan.length != 5 && adddan.length != 4 && adddan.length != 3) {
            //如果有数据就显示 ，没有显示空

            if (adddan[j]) {
              addstr += "<td  class='adderror'>" + adddan[j] + "</td>";
            } else {
              addstr += "<td  class='adderror'>" + "</td>";
            }
          } else {
            if (adddan.length == 4 && j == 2) {
              addstr += "<td class='addyes'></td>";
            }

            if (isno == 0) {
              addstr += "<td class='adderror'>" + adddan[j] + "</td>";
            } else {
              addstr += "<td class='addyes'>" + adddan[j] + "</td>";
            }
          }
        }

        var regex = /^\d+\./g;
        var m = addtextarr[i].match(regex);
        if (m != null) {
          addtextarr[i] = addtextarr[i].replace(m[0], "");
        }

        addinputarr +=
          "<input type='hidden' name='addinputarr[" +
          addsum +
          "]' value='" +
          addtextarr[i] +
          "' >";

        addstr += "</tr>";
      }
    }
  } else {
    alert("亲，一次最多只能批量下5000单，请减少收货地址！");
    isno = 0;
  }

  if (Object.keys(waitParse).length > 0 && alreadyParse == false) {
    GiftAPI.parseAdress({
      addressList: waitParse,
    }).then((newAddressList: any) => {
      for (let i in newAddressList) {
        adddan = trim(addtextarr[i]).split("，");
        if (adddan.length == 5) {
          var shouhuodizhiKey = 3;
          var shouhuodizhi = adddan[3]; //获取收件地址
        } else if (adddan.length == 4) {
          var shouhuodizhiKey = 2;
          var shouhuodizhi = adddan[2]; //获取收件地址
        } else if (adddan.length == 3) {
          var shouhuodizhiKey = 2;
          var shouhuodizhi = adddan[2]; //获取收件地址
        } else {
          // 没有这个项
          var shouhuodizhiKey = -1;
        }

        adddan[shouhuodizhiKey] = newAddressList[i];
        addtextarr[i] = adddan.join("，");
      }
      addressCheckPassed.value = true;
      modifyDialog.currentDetail.address = addtextarr.join("\n");
      return checkisnos(true);
    });
  } else {
    let goodsPrice = new Decimal(currentGoods.value.price || 0);
    let expressPrice = new Decimal(currentExpressInfo.value.price || 0);
    let profit = new Decimal(userInfo.value.profit_rate);

    let current_price = goodsPrice.add(expressPrice);
    current_price = current_price.add(profit);

    console.log("商品价格", goodsPrice.toFixed(2));
    console.log("快递价格", expressPrice.toFixed(2));
    console.log("利润金额元", profit.toFixed(2));
    console.log("销售价格", current_price.toFixed(2));

    //计算价格
    let kuaidisum = current_price.mul(addsum);

    var table_html =
      `<table class='addtextstyle' width='100%' style="table-layout: fixed; width: 100%;" >
      <tr><td  class='addyes' style='width: 50px' ><b>编号</b></td>
      <td  class='addyes' style='width: 200px'><b>名字</b></td>
      <td  class='addyes' style='width: 200px'><b>手机</b></td>
      <td  class='addyes' style='width: 200px'><b>电话</b></td>
      <td  class='addyes' style='width: 200px'><b>地址</b></td>
      <td  class='addyes' style='width: 200px'><b></b></td></tr>` +
      addstr +
      "</table>" +
      "<p> 共 " +
      addsum +
      " X " +
      current_price +
      "=" +
      kuaidisum.toFixed(2) +
      " <input type='hidden' value=" +
      kuaidisum.toFixed(2) +
      " name='kuaidipicle' id='kuaidipicle' /><input type='hidden' value='0' name='youyuer' id='youyuer'/>";

    cheisnos.value = table_html;

    // 显示错误行数
    if (errorLine.length > 0) {
      for (var i = 0; i < addtextarr.length; i++) {
        if (addtextarr[i]) {
          var regex = /^\d+\./g;
          var m = addtextarr[i].match(regex);
          if (m == null) {
            addtextarr[i] = (i + 1).toString() + "." + addtextarr[i];
          }
        }
      }
    }

    addtext = addtextarr.join("\n");
    modifyDialog.currentDetail.address = addtext;

    isEnabled.value = isno;
    addressCheckPassed.value = isno === 1;
    return isno;
  }
}

async function chk_submit() {
  if (!addressCheckClicked.value) {
    let no = checkisnos();
    if (no == 0) {
      return;
    }
  }

  if (!modifyDialog.currentDetail.sendAddress) {
    ElMessage.error("请选择发货地址!");
    return;
  }

  if (!addressCheckPassed.value) {
    ElMessage.error("有错误数据，请调整后再提交!");
    return;
  }

  let sendAddressInfo: any = {};
  userAddress.value?.forEach((item: any) => {
    if (item.id === modifyDialog.currentDetail.sendAddress) {
      sendAddressInfo = item;
    }
  });

  let expressInfo: any = {};
  currentExpressCompaniesReflect.value[
    modifyDialog.currentDetail.expressCompany
  ]?.forEach((item: any) => {
    if (item.cang.cang_id === modifyDialog.currentDetail.storage) {
      expressInfo = item;
    }
  });

  modifyDialog.currentDetail.sendAddress;

  if (Object.keys(expressInfo).length <= 0) {
    ElMessage.error("请选择快递公司");
    return;
  }

  if (Object.keys(currentGoods.value).length <= 0) {
    ElMessage.error("请选择商品");
    return;
  }

  const data: any = {
    kuaidiId: expressInfo.id,
    cangId: expressInfo.cang.cang_id,
    goodsId: currentGoods.value.goods_id,
    goodsName: currentGoods.value.goods_name,
    platform: platformRefection[modifyDialog.currentDetail.platform],
    platformName: platformRefectionName[modifyDialog.currentDetail.platform],
    sendName: sendAddressInfo.name,
    telephone: sendAddressInfo.telephone,
    sendMobile: sendAddressInfo.phone,
    sendProvince: sendAddressInfo.province,
    sendCity: sendAddressInfo.city,
    sendDistrict: sendAddressInfo.district,
    sendAddress: sendAddressInfo.address,
    storageProvince: expressInfo.cang.send_province,
    storageCity: expressInfo.cang.send_city,
    storageDistrict: expressInfo.cang.send_district,
    storageAddress: expressInfo.cang.send_address,
    recList: modifyDialog.currentDetail.address?.split("\n") || [],
    wuhuoyuan: expressInfo.is_wuhuoyuan,
    isXinfeng: true,
  };

  const res: any = await GiftAPI.buy(data);

  if (res.errorMsg) {
    ElMessageBox.alert(res.errorMsg, "提示");
    return;
  } else if (res.failed.length > 0) {
    let msg = "";
    res.errors.forEach((item: any) => {
      msg += "<p style='color:red'>" + item + "</p><br />";
    });

    msg +=
      "<p style='color:green'>取号成功" +
      res.success.length +
      "个订单</p><br />";

    ElMessage({
      dangerouslyUseHTMLString: true,
      message: msg,
    });
  } else {
    ElMessage.success("操作成功");
  }

  router.replace({
    path: "/gift/sendLogs",
    query: {
      mainOrderSn: res.mainOrderSn,
    },
  });
}

const userInfo: any = ref({});
const fetchMe = () => {
  UserAPI.getInfo().then((res: any) => {
    userInfo.value = res;
    if (userInfo.value.is_disabled == 1) {
      ElMessage.error("用户账户已被禁用，无法购买");
      return;
    }

    if (userInfo.value.is_xinfeng == 0) {
      ElMessage.error("无权限，请联系管理员开通");
      return;
    }
  });
};

const upload = ref<UploadInstance>();
const handleExceed: UploadProps["onExceed"] = (files) => {
  upload.value!.clearFiles();
  const file = files[0] as UploadRawFile;
  file.uid = genFileId();
  upload.value!.handleStart(file);
  upload.value!.submit();
};

const handleSuccess = (response: any) => {
  if (response.code === 200) {
    modifyDialog.currentDetail.address = response.data.address;
    addressAddRef.value.setFormItemData("detailAddress", response.data.address);
  } else {
    ElMessage.error(response.errorMsg);
  }
};

let uploadUrl = import.meta.env.VITE_APP_API_URL + "/api/up_csv";
if (import.meta.env.VITE_APP_API_URL.indexOf("/api") > -1) {
  uploadUrl = import.meta.env.VITE_APP_API_URL + "/up_csv";
}

const headers = {
  token: localStorage.getItem("token"),
};

const currentExpressInfo: any = computed(() => {
  let expressInfo: any = {};
  currentExpressCompaniesReflect.value[
    modifyDialog.currentDetail.expressCompany
  ]?.forEach((item: any) => {
    if (item.cang.cang_id === modifyDialog.currentDetail.storage) {
      expressInfo = item;
      const exprice = new Decimal(expressInfo.price || 0);
      const profit = new Decimal(userInfo.value.profit_rate || 0);
      expressInfo.salePrice = exprice.add(profit);
    }
  });
  return expressInfo;
});

// 获取快递公司
const changeCurrentExpressCompanies = () => {
  currentExpressCompanies.value = [];
  const expressCompanies: any = {};
  sendOptions.value.forEach((item: any) => {
    if (item[modifyDialog.currentDetail.platform] == 1) {
      if (!expressCompanies[item.name]) {
        expressCompanies[item.name] = [];
      }
      expressCompanies[item.name].push(item);
    }
  });

  for (const key in expressCompanies) {
    currentExpressCompanies.value.push({
      label: key,
      value: key,
    });
  }
};

// 获取仓库列表
const changeCurrentExpressCompaniesStorages = () => {
  currentExpressCompaniesStorages.value = [];
  currentExpressCompaniesReflect.value[
    modifyDialog.currentDetail.expressCompany
  ]?.forEach((item: any) => {
    // 购买信封
    if (item.is_xinfeng == 1) {
      currentExpressCompaniesStorages.value.push({
        value: item.cang.cang_id,
        label:
          item.cang.cang_name +
          ": " +
          item.cang.send_city +
          item.cang.send_district +
          item.cang.send_province +
          item.cang.send_address +
          (item.is_wuhuoyuan == 1 ? "【无货源模式】" : "") +
          (item.is_xinfeng == 1 ? "【购买信封】" : "【购买礼品】"),
      });
    }
  });
};

// 获取商品列表
const changeCurrentExpressCompaniesStoragesGoods = () => {
  currentExpressCompaniesStoragesGoods.value = [];
  currentExpressCompaniesStoragesGoods.value =
    currentExpressCompaniesStoragesReflect.value[
      modifyDialog.currentDetail.storage
    ] || [];
};

const setDefault = () => {
  const params: any = {
    currentGoods: currentGoods.value,
    platform: modifyDialog.currentDetail.platform,
    expressCompany: modifyDialog.currentDetail.expressCompany,
    storage: modifyDialog.currentDetail.storage,
  };

  localStorage.setItem("defaultXinfeng", JSON.stringify(params));
  ElMessage.success("操作成功");
};

const getDefault = () => {
  try {
    const str = localStorage.getItem("defaultXinfeng") || "";
    if (str) {
      const params: any = JSON.parse(str);

      if (Object.keys(params).length > 0) {
        currentGoods.value = params.currentGoods;
        modifyDialog.currentDetail.platform = params.platform;
        chosePlatform(params.platform);
        modifyDialog.currentDetail.expressCompany = params.expressCompany;
        handelSelect(params.expressCompany, "expressCompany");
        modifyDialog.currentDetail.storage = params.storage;
        handelSelect(params.storage, "storage");
        choseGoods(params.currentGoods);
      }
    }
  } catch (err: any) {}
};

onMounted(async () => {
  await fetchSendOptions();
  fetchUserAddress();
  fetchMe();
  getDefault();
});
</script>

<style scoped>
.alert-danger {
  border-color: rgb(238, 211, 215);
  color: rgb(185, 74, 72);
  background-color: rgb(242, 222, 222);
}
.demo-image .block {
  padding: 30px 0;
  text-align: center;
  border-right: solid 1px var(--el-border-color);
  display: inline-block;
  width: 20%;
  box-sizing: border-box;
  vertical-align: top;
}
.demo-image .block:last-child {
  border-right: none;
}
.demonstration {
  display: block;
  color: var(--el-text-color-secondary);
  font-size: 14px;
  /*margin-bottom: 20px;*/
  text-align: center;
  display: flex;
  align-items: center;
  flex-direction: row;
}

.item > div {
  display: flex;
  flex-direction: column;
  justify-content: center;
  flex-wrap: wrap;
  align-content: space-around;
  padding-top: 20px;
}

.item .checked {
  /*border: 1px solid green;*/

  background: #f3f3f3;
}

.pic {
  display: flex;
  justify-content: space-around;
  align-items: center;
}

.box {
  border: 1px solid #e5e5e5;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 10px;
}

.box .title {
  font-weight: 600;
  color: green;
}
</style>
<style>
.adderror {
  background: #fc0;
}
</style>
