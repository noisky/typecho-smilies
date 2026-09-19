# Typecho Smilies：百度贴吧泡泡表情插件

这是一个面向 Typecho 的评论表情插件，基于原版 Smilies 改造。
插件固定使用百度贴吧泡泡表情，不再提供 QQ、WordPress 或自定义表情集切换。

## 功能

- 评论框显示表情面板，点击表情即可在光标位置插入代码。
- 自动解析评论、文章、页面正文及摘要中的贴吧表情代码。
- 在文章和页面编辑器中提供表情面板。
- 内置 60 个普通尺寸 PNG 和 60 个 @2x 高清 PNG：面板使用高清图，正文使用普通图。
- 支持配置表情图片 CDN；未配置时使用插件本地资源。
- 表情面板始终展开，插入逻辑使用原生 JavaScript，不依赖 jQuery。
- 插入表情后保留编辑框和页面的滚动位置，避免焦点跳到文章底部。
- 对缺失、非字符串和不同表示形式的插件配置做容错处理。
- 对表情标记、图片文件名和资源 URL 进行 HTML/JavaScript 上下文转义。

## 环境与主题要求

- Typecho 依赖版本：14.10.10-*。
- 插件目录必须命名为 Smilies。
- 前台主题需要：
  - 在评论文本框附近调用 Smilies_Plugin::output()；
  - 加载 smilies.css，或提供自定义的 #smiliesbox、.face 和 .face img 样式；
  - 保留主题页脚中的 $this->footer()，用于输出表情插入脚本。

评论和正文的表情解析由插件钩子自动完成，不需要在主题中手动替换内容。

## 安装

1. 将插件目录复制到 Typecho 的 usr/plugins/Smilies/。
2. 确认 Plugin.php、README.md、smilies.css 和 paopao/ 位于该目录下。
3. 在后台进入“控制台 → 插件”，启用 Smilies。
4. 打开插件配置页，按需设置 CDN 地址、评论框 ID 和“正文使用表情”。
5. 按下面的说明完成主题接入。

## 主题接入

### 1. 加载样式

在主题的 head 区域加入：

~~~php
<link rel="stylesheet" href="<?php $this->options->pluginUrl('Smilies/smilies.css'); ?>">
~~~

插件不会自动向前台页面插入这份样式。后台文章和页面编辑器在启用“正文使用表情”后会自动加载。
如果主题已经内置等价的 #smiliesbox、.face 和 .face img 样式（例如当前 Material 主题），可以不重复加载；这些样式只负责面板外观，不参与表情代码解析或插入。

### 2. 输出评论表情面板

在主题 comments.php 的评论 textarea 后面加入：

~~~php
<?php if (\Typecho\Plugin::exists('Smilies') && class_exists('Smilies_Plugin')): ?>
    <?php Smilies_Plugin::output(); ?>
<?php endif; ?>
~~~

建议将代码放在评论文本框之后、提交按钮之前。直接调用前应保留插件和类存在判断，这样停用插件时主题仍能正常运行。

### 3. 保留页脚钩子

主题的 footer.php 应保留：

~~~php
<?php $this->footer(); ?>
~~~

插件通过该钩子在前台输出表情插入脚本。大多数 Typecho 主题已经包含这行，不需要重复添加。

## 配置说明

### 表情图片 CDN 地址

留空时使用当前站点的插件目录：

~~~text
/usr/plugins/Smilies/paopao/
~~~

填写 CDN 时，应填写对应 usr/plugins/ 目录的根地址，例如：

~~~text
https://cdn.example.com/usr/plugins/
~~~

插件会在该地址后追加 paopao/。CDN 需要保留原文件名、目录结构和 UTF-8 中文文件名，例如：

~~~text
https://cdn.example.com/usr/plugins/paopao/呵呵.png
https://cdn.example.com/usr/plugins/paopao/呵呵@2x.png
~~~

CDN 配置只改变表情图片地址，smilies.css 始终从当前 Typecho 站点加载。生产环境建议使用 HTTPS。

### 指定评论框 ID

默认值“一般无需填写”即可：

- 单页评论：自动使用页面中的第一个 textarea；
- 列表或非单页场景：默认使用 ID 为 text 的文本框。

