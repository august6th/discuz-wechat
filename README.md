# Discuz 论坛结合微信公众号开发

> 1. 在微信公众号实现绑定论坛账号
> 2. 实现在微信公众号中，领取论坛的金币红包（后期结合更多活动展开）

### 环境说明

* Discuz 论坛
* PHP 5.2
* IIS 2008

可以看到，PHP 版本极低，对开发造成极大的阻碍，许多现存的 SDK 无法进行使用，例如 [Easywechat][link_1] ，但该 SDK 兼容低版本的 3.0.0 也需要 PHP 5.6 以上。

但是，好在 Discuz 自带的 “微信登陆” 插件，存在已经实现好的对微信公众号开发的封装，得益于此，开发可以很顺利的进行。

详细代码，参考 Discuz 源码下 */source/plugin/wechat* 目录下的代码，封装的类库为 ***wechat.lib.class.php***

[link_1]: https://www.easywechat.com/  "优雅的微信公众号开发实现"

### 需求分析

需要实现以下两个功能

1. 在微信公众号实现绑定论坛账号
2. 实现在微信公众号中，领取论坛的金币红包（后期结合更多活动展开）

流程总结：

1. 微信公众号点击 “论坛红包” （判断是否绑定账号）
   1. 是：触发红包事件（判断用户是否已经领取过红包）
      1. 是：返回（已经领取，并不能重复领取）
      2. 否：领取红包，并提示跳转到论坛积分页面进行查看（将签到用户的 uid, 红包数值，保存在数据表***common_member_wechat_sign*** 中，设计每日任务，00 : 00 事件点，执行清除任务。）
      3. 彩蛋：可以添加后台设置，用户可以领取，一个以上的红包，并提示用户有多少红包还可以进行领取，适用于活动日。
   2. 否：提示用户进行账号绑定（跳转绑定页面）
      1. 传递用户的 openid
      2. 检查 openid 的存在以及可用性
      3. 用户输入用户名和密码提交绑定验证（以下为验证内容）
         1. 跨站请求验证（防止跨站提交）
         2. 用户名和密码不能为空，且格式正确（前端 - 后台，两次验证）
         3. 用户名是否存在
            1. 是：验证密码是否正确
               1. 是：绑定成功！！！（将获得的 uid, openid 保存在数据表 ***common_member_wechat***）
               2. 否：返回错误信息（密码不正确）
            2. 否：返回错误信息（用户名不存在） 
2. 后台关于红包的设置
   1. 设置红包随机数的最小值和最大值 ，不设置默认为 [1, 6]
   2. 设置今日的红包领取数目，不设置默认为 1

### GBK 与 UTF-8

开发过程中，最繁琐的就是格式的转换，首先论坛的默认格式为 GBK，然而微信公众号的输出格式为 UTF-8，所以需要你注意一下几个地方的格式转换。

涉及到的两个方法 ```diconv()```  ```convertToUtf()``` ，他们的原型都是 PHP 函数 ```iconv()``` ，请查阅相关文档。

1. 脚本中的中文，直接输出到微信公众号
2. 账号绑定的表单，提交的中文字符集格式，以及接受内容的脚本格式
3. 账号绑定的表单的运行环境，手机端微信浏览器，和电脑端微信浏览器，是有可能出现不同的乱码情况的。


### 开发流程（先看补充部分）

1. 配置好我们的论坛后台设置，关联 "中国模具论坛" 微信公众号的 Appid 以及 Appsecret （这个需要结合好，我们的微信公众号设置）

