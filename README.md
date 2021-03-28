某校健康信息系统打卡脚本

# 功能

用于替代手动健康信息系统打卡的 PHP 脚本

在脚本中设置学号与密码后，运行脚本进行健康信息系统打卡

# 运行需求

- PHP 7.2+: 脚本运行环境
- PHP mbstring 扩展: 中文字符支持
- Tesseract OCR: 自动识别登录验证码
- PHP 可以读写脚本目录下的 `captcha.jpg`

# 使用

1. 安装 PHP、PHP mbstring 扩展与 [Tesseract OCR](https://github.com/tesseract-ocr/tesseract)
3. 下载或 clone 本项目
3. 确保脚本目录下的`captcha.jpg`可以被 PHP 读写
4. 修改`checkin.php`第 8 行处的学号与第 9 行处的密码
5. 运行`checkin.php`进行打卡
6. 检查脚本输出，若为`ERROR`则运行错误（后接详细报错），若为`SUCCESS`则运行成功（后接成功打卡的表单地址，可访问查看）

# FAQ

## 如何运行脚本？

脚本可通过 CLI 方式或 HTTP 方式运行

### CLI（推荐）

最简单的运行方式，在 Shell 中直接调用 PHP 运行脚本：`php /PATH/TO/checkin.php`

### HTTP

使用 PHP FastCGI 或 PHP-FPM 配合 Web Server（如 Nginx）将脚本解析到 HTTP 地址，通过使用任意设备访问该地址进行打卡

使用这种方式时，要确保脚本不被暴露在公网，或加入 HTTP 验证，由于每访问一次该地址都会进行一次打卡操作，将脚本暴露在公网可能会由于爬虫等不可控的访问被造成被多次重复打卡

## 如何实现每天自动打卡？

Linux 下可以使用 crontab 实现自动化，Windows 下可以使用计划任务

## 报 Tesseract OCR 错误

确保 PHP 可以读写脚本目录下的`captcha.jpg`，且已经正确安装了 Tesseract OCR 主程序与任意语言包（如`tesseract-data-eng`）

Linux 用户请确保在 shell 中可以通过`tesseract`正常运行 Tesseract OCR，Windows 用户请将 Tesseract OCR 程序加入 PATH 变量

## 为什么是 PHP？

欢迎移植

# 本项目使用了

- [guzzle/guzzle](https://github.com/guzzle/guzzle)
- [thiagoalessio/tesseract-ocr-for-php](https://github.com/thiagoalessio/tesseract-ocr-for-php)

# 开源许可

The GNU General Public License v2.0
