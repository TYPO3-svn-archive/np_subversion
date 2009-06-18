#
# Table structure for table 'tx_npsubversion_repository'
#
CREATE TABLE tx_npsubversion_repository (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	rep_title tinytext NOT NULL,
	rep_url tinytext NOT NULL,
	rep_username tinytext NOT NULL,
	rep_password tinytext NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_npsubversion_workingcopy'
#
CREATE TABLE tx_npsubversion_workingcopy (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	wc_title tinytext NOT NULL,
	repository int(11) DEFAULT '0' NOT NULL,
	wc_url tinytext NOT NULL,
	wc_type int(11) DEFAULT '0' NOT NULL,
	wc_target_type int(11) DEFAULT '0' NOT NULL,
	wc_extension tinytext NOT NULL,
	wc_extension_type int(11) DEFAULT '0' NOT NULL,
	wc_path tinytext NOT NULL,
	wc_no_backup tinyint(3) DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);