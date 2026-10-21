import request from "@/utils/request";

const apiUrl = import.meta.env.VITE_APP_API_URL;

const UserAPI = {
  getPayQr(amount: any) {
    return request({
      url: `${apiUrl}/user/charge/${amount}`,
      method: "get",
    });
  },
  submitWechaPay(data: any) {
    return request({
      url: `${apiUrl}/user/charge/createWechatPay`,
      method: "post",
      data: data,
    });
  },
  wechatPayPass(orderNo: any) {
    return request({
      url: `${apiUrl}/user/charge/wechatPay/pass/${orderNo}`,
      method: "get",
    });
  },
  wechatPayReject(orderNo: any) {
    return request({
      url: `${apiUrl}/user/charge/wechatPay/reject/${orderNo}`,
      method: "get",
    });
  },
  getPayResult(orderNo: any, amount: any) {
    return request({
      url: `${apiUrl}/user/chargeResult/${orderNo}/${amount}`,
      method: "get",
    });
  },
  getPromotionList() {
    return request({
      url: `${apiUrl}/user/promotion/list`,
      method: "get",
    });
  },
  getPromotionUrl() {
    return request({
      url: `${apiUrl}/user/promotion/url`,
      method: "get",
    });
  },
  getWechatPayAuditList(data: any = {}) {
    return request({
      url: `${apiUrl}/user/wechatPayOrders`,
      method: "post",
      data: data,
    });
  },
  getAliPayAuditList(data: any = {}) {
    return request({
      url: `${apiUrl}/user/alipayOrders`,
      method: "post",
      data: data,
    });
  },
  updateUserInfo(data: any) {
    return request({
      url: `${apiUrl}/user/updateUserInfo`,
      method: "post",
      data: data,
    });
  },
  updateUserCoin(data: any) {
    return request({
      url: `${apiUrl}/user/updateUserCoin`,
      method: "post",
      data: data,
    });
  },
  updatePassword(data: any) {
    return request({
      url: `${apiUrl}/user/updatePassword`,
      method: "post",
      data: data,
    });
  },
  createUser(data: any) {
    return request({
      url: `${apiUrl}/user/createUser`,
      method: "post",
      data: data,
    });
  },
  deleteUser(id: any) {
    return request({
      url: `${apiUrl}/user/deleteUser/${id}`,
      method: "get",
    });
  },
  getSettingInfo() {
    return request({
      url: `${apiUrl}/user/setting/info`,
      method: "get",
    });
  },
  getUserSettingInfo() {
    return request({
      url: `${apiUrl}/user/settingInfo`,
      method: "get",
    });
  },
  updateSetting(data: any) {
    return request({
      url: `${apiUrl}/user/setting/update`,
      method: "post",
      data: data,
    });
  },
  fetchUserList(data: any = {}) {
    return request({
      url: `${apiUrl}/user/list`,
      method: "post",
      data: data,
    });
  },
  fetchUserLoginLogs(data: any = {}) {
    return request({
      url: `${apiUrl}/user/loginLogs`,
      method: "post",
      data: data,
    });
  },
  resetCheck(data: any = {}) {
    return request({
      url: `${apiUrl}/user/checkAccountExist`,
      method: "post",
      data: data,
    });
  },
  reset1(data: any = {}) {
    return request({
      url: `${apiUrl}/user/resetByEmail`,
      method: "post",
      data: data,
    });
  },
  reset2(data: any = {}) {
    return request({
      url: `${apiUrl}/user/reset`,
      method: "post",
      data: data,
    });
  },
  /**
   * 获取当前登录用户信息
   *
   * @returns 登录用户昵称、头像信息，包括角色和权限
   */
  getInfo() {
    return request<any, UserInfo>({
      url: `${apiUrl}/user/me`,
      method: "get",
    });
  },
  point2coin(data: any) {
    return request({
      url: `${apiUrl}/user/point2coin`,
      method: "post",
      data: data,
    });
  },
  updateAvatar(data: any) {
    return request({
      url: `${apiUrl}/user/updateAvatar`,
      method: "post",
      data: data,
    });
  },
  pointLogs(data: any) {
    return request({
      url: `${apiUrl}/user/pointLogs`,
      method: "post",
      data: data,
    });
  },
  coinLogs(data: any) {
    return request({
      url: `${apiUrl}/user/coinLogs`,
      method: "post",
      data: data,
    });
  },
};
export default UserAPI;

