<center>

<h1 align="center">OneBotFrameworkForPHP</h1>

<p align="center">
  <a href="LICENSE">
    <img src="https://img.shields.io/badge/license-MIT-lightgrey.svg" alt="LICENSE">
  </a>
  <img src="https://img.shields.io/badge/Platform-Windows%20%7C%20Mac%20%7C%20Linux-red.svg" alt="platform">
  <a href="https://github.com/budingxiaocai" title="点击访问">
    <img src="https://img.shields.io/badge/Author-%E5%B8%83%E4%B8%81%E5%B0%8F%E6%89%8D-blue.svg">
  </a>
</p>

<div align="center">
  <strong>👉 基于PHP的OneBot开发框架 👈</strong><br>
  <sub>适用于 Linux，macOS，Windows 等平台，只要是PHP支持的平台都可以运行</sub><br/>
  <sub>基于<a href="https://github.com/walkor/GatewayWorker">GatewayWorker</a>打造</sub>
</div>
</center><br>

## 💽 安装方式
下载 [Release](https://github.com/budingxiaocai/releases/latest) 里的OneBotFrameworkForPHP.phar文件运行即可

## 🎨 使用方法
第一次运行会自动在运行目录创建app/QQEvents.php文件，根据文件中的注释及OneBot的文档自行更改业务逻辑即可食用

[Go-CqHttp文档](https://docs.go-cqhttp.org)

[OneBotFrameworkForPHP文档](https://github.com/budingxiaocai/OneBotFrameworkForPHP/wiki)
##### 命令行用法：
```
  php OneBotFrameworkForPHP.phar [options]
  -p, --port <number>         设置服务器运行的端口号，默认为6880
  -d, --run-dir <path>        设置服务器运行的目录，默认为当前目录
  --disableFileMonitor <1|0>  禁用文件监控功能，默认为启用
  -h, --help                  显示帮助信息
```

#### 绑定OneBot服务器
##### 以NapCat为例

**1.** 打开NapCat的WebUi
![image](https://github.com/user-attachments/assets/8e34410e-a6fc-4d13-98e7-febd30e42d17)

**2.** 打开"网络配置"一栏，点击"添加配置"按钮

**3.** 填写名称（随意），类型选择WebSocket客户端（websocketClients），打开"启用"开关，URL填写：ws://框架所在设备的IP地址:框架端口（默认为6880），消息格式选Array，其余默认
![image](https://github.com/user-attachments/assets/a3ce64ce-67b2-4a47-b8d0-b3281f6e992b)

**4.** 点击"添加"按钮，完成！
##### 其他服务器请根据其对应的文档进行操作

## 🐞 Bug 反馈

如果您在使用过程中遇到问题，请 [发起issues](https://github.com/budingxiaocai/OneBotFrameworkForPHP/issues) 进行反馈。