2. 添加 OAS 的积分类型，便于记录下论坛签到的积分变动 （***注意，代码中要提前设置好你红包的积分类型。***）

   1. 后台添加 OAS 积分变动类型：*source/admincp/admincp_logs.php*  316

   ```php
   $operationlist = array('TRC', 'RTC', 'RAC', 'MRC', 'TFR', 'RCV', 'CEC', 'ECU', 'SAC', 'BAC', 'PRC', 'RSC', 'STC', 'BTC', 'AFD', 'UGP', 'RPC', 'ACC', 'RCT', 'RCA', 'RCB', 'CDC', 'RKC', 'BME', 'RPR', 'RPZ', 'OAS');

   $rdata = array(
   	'task' => array('TRC', 'OAS'),
   	'thread' => array('RTC', 'RAC', 'STC', 'BTC', 'ACC', 'RCT', 'RCA', 'RCB'),
   	'member' => array('TFR', 'RCV', 'CEC', 'ECU', 'AFD', 'CDC', 'RKC', 'RPR', 'RPZ'),
   	'attach' => array('BAC', 'SAC'),
   	'magic' => array('MRC', 'BGC', 'RGC', 'AGC', 'BMC'),
   	'medal' => array('BME'),
   	'post' => array('PRC', 'RSC'),
   	'usergroup' => array('UGP'),
   	'report' => array('RPC'),
   );
   ```

   2. 前台利用 OAS 积分变动类型：*source/include/spacecp/spacecp_credit.php*

   ```php
   case 'OAS':
       $log['opinfo'] = lang('spacecp', 'wechat_sign');
       break;
   ```

   3. 接下来就需要添加前后台的语言包： *source/language/lang_spacecp.php*

   ```php
   'logs_credit_update_INDEX' => array('TRC','RTC','RAC','MRC','BMC','TFR','RCV','CEC','ECU','SAC','BAC','PRC','RSC','STC','BTC','AFD','UGP','RPC','ACC','RCT','RCA','RCB','CDC','RGC','BGC','AGC','RKC','BME','RPR','RPZ','FCP','BGC','OAS'),
   'logs_credit_update_OAS' => '公众号签到',
   'wechat_sign' => '公众号签到奖励',
   ```

3. 新增数据表  ***common_member_wechat_sign*** 用于存储签到数据

   同时添加该表的操作文件，即 *source/plugin/wechat/table/table_common_member_wechat_sign.php*

   ```sql
   CREATE TABLE IF NOT EXISTS dz20_common_member_wechat_sign (
     `uid` mediumint(8) unsigned NOT NULL,
     `status` tinyint(1) NOT NULL DEFAULT 0,
     PRIMARY KEY (`uid`)
   ) ENGINE=MYISAM;
   ```

4. 新增实现红包发送的关键逻辑

   直接将本目录下的 *source/plugin/wechat/wechat.lib.class.php* 覆盖原论坛源码即可。

5. 绑定账号页面的开发

   1. API 入口新增一个加载内容 wechat/bind

   ```php
   $modarray = array(
       'js' => 'javascript/javascript',
       'ad' => 'javascript/advertisement',
       'bind' => 'wechat/bind',
   );

   $mod = !empty($_GET['mod']) ? $_GET['mod'] : '';
   if(empty($mod) || !in_array($mod, array('js', 'ad', 'bind'))) {
   	exit('Access Denied');
   }
   ```

   2. 编辑绑定页的 PHP 脚本

   添加 *api/wechat* 文件夹下的所有脚本

   3. 编辑绑定页的 HTML 脚本
      1. 添加 “中国模具论坛”  的 logo 图片，放到 *static/image/common* 下
      2. 添加 *template/kouei_template/api* 下的所有文件
      3. 添加 *static/js/extend_common/wechat_css* 下的所有文件

6. 添加每日更新脚本（签到重新开始）

   这里我添加到以前的一个计划任务中 *source/include/cron/cron_sort_block_daily.php*

   本目录下文件，覆盖一下就完成了。

### 补充

>这是正式上线论坛之后的补充部分。

1. 微信自带的插件中，你的  ***common_member_wechat*** 表需要新增一个 ```fetch_by_uid``` 的方法。

2. 绑定页面的语言包和图片别忘了

   *source/language/lang_wechat_bind.php*

   *static/image/common/mould500.png*

3. 新增快捷绑定，用户在登陆状态下，可以实现快捷绑定微信公众号，也可以选择使用其他账号进行绑定。

4. 新增公众号绑定的积分变动类型 -- OAB，同 OAS 的建立，同时，后台语言包需要添加对应的内容，lang_admincp.php