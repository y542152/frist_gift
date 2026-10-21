import moment from "moment";

export const exportData = (
  f: any,
  formData: any,
  t: string,
  type?: boolean
) => {
  return new Promise((resolve, reject) => {
    f(type ? formData : { ...formData })
      .then((res: any) => {
        const url = window.URL.createObjectURL(new Blob([res.data]));
        const link = document.createElement("a");
        link.style.display = "none";
        link.href = url; // 设置a标签路径
        link.download = t;
        document.body.appendChild(link);
        link.click();
        URL.revokeObjectURL(link.href);
        document.body.removeChild(link);
        resolve(true);
      })
      .catch((error: any) => {
        reject(error);
      });
  });
};

/**
 * 列表字段枚举过滤
 */
export const enumFilter = (obj: any, type: any) => {
  if (type === "warefileState") {
    if (obj.state === 1) {
      return "正常";
    } else if (obj.state === 2) {
      return "禁用";
    }
  } else if (type === "sortOutStates") {
    if (obj.sortOutState === 1) {
      return "待分拣";
    } else if (obj.sortOutState === 2) {
      return "已分拣";
    } else if (obj.sortOutState === 3) {
      return "缺货";
    } else if (obj.sortOutState === 4) {
      return "分拣中";
    }
  } else if (type === "orderStates") {
    if (obj.orderState === 1) {
      return "待发货";
    } else if (obj.orderState === 2) {
      return "已发货";
    } else if (obj.orderState === 3) {
      return "缺货";
    }
  }
};

// 获取前30天的时间 YYYY-MM-DD
export const getDate = () => {
  return [
    moment().subtract(1, "month").startOf("day").format("YYYY-MM-DD"),
    moment(new Date()).endOf("day").format("YYYY-MM-DD"),
  ];
};

// 获取前30天的时间 YYYY-MM-DD HH:mm:ss
export const getDates = () => {
  return [
    moment().subtract(1, "month").startOf("day").format("YYYY-MM-DD HH:mm:ss"),
    moment(new Date()).endOf("day").format("YYYY-MM-DD HH:mm:ss"),
  ];
};

/**
 * 时间戳转换成年月日时分秒
 * @param val 传过来的时间戳
 */
export const getTime = (val: any) => {
  if (val) return moment(val).format("YYYY-MM-DD HH:mm:ss");
};

export const getTimeByFormat = (val: any, format: string) => {
  if (val) return moment(val).format(format);
};

/**
 * 时间戳转换成年月日时分秒
 * @param val 传过来的时间戳
 */
export const getTimes = (val: any) => {
  if (val) return moment(val).format("YYYY-MM-DD");
};

/**
 * 时间戳转换成年月日
 * @param val 传过来的时间戳
 */
export const getSpecificDate = (val: any) => {
  return moment(val).format("YYYY-MM-DD");
};

/**
 * 数组扁平化
 * @param arr 需要处理的数组
 * @returns 返回处理过新的数组
 */
export function flatten(arr: any) {
  return arr.reduce((result: string | any[], item: any) => {
    return result.concat(Array.isArray(item) ? flatten(item) : item);
  }, []);
}

/**
 * @param {string} path
 * @returns {Boolean}
 */
export function isExternal(path: string) {
  const isExternal = /^(https?:|http?:|mailto:|tel:)/.test(path);
  return isExternal;
}

/**
 * 获取当天日期
 * @type {type:string}  不传默认返回今天日期
 */
export const getDateTime = (type?: string) => {
  // 获歌当前 || 明天日期
  return type === "tomorrow"
    ? moment().add(1, "days").format("YYYY-MM-DD")
    : moment().format("YYYY-MM-DD");
};

/**
 * 获取哪天日期
 * @type {num:any}  数字
 */
export const getDateDayTime = (num: any) => {
  // 获歌当前 || 明天日期
  return moment().add(num, "days").format("YYYY-MM-DD");
};

// 多位小数点----JS浮点数计算的bug

export const displaynum = (num: any) => {
  // const num1 = Math.floor(num) // 小数点前面的数字
  // const num2 = num.toString().split('.')
  const biantai = num.toFixed(2);
  return biantai;
};

// 转换
export const returnName = (num: any) => {
  if (num === "1") {
    return "原路退回";
  } else if (num === "2") {
    return "退到账户余额";
  } else if (num === "3") {
    return "退到转入账户";
  }
};

// 转换2
export const drawback = (num: any) => {
  if (num === "0") {
    return "仅售后";
  } else if (num === "1") {
    return "售后退货";
  } else if (num === "3") {
    return "部分售后";
  } else if (num === "4") {
    return "退补业务售后";
  }
};

// 防抖
export const debounce = (fn: any, delay?: number) => {
  let timer: any = null;
  return function (...args: any) {
    if (timer !== null) {
      clearTimeout(timer);
    }

    timer = setTimeout(() => {
      fn.apply(fn, args);
      timer = null;
    }, delay || 500);
  };
};
