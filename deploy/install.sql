CREATE TABLE `app_updates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `kind` varchar(12) NOT NULL,
  `from_version` varchar(20) DEFAULT NULL,
  `to_version` varchar(20) DEFAULT NULL,
  `status` varchar(14) NOT NULL,
  `backup_file` varchar(120) DEFAULT NULL,
  `log` text DEFAULT NULL,
  `user_id` int(11) unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `blog_posts` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `locale` varchar(10) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `title` varchar(200) NOT NULL,
  `excerpt` varchar(300) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `published_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locale_slug` (`locale`,`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `coupons` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(40) NOT NULL,
  `type` varchar(10) NOT NULL,
  `value` int(11) unsigned NOT NULL,
  `currency` varchar(3) DEFAULT NULL,
  `formats` varchar(120) DEFAULT NULL,
  `max_uses` int(11) unsigned NOT NULL DEFAULT 0,
  `user_id` int(11) unsigned DEFAULT NULL,
  `source` varchar(20) NOT NULL DEFAULT 'admin',
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `ref_user_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `event_types` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(60) NOT NULL,
  `names` text DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `event_types` VALUES (1,'wedding','{\"en\":\"Wedding\",\"hi\":\"शादी\",\"bn\":\"বিয়ে\",\"mr\":\"लग्न\",\"te\":\"పెళ్లి\",\"ta\":\"திருமணம்\",\"gu\":\"લગ્ન\",\"kn\":\"ಮದುವೆ\",\"ml\":\"വിവാഹം\",\"pa\":\"ਵਿਆਹ\",\"or\":\"ବିବାହ\",\"ur\":\"شادی\",\"as\":\"বিবাহ\",\"es\":\"Boda\",\"fr\":\"Mariage\",\"de\":\"Hochzeit\",\"pt\":\"Casamento\",\"ar\":\"زفاف\"}',0,1,'2026-10-17 17:18:48',NULL);
INSERT INTO `event_types` VALUES (2,'birthday','{\"en\":\"Birthday\",\"hi\":\"जन्मदिन\",\"bn\":\"জন্মদিন\",\"mr\":\"वाढदिवस\",\"te\":\"పుట్టినరోజు\",\"ta\":\"பிறந்தநாள்\",\"gu\":\"જન્મદિવસ\",\"kn\":\"ಹುಟ್ಟುಹಬ್ಬ\",\"ml\":\"ജന്മദിനം\",\"pa\":\"ਜਨਮ ਦਿਨ\",\"or\":\"ଜନ୍ମଦିନ\",\"ur\":\"سالگرہ\",\"as\":\"জন্মদিন\",\"es\":\"Cumpleaños\",\"fr\":\"Anniversaire\",\"de\":\"Geburtstag\",\"pt\":\"Aniversário\",\"ar\":\"عيد ميلاد\"}',1,1,'2026-10-17 17:18:48',NULL);
INSERT INTO `event_types` VALUES (3,'baby-shower','{\"en\":\"Baby Shower\",\"hi\":\"गोद भराई\",\"bn\":\"বেবি শাওয়ার\",\"mr\":\"डोहाळे जेवण\",\"te\":\"శ్రీమంతం\",\"ta\":\"வளைகாப்பு\",\"gu\":\"સીમંત\",\"kn\":\"ಸೀಮಂತ\",\"ml\":\"ബേബി ഷവർ\",\"pa\":\"ਬੇਬੀ ਸ਼ਾਵਰ\",\"or\":\"ସାଧଭକ୍ଷଣ\",\"ur\":\"بیبی شاور\",\"as\":\"বেবী শ্বাৱাৰ\",\"es\":\"Baby shower\",\"fr\":\"Baby shower\",\"de\":\"Babyparty\",\"pt\":\"Chá de bebê\",\"ar\":\"حفل استقبال المولود\"}',2,1,'2026-10-17 17:18:48',NULL);
CREATE TABLE `field_definitions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_type_id` int(11) unsigned NOT NULL,
  `field_key` varchar(60) NOT NULL,
  `field_type` varchar(30) NOT NULL,
  `labels` text DEFAULT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `options` text DEFAULT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_type_id_field_key` (`event_type_id`,`field_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `guests` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `invite_id` int(11) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(190) DEFAULT NULL,
  `token` varchar(24) NOT NULL,
  `opened_at` datetime DEFAULT NULL,
  `last_reminded_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `invite_id` (`invite_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invite_assets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `invite_id` int(11) unsigned NOT NULL,
  `kind` varchar(20) NOT NULL,
  `path` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invite_id` (`invite_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invites` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `template_id` int(11) unsigned NOT NULL,
  `region_id` int(11) unsigned NOT NULL,
  `short_code` varchar(12) DEFAULT NULL,
  `locale` varchar(10) NOT NULL,
  `content` text DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `edit_token` varchar(40) DEFAULT NULL,
  `format` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `short_code` (`short_code`),
  UNIQUE KEY `invites_edit_token` (`edit_token`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `languages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(60) NOT NULL,
  `native` varchar(60) NOT NULL,
  `is_rtl` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `font_family` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `languages` VALUES (1,'en','English','English',0,1,'2026-10-17 17:18:48',NULL,'Noto Sans');
INSERT INTO `languages` VALUES (2,'hi','Hindi','हिन्दी',0,1,'2026-10-17 17:18:48',NULL,'Noto Sans Devanagari');
INSERT INTO `languages` VALUES (3,'bn','Bengali','বাংলা',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Bengali');
INSERT INTO `languages` VALUES (4,'mr','Marathi','मराठी',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Devanagari');
INSERT INTO `languages` VALUES (5,'te','Telugu','తెలుగు',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Telugu');
INSERT INTO `languages` VALUES (6,'ta','Tamil','தமிழ்',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Tamil');
INSERT INTO `languages` VALUES (7,'gu','Gujarati','ગુજરાતી',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Gujarati');
INSERT INTO `languages` VALUES (8,'kn','Kannada','ಕನ್ನಡ',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Kannada');
INSERT INTO `languages` VALUES (9,'ml','Malayalam','മലയാളം',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Malayalam');
INSERT INTO `languages` VALUES (10,'pa','Punjabi','ਪੰਜਾਬੀ',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Gurmukhi');
INSERT INTO `languages` VALUES (11,'or','Odia','ଓଡ଼ିଆ',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Oriya');
INSERT INTO `languages` VALUES (12,'ur','Urdu','اردو',1,0,'2026-10-17 17:18:48',NULL,'Noto Nastaliq Urdu');
INSERT INTO `languages` VALUES (13,'as','Assamese','অসমীয়া',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans Bengali');
INSERT INTO `languages` VALUES (14,'es','Spanish','Español',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans');
INSERT INTO `languages` VALUES (15,'fr','French','Français',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans');
INSERT INTO `languages` VALUES (16,'de','German','Deutsch',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans');
INSERT INTO `languages` VALUES (17,'pt','Portuguese','Português',0,0,'2026-10-17 17:18:48',NULL,'Noto Sans');
INSERT INTO `languages` VALUES (18,'ar','Arabic','العربية',1,0,'2026-10-17 17:18:48',NULL,'Noto Sans Arabic');
CREATE TABLE `migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) NOT NULL,
  `class` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `namespace` varchar(255) NOT NULL,
  `time` int(11) NOT NULL,
  `batch` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` VALUES (1,'2026-10-17-000001','App\\Database\\Migrations\\CreateCoreTables','default','App',1792257527,1);
INSERT INTO `migrations` VALUES (2,'2026-10-17-000002','App\\Database\\Migrations\\AddInviteEditToken','default','App',1792257527,1);
INSERT INTO `migrations` VALUES (3,'2026-10-17-000003','App\\Database\\Migrations\\AllowOrdersWithoutInvite','default','App',1792257527,1);
INSERT INTO `migrations` VALUES (4,'2026-10-17-000004','App\\Database\\Migrations\\AddLanguageFont','default','App',1792257527,1);
INSERT INTO `migrations` VALUES (5,'2026-10-17-000005','App\\Database\\Migrations\\CreateVideoJobs','default','App',1792257527,1);
INSERT INTO `migrations` VALUES (6,'2026-10-17-000006','App\\Database\\Migrations\\GrowthTables','default','App',1792257528,1);
INSERT INTO `migrations` VALUES (7,'2026-10-17-000007','App\\Database\\Migrations\\CouponRefUser','default','App',1792257528,1);
INSERT INTO `migrations` VALUES (8,'2026-10-17-000008','App\\Database\\Migrations\\CreatePasswordResets','default','App',1792257528,1);
INSERT INTO `migrations` VALUES (9,'2026-10-17-000009','App\\Database\\Migrations\\CreateAppUpdates','default','App',1792257528,1);
CREATE TABLE `orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned DEFAULT NULL,
  `invite_id` int(11) unsigned DEFAULT NULL,
  `template_format_id` int(11) unsigned NOT NULL,
  `currency` varchar(3) NOT NULL,
  `amount_minor` int(11) unsigned NOT NULL,
  `status` varchar(20) NOT NULL,
  `coupon_code` varchar(40) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `access_count` int(11) unsigned DEFAULT 0,
  `discount_minor` int(11) unsigned DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `invite_id` (`invite_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `password_resets` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(11) unsigned NOT NULL,
  `driver` varchar(30) NOT NULL,
  `provider_ref` varchar(120) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `raw` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `region_languages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `region_id` int(11) unsigned NOT NULL,
  `language_id` int(11) unsigned NOT NULL,
  `sort` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `region_id_language_id` (`region_id`,`language_id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `region_languages` VALUES (1,1,1,0);
INSERT INTO `region_languages` VALUES (2,1,2,1);
INSERT INTO `region_languages` VALUES (3,1,3,2);
INSERT INTO `region_languages` VALUES (4,1,4,3);
INSERT INTO `region_languages` VALUES (5,1,5,4);
INSERT INTO `region_languages` VALUES (6,1,6,5);
INSERT INTO `region_languages` VALUES (7,1,7,6);
INSERT INTO `region_languages` VALUES (8,1,8,7);
INSERT INTO `region_languages` VALUES (9,1,9,8);
INSERT INTO `region_languages` VALUES (10,1,10,9);
INSERT INTO `region_languages` VALUES (11,1,11,10);
INSERT INTO `region_languages` VALUES (12,1,12,11);
INSERT INTO `region_languages` VALUES (13,1,13,12);
INSERT INTO `region_languages` VALUES (14,2,1,0);
INSERT INTO `region_languages` VALUES (15,2,14,1);
INSERT INTO `region_languages` VALUES (16,2,15,2);
INSERT INTO `region_languages` VALUES (17,2,16,3);
INSERT INTO `region_languages` VALUES (18,2,17,4);
INSERT INTO `region_languages` VALUES (19,2,18,5);
CREATE TABLE `regions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `domain` varchar(190) NOT NULL,
  `currency` varchar(3) NOT NULL,
  `default_locale` varchar(10) NOT NULL,
  `payment_driver` varchar(30) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  UNIQUE KEY `domain` (`domain`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `regions` VALUES (1,'in','inviteandvows.in','INR','en','razorpay',1,'2026-10-17 17:18:48',NULL);
INSERT INTO `regions` VALUES (2,'com','inviteandvows.com','USD','en','razorpay',1,'2026-10-17 17:18:48',NULL);
CREATE TABLE `rsvps` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `invite_id` int(11) unsigned NOT NULL,
  `guest_name` varchar(120) NOT NULL,
  `contact` varchar(120) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `guests` int(11) NOT NULL DEFAULT 1,
  `message` text DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `guest_id` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invite_id` (`invite_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `template_formats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` int(11) unsigned NOT NULL,
  `region_id` int(11) unsigned NOT NULL,
  `format` varchar(30) NOT NULL,
  `price_minor` int(11) unsigned NOT NULL,
  `delivery_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `template_id_region_id_format` (`template_id`,`region_id`,`format`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `templates` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `event_type_id` int(11) unsigned NOT NULL,
  `slug` varchar(120) NOT NULL,
  `names` text DEFAULT NULL,
  `preview_image` varchar(255) DEFAULT NULL,
  `view_file` varchar(190) DEFAULT NULL,
  `config` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `event_type_id` (`event_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `role` varchar(20) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `referral_code` varchar(12) DEFAULT NULL,
  `referred_by` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `users_referral_code` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `video_jobs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `invite_id` int(11) unsigned NOT NULL,
  `status` varchar(20) NOT NULL,
  `attempts` int(11) unsigned NOT NULL DEFAULT 0,
  `file_path` varchar(255) DEFAULT NULL,
  `error` text DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `finished_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status_id` (`status`,`id`),
  KEY `invite_id` (`invite_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