如果主题有多个 textarea，或评论框 ID 不是 text，请填写实际的 textarea ID。点击表情没有插入内容时，优先检查此项。

### 正文使用表情

该选项默认开启：

- 开启：文章和页面编辑器显示表情面板，并解析文章、页面正文及摘要中的表情代码；
- 关闭：插件仍可用于评论表情，但不处理正文，也不显示编辑器表情面板。

## 表情代码

点击面板中的表情会插入以下代码。评论、文章和页面内容中的这些代码会被解析为图片：

~~~text
@(呵呵)     @(哈哈)     @(吐舌)     @(太开心)   @(笑眼)
@(花心)     @(小乖)     @(乖)       @(捂嘴笑)   @(滑稽)
@(你懂的)   @(不高兴)   @(怒)       @(汗)       @(黑线)
@(泪)       @(真棒)     @(喷)       @(惊哭)     @(阴险)
@(鄙视)     @(酷)       @(啊)       @(狂汗)     @(what)
@(疑问)     @(酸爽)     @(呀咩爹)   @(委屈)     @(惊讶)
@(睡觉)     @(笑尿)     @(挖鼻)     @(吐)       @(犀利)
@(小红脸)   @(懒得理)   @(勉强)     @(爱心)     @(心碎)
@(玫瑰)     @(礼物)     @(彩虹)     @(太阳)     @(星星月亮)
@(钱币)     @(茶杯)     @(蛋糕)     @(大拇指)   @(胜利)
@(OK)       @(沙发)     @(手纸)     @(香蕉)     @(便便)
@(药丸)     @(红领巾)   @(蜡烛)     @(音乐)     @(灯泡)
~~~

表情代码与图片采用固定映射。插件不支持原版的 :smile: 等字符表情，也不会自动扫描 paopao/ 之外的表情目录。

## 常见问题

### 面板显示但没有样式

确认主题已经在 head 中加载：

~~~php
<link rel="stylesheet" href="<?php $this->options->pluginUrl('Smilies/smilies.css'); ?>">
~~~

### 点击表情没有插入

依次检查：

1. 评论文本框附近是否调用了 Smilies_Plugin::output()；
2. 主题是否执行了 $this->footer()；
3. 插件配置中的评论框 ID 是否与实际 textarea 的 ID 一致；
4. 列表场景的目标文本框是否确实使用 ID text。

### 插入后编辑器滚动到底部

当前版本会在插入前保存编辑框和页面滚动位置，并在恢复光标后还原。旧版本直接修改 textarea 内容并重新获取焦点，浏览器可能会把编辑区滚动到光标位置。

如果更新后仍然跳动，请确认后台实际加载的是最新的 Plugin.php，并清理浏览器缓存。

### 图片返回 404

检查 CDN 根地址是否填写到了 usr/plugins/ 这一层，并确认 CDN 上存在 paopao/ 目录及普通、@2x 两套同名文件。中文文件名也必须保持不变。

### 正文没有转换

确认“正文使用表情”已开启。关闭该选项后，插件只处理评论内容。

## 从原版升级

本版本的资源和配置结构已经收敛为百度贴吧泡泡表情：

- 表情集固定为 paopao/，不再加载 QQ、WordPress 或其他旧资源；
- 不再提供表情风格切换、拖动排序、弹窗、jQuery 模式、最大宽度等旧功能；
- 配置项保留 CDN 地址、评论框 ID 和正文开关；
- 修复后台文章和页面编辑器插入表情后焦点或滚动位置跳动；
- 前台只保留 smilies.css，旧版 custom.css、custom.js 等资源不会被加载。

升级后请重新打开插件配置页确认选项，并检查主题是否已完成样式和表情面板接入。

## 文件结构

~~~text
Smilies/
├── Plugin.php       插件逻辑、表情映射、Typecho 钩子及输出方法
├── README.md        使用说明
├── smilies.css      表情面板样式
└── paopao/          普通尺寸和 @2x 高清表情图片
~~~

## 致谢

本插件基于原版 Typecho Smilies 插件改造，参考项目：[Typecho 表情插件](https://github.com/jzwalk/Smilies)。
