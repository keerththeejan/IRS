-- Create table for pre-created users
CREATE TABLE IF NOT EXISTS `precreated_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `email` varchar(254) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_used` tinyint(1) NOT NULL DEFAULT '0',
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample pre-created users (optional)
-- INSERT INTO `precreated_users` (`username`, `email`, `full_name`, `department`, `position`) VALUES
-- ('john.doe', 'john.doe@example.com', 'John Doe', 'ICT', 'Lecturer'),
-- ('jane.smith', 'jane.smith@example.com', 'Jane Smith', 'Engineering', 'Senior Lecturer');
