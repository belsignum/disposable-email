#
# Table structure for table 'tx_disposableemail_list'
#
CREATE TABLE tx_disposableemail_list (
	uid int(11) NOT NULL auto_increment,

	domain varchar(255),
	provider_type varchar(32) DEFAULT '' NOT NULL,

	PRIMARY KEY (uid),
	UNIQUE KEY uniq_domain_provider_type (domain, provider_type)
);

#
# Table structure for table 'tx_disposableemail_domain_rule'
# Editorial allow (whitelist) and deny (blacklist) entries, maintained in the backend module.
#
CREATE TABLE tx_disposableemail_domain_rule (
	domain varchar(255) DEFAULT '' NOT NULL,
	rule_type varchar(16) DEFAULT 'allow' NOT NULL,
	note text,

	KEY domain (domain)
);
