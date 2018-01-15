<?php
loadcore();
global $_G;
$lang = lang('api/wechat_bind');
if (!isset($_GET['openid'])) {
    showmessage("$lang[tips]", '/forum.php');
}
$openid = htmlspecialchars(trim($_GET['openid']));

require_once DISCUZ_ROOT . './source/plugin/wechat/wechat.lib.class.php';

/*
$token = $client->getAccessToken();
dd($token);
*/
// 初始化
$flag = false; // 判断是否通过绑定
$message = ''; // 未通过绑定时的错误提示
$mobile = checkmobile(); // 检查是否在手机端中运行, 来判断是否转码
$has_login = (isset($_GET['type']) && trim($_GET['type']) == 'other');
if (!$_G['uid'] || $has_login) {
// 验证用户提交绑定
    if (submitcheck('bindsubmit')) {
        if (!isset($_GET) || !$_GET['username'] || !$_GET['password'] || !$_GET['openid']) {
            showmessage("$lang[system_error_or_unfirendly_access]", "/api.php?mod=bind&openid=$openid");
        }
        // 验证绑定用户是否存在，以及密码是否正确。
        $username = (bool)$mobile ? dhtmlspecialchars(trim($_GET['username'])) : dhtmlspecialchars(trim(diconv($_GET['username'], 'UTF-8', CHARSET)));
        $password = trim($_GET['password']);
        loaducenter();
        $result = uc_user_login($username, $password);
        /*
        // 调试编码问题
        dd($username);
        dd($result);
        exit;
        */
        /*
         * 根据 $result[0] 的值，判断是否验证成功
         * 值 > 0 时，验证成功，且该值为用户的 uid
         * 值 = -1 时，验证失败，原因是用户名不存在
         * 值 = -2 时，验证失败，原因是密码不正确
         */
        if ($result[0] < 0) {
            switch ($result[0]) {
                case -1:
                    $message = $lang['username_not_register'];
                    break;
                case -2:
                    $message = $lang['password_not_corret'];
                    break;
                default:
                    showmessage("$lang[system_error_or_unfirendly_access]", "/api.php?mod=bind&openid=$openid");
                    break;
            }
        } else {
            $result = getResult($result[0], $openid, $lang);
            $result === true ? $flag = true : $message = $result;
        }
    }
    include_once template('api/wechat/bind');
} else {
    if (submitcheck('fastbindsubmit')) {
        $result = getResult($_G['uid'], $openid, $lang);
        $result === true ? $flag = true : $message = $result;
    }
    include_once template('api/wechat/fast_bind');
}

function getResult ($uid, $openid, $lang){
    global $_G;
    $_G['wechat']['setting'] = unserialize($_G['setting']['mobilewechat']);
    $client = new WeChatClient($_G['wechat']['setting']['wechat_appId'], $_G['wechat']['setting']['wechat_appsecret']);
    $uid_result = C::t("#wechat#common_member_wechat")->fetch_by_uid($uid);
    $openid_result = C::t("#wechat#common_member_wechat")->fetch_by_openid($openid);
    if (!empty($uid_result)) {
        return $lang['mould_has_been_binded'];
    } elseif (!empty($openid_result)) {
        return $lang['openid_has_been_binded'];
    } else {
        weChatHook::bindOpenId($uid, $openid);
        updatemembercount($uid, array('2' => '10'), true, '', 667, '', '绑定公众号', '首次绑定公众号获得奖励');
        $client->sendTextMsg($openid, urlencode(diconv("恭喜您，首次绑定成功！/::P\n\n获得 10 金钱，<a href='$_G[siteurl]'>请在论坛中查看</a>/:circle", CHARSET, 'UTF-8')));
        return true;
    }
}
?>