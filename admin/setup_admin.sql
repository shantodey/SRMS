-- Create admin table if not exists
CREATE TABLE IF NOT EXISTS `admin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert demo admin account
-- Username: admin@srms.edu
-- Password: admin123
-- Note: Password is hashed using PHP password_hash()
INSERT INTO `admin` (`name`, `email`, `password`) VALUES
('Administrator', 'admin@srms.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE
`name` = 'Administrator',
`password` = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Demo Account Credentials:
-- Email: admin@srms.edu
-- Password: admin123
