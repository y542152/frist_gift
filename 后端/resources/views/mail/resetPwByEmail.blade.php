<!DOCTYPE html>
<html>
<head>
    <title></title>
</head>
<body>
<div class="container">
    <div class="content">
        <div class="url">
            <p>点击下方链接重置密码</p>
            <a href="{{ $url }}">{{ $url }}</a>
            <p>
                如果无法点击此链接，请复制链接到浏览器中。
            </p>
        </div>

        <p class="ignore">
            邮件2小时内有效，如非本人操作请忽略此邮件。
        </p>
    </div>
</div>
</body>
<style>
    html,
    body {
        height: 100%;
    }
    .container {
        background: #c5c5c5;
        height: 100%;
        text-align: center;
    }
    .content {
        margin: 0 auto;
        padding-top: 200px;
        width: 600px;
        background: white;
        height: 700px;
    }
    .logo {
        cursor: pointer;
    }

    .url {
        width: 90%;
        margin: 0 auto;
    }
    .url a {
        word-break: break-all;
        color: grey;
    }

    .ignore {
        color: grey;
        font-size: 0.8em;
    }
</style>
</html>
