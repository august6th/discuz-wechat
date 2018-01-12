CREATE TABLE IF NOT EXISTS dz20_common_member_wechat_sign (
  `uid` mediumint(8) unsigned NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`uid`)
) ENGINE=MYISAM;