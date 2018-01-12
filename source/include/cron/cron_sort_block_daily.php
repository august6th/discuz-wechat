<?php

/**
 *      [Discuz!] (C)2001-2099 Comsenz Inc.
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: cron_magic_daily.php 24589 2011-09-27 07:45:55Z monkey $
 */

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

require_once libfile('function/cache');
$sort_result = C::t('forum_kouei_blockitem')->sort_by_bid();
$sort_block = array_column($sort_result, 'block_id');
savecache('sort_block_id', $sort_block);

C::t('#wechat#common_member_wechat_sign')->clear_all();

?>