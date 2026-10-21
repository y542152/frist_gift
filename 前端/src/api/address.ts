import request from "@/utils/request";

const apiUrl = import.meta.env.VITE_APP_API_URL;

const AddresstAPI = {
  getPlaceOptions() {
    return request({
      url: `${apiUrl}/address/index`,
      method: "GET",
    });
  },
  addAddress(data: any) {
    return request({
      url: `${apiUrl}/address/add`,
      method: "POST",
      data: data,
    });
  },
  getUserAddressList(data: any = {}) {
    return request({
      url: `${apiUrl}/address/list`,
      method: "POST",
      data: data,
    });
  },
  setAddressStatus(id: any, status: any) {
    return request({
      url: `${apiUrl}/address/setStatus/${id}/${status}`,
      method: "GET",
    });
  },
};
export default AddresstAPI;
