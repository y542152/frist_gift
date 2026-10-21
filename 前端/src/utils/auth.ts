const TOKEN_KEY_ADMIN = "admin-token";
const TOKEN_KEY = "token";

function getToken(): string {
  console.log("getToken哪个token ?", isAdmin() ? TOKEN_KEY_ADMIN : TOKEN_KEY);
  return localStorage.getItem(isAdmin() ? TOKEN_KEY_ADMIN : TOKEN_KEY) || "";
}

function setToken(token: string) {
  return localStorage.setItem(isAdmin() ? TOKEN_KEY_ADMIN : TOKEN_KEY, token);
}

function removeToken() {
  console.log("删除哪个token ?", isAdmin() ? TOKEN_KEY_ADMIN : TOKEN_KEY);
  return localStorage.removeItem(isAdmin() ? TOKEN_KEY_ADMIN : TOKEN_KEY);
}

function isLogin(): boolean {
  return !!getToken();
}

function isAdmin(): boolean {
  return (
    window.location.href.indexOf("/admin/") > -1 ||
    window.location.href.indexOf("/adminLogin") > -1
  );
}
export { getToken, setToken, removeToken, isLogin, isAdmin };
