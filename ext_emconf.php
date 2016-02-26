<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "transactor_concardis".
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Transactor ConCardis Gateway',
	'description' => 'Provides the possibility to transact payments via ConCardis using the Payment Transactor extension.',
	'category' => 'misc',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'shy' => '',
	'dependencies' => 'transactor',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.2.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-5.6.99',
			'typo3' => '4.3.0-6.2.99',
			'transactor' => '0.1.2-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:18:{s:9:"ChangeLog";s:4:"b561";s:10:"README.txt";s:4:"ee2d";s:16:"contributors.txt";s:4:"62e1";s:21:"ext_conf_template.txt";s:4:"78cf";s:12:"ext_icon.gif";s:4:"a8da";s:17:"ext_localconf.php";s:4:"2307";s:13:"locallang.php";s:4:"5bc0";s:18:"paymentmethods.xml";s:4:"ffc4";s:14:"doc/manual.sxw";s:4:"8f89";s:46:"model/class.tx_transactorconcardis_gateway.php";s:4:"d45c";s:29:"model/include_error_codes.php";s:4:"4b62";s:30:"model/include_status_codes.php";s:4:"a98d";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v1_5.gif";s:4:"7f3b";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v1_6.gif";s:4:"c427";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v1_7.gif";s:4:"00cf";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v2_1.gif";s:4:"6551";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v2_2.gif";s:4:"b196";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v2_3.gif";s:4:"c52e";}',
	'suggests' => array(
	),
);

