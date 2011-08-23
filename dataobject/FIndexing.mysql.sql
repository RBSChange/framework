CREATE TABLE IF NOT EXISTS `f_indexing` (
 `document_id` int(11) NOT NULL,
 `indexing_status` enum('TO_INDEX', 'INDEXED') NOT NULL DEFAULT 'TO_INDEX',
 `lastupdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`document_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci