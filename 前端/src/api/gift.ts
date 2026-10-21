import request from "@/utils/request";

const apiUrl = import.meta.env.VITE_APP_API_URL;

const GiftAPI = {
  getSendOptions() {
    return request({
      url: `${apiUrl}/gift/sendOptions`,
      method: "GET",
    });
  },
  parseAdress(data: any) {
    return request({
      url: `${apiUrl}/gift/parseAddress`,
      method: "POST",
      data: data,
    });
  },
  buy(data: any) {
    return request({
      url: `${apiUrl}/gift/buy`,
      method: "post",
      data: data,
    });
  },
  logs(data: any) {
    return request({
      url: `${apiUrl}/gift/logs`,
      method: "post",
      data: data,
    });
  },
  exportLogs(data: any) {
    return request({
      url: `${apiUrl}/gift/export`,
      method: "post",
      data: data,
      responseType: "blob",
    });
  },
  queryOrderStatus(orderSn: any) {
    return request({
      url: `${apiUrl}/gift/queryOrderStatus/${orderSn}`,
      method: "get",
    });
  },
};
export default GiftAPI;
