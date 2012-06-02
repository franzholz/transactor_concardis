<?php

########################################################################
# Extension Manager/Repository config file for ext "transactor_concardis".
#
# Auto generated 17-02-2011 12:26
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

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
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.1',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
			'transactor' => '0.1.2-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:9:"ChangeLog";s:4:"1d78";s:10:"README.txt";s:4:"ee2d";s:21:"ext_conf_template.txt";s:4:"78cf";s:12:"ext_icon.gif";s:4:"a8da";s:17:"ext_localconf.php";s:4:"2307";s:13:"locallang.php";s:4:"5bc0";s:18:"paymentmethods.xml";s:4:"ffc4";s:14:"doc/manual.sxw";s:4:"12d1";s:46:"model/class.tx_transactorconcardis_gateway.php";s:4:"3d0d";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v1_5.gif";s:4:"7f3b";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v1_6.gif";s:4:"c427";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v1_7.gif";s:4:"00cf";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v2_1.gif";s:4:"6551";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v2_2.gif";s:4:"b196";s:50:"res/08-25-030_Banner_Kundenwebsite_234x60_v2_3.gif";s:4:"c52e";}',
	'suggests' => array(
	),
);

?>