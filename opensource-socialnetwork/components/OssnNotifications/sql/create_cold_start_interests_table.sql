-- Create the missing ossn_cold_start_interests table
CREATE TABLE IF NOT EXISTS `ossn_cold_start_interests` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `user_guid` bigint NOT NULL,
  `topics` longtext COLLATE utf8mb4_general_ci NOT NULL,
  `time_created` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_guid` (`user_guid`),
  KEY `time_created` (`time_created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