/** 登录用户信息 */
export interface UserInfo {
  /** 用户ID */
  userId?: number;

  /** 用户名 */
  username?: string;

  /** 昵称 */
  nickname?: string;

  /** 头像URL */
  avatar?: string;

  /** 角色 */
  roles: string[];

  /** 权限 */
  perms: string[];
}

/**
 * 用户分页查询对象
 */
export interface UserPageQuery extends PageQuery {
  /** 搜索关键字 */
  keywords?: string;

  /** 用户状态 */
  status?: number;

  /** 部门ID */
  deptId?: number;

  /** 开始时间 */
  createTime?: [string, string];
}

/** 用户分页对象 */
export interface UserPageVO {
  /** 用户头像URL */
  avatar?: string;
  /** 创建时间 */
  createTime?: Date;
  /** 部门名称 */
  deptName?: string;
  /** 用户邮箱 */
  email?: string;
  /** 性别 */
  genderLabel?: string;
  /** 用户ID */
  id?: number;
  /** 手机号 */
  mobile?: string;
  /** 用户昵称 */
  nickname?: string;
  /** 角色名称，多个使用英文逗号(,)分割 */
  roleNames?: string;
  /** 用户状态(1:启用;0:禁用) */
  status?: number;
  /** 用户名 */
  username?: string;
}

/** 用户表单类型 */
export interface UserForm {
  /** 用户头像 */
  avatar?: string;
  /** 部门ID */
  deptId?: number;
  /** 邮箱 */
  email?: string;
  /** 性别 */
  gender?: number;
  /** 用户ID */
  id?: number;
  /** 手机号 */
  mobile?: string;
  /** 昵称 */
  nickname?: string;
  /** 角色ID集合 */
  roleIds?: number[];
  /** 用户状态(1:正常;0:禁用) */
  status?: number;
  /** 用户名 */
  username?: string;
}

/** 个人中心用户信息 */
export interface UserProfileVO {
  /** 用户ID */
  id?: number;

  /** 用户名 */
  username?: string;

  /** 昵称 */
  nickname?: string;

  /** 头像URL */
  avatar?: string;

  /** 性别 */
  gender?: number;

  /** 手机号 */
  mobile?: string;

  /** 邮箱 */
  email?: string;

  /** 部门名称 */
  deptName?: string;

  /** 角色名称，多个使用英文逗号(,)分割 */
  roleNames?: string;

  /** 创建时间 */
  createTime?: Date;
}

/** 个人中心用户信息表单 */
export interface UserProfileForm {
  /** 用户ID */
  id?: number;

  /** 用户名 */
  username?: string;

  /** 昵称 */
  nickname?: string;

  /** 头像URL */
  avatar?: string;

  /** 性别 */
  gender?: number;

  /** 手机号 */
  mobile?: string;

  /** 邮箱 */
  email?: string;
}

/** 修改密码表单 */
export interface PasswordChangeForm {
  /** 原密码 */
  oldPassword?: string;
  /** 新密码 */
  newPassword?: string;
  /** 确认新密码 */
  confirmPassword?: string;
}

/** 修改手机表单 */
export interface MobileBindingForm {
  /** 手机号 */
  mobile?: string;
  /** 验证码 */
  code?: string;
}

/** 修改邮箱表单 */
export interface EmailBindingForm {
  /** 邮箱 */
  email?: string;
  /** 验证码 */
  code?: string;
}
