CREATE DATABASE IF NOT EXISTS citywatch;
USE citywatch;

CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    location VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some dummy data
INSERT INTO reports (type, location, description, status) VALUES 
('Garbage', 'North Market Gate', 'Heap of trash blocking road', 'Pending'),
('Water Leakage', 'Sector 4 Main Pipe', 'Clean water wasting', 'In Progress'),
('Pollution', 'Industrial Area', 'Black smoke from factory', 'Resolved');