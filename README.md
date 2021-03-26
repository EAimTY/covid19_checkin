某校健康信息系统打卡脚本

# 功能

用于替代手动健康信息系统打卡的 PHP 脚本
在脚本中设置学号与密码后，运行脚本进行健康信息系统打卡，输出打卡表单地址

# 运行需求

- PHP 7.0+: 脚本运行环境
- Tesseract OCR: 自动识别登录验证码
- PHP 可以读写脚本目录下的 `captcha.jpg`

# 使用

1. 准备 PHP 环境
2. 安装并测试 [Tesseract OCR](https://github.com/tesseract-ocr/tesseract)
3. 下载或 clone 本项目
4. 修改`checkin.php`开头处的学号与密码
5. 运行`checkin.php`进行打卡
6. 检查脚本输出的打卡表单地址，确保打卡已完成

# FAQ

## 如何实现每天自动打卡？

脚本本身没有自动运行功能，Linux 下可以使用 crontab、Windows 下可以使用计划任务实现自动化

## 脚本无法调用 Tesseract OCR

确保已经正确安装了 Tesseract OCR 主程序与任意语言包（如`tesseract-data-eng`）
Linux 用户请确保在 shell 中可以通过`tesseract`正常运行 Tesseract OCR，Windows 用户请将 Tesseract OCR 程序加入 PATH 变量

## 为什么是 PHP？

欢迎移植

# 本项目使用了

- [guzzle/guzzle](https://github.com/guzzle/guzzle)
- [thiagoalessio/tesseract-ocr-for-php](https://github.com/thiagoalessio/tesseract-ocr-for-php)

# 开源许可

The GNU General Public License v2.0
