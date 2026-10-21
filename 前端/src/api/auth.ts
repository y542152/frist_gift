import request from "@/utils/request";

const apiUrl = import.meta.env.VITE_APP_API_URL;

const AuthAPI = {
  /** 登录 接口*/
  login(data: LoginData) {
    const formData = new FormData();
    formData.append("username", data.username);
    formData.append("password", data.password);
    return request<any, LoginResult>({
      url: `${apiUrl}/user/login`,
      method: "post",
      data: formData,
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  },

  adminLogin(data: LoginData) {
    const formData = new FormData();
    formData.append("username", data.username);
    formData.append("password", data.password);
    return request<any, LoginResult>({
      url: `${apiUrl}/user/adminLogin`,
      method: "post",
      data: formData,
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  },

  register(data: any) {
    return request<any, LoginResult>({
      url: `${apiUrl}/user/register`,
      method: "post",
      data: data,
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  },

  /** 注销 接口*/
  logout() {
    return request({
      url: `${apiUrl}/user/logout`,
      method: "delete",
    });
  },
};

export default AuthAPI;

/** 登录请求参数 */
export interface LoginData {
  /** 用户名 */
  username: string;
  /** 密码 */
  password: string;
  password1?: string;
  /** 验证码缓存key */
  captchaKey?: string;
  /** 验证码 */
  captchaCode?: string;
  qq?: string;
  qqemail?: string;
}

/** 登录响应 */
export interface LoginResult {
  /** 访问token */
  token?: string;
  /** 过期时间(单位：毫秒) */
  expires?: number;
  /** 刷新token */
  refreshToken?: string;
  /** token 类型 */
  tokenType?: string;
}

/** 验证码响应 */
export interface CaptchaResult {
  /** 验证码缓存key */
  captchaKey: string;
  /** 验证码图片Base64字符串 */
  captchaBase64: string;
}
