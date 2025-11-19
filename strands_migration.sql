-- Strands Migration for WESSH Database
-- This migration creates the strands table, inserts Philippine SHS strands,
-- alters the blocks table to add strand_id foreign key, and inserts sample blocks.

-- 1. Create strands table
CREATE TABLE IF NOT EXISTS strands (
    strand_id INT AUTO_INCREMENT PRIMARY KEY,
    strand_code VARCHAR(10) NOT NULL UNIQUE,
    strand_name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Insert official Philippine SHS strands
INSERT INTO strands (strand_code, strand_name, description) VALUES
('STEM', 'Science, Technology, Engineering and Mathematics', 'Focuses on science, technology, engineering, and mathematics subjects.'),
('ABM', 'Accountancy, Business and Management', 'Prepares students for business and management careers.'),
('HUMSS', 'Humanities and Social Sciences', 'Emphasizes humanities, social sciences, and communication skills.'),
('GAS', 'General Academic Strand', 'Provides a general academic foundation for various fields.'),
('TVL-ICT', 'Technical-Vocational Livelihood - Information and Communications Technology', 'Specializes in ICT and computer-related skills.'),
('TVL-HE', 'Technical-Vocational Livelihood - Home Economics', 'Focuses on home economics and related vocational skills.'),
('TVL-IA', 'Technical-Vocational Livelihood - Industrial Arts', 'Covers industrial arts and technical skills.'),
('SPORTS', 'Sports Track', 'Designed for students interested in sports and physical education.'),
('ARTS', 'Arts and Design Track', 'Focuses on arts, design, and creative expression.')
ON DUPLICATE KEY UPDATE strand_name = VALUES(strand_name), description = VALUES(description);

-- 3. Alter blocks table to add strand_id foreign key
ALTER TABLE blocks ADD COLUMN strand_id INT AFTER strand;
ALTER TABLE blocks ADD CONSTRAINT fk_blocks_strand_id FOREIGN KEY (strand_id) REFERENCES strands(strand_id) ON DELETE SET NULL;

-- 4. Insert sample blocks for Grades 11 and 12 under each strand
-- STEM blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Einstein', 'Grade 11', 'STEM', (SELECT strand_id FROM strands WHERE strand_code = 'STEM'), 40),
('Curie', 'Grade 11', 'STEM', (SELECT strand_id FROM strands WHERE strand_code = 'STEM'), 40),
('Tesla', 'Grade 12', 'STEM', (SELECT strand_id FROM strands WHERE strand_code = 'STEM'), 40),
('Newton', 'Grade 12', 'STEM', (SELECT strand_id FROM strands WHERE strand_code = 'STEM'), 40);

-- ABM blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Rizal', 'Grade 11', 'ABM', (SELECT strand_id FROM strands WHERE strand_code = 'ABM'), 40),
('Bonifacio', 'Grade 11', 'ABM', (SELECT strand_id FROM strands WHERE strand_code = 'ABM'), 40),
('Mabini', 'Grade 12', 'ABM', (SELECT strand_id FROM strands WHERE strand_code = 'ABM'), 40),
('Del Pilar', 'Grade 12', 'ABM', (SELECT strand_id FROM strands WHERE strand_code = 'ABM'), 40);

-- HUMSS blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Plato', 'Grade 11', 'HUMSS', (SELECT strand_id FROM strands WHERE strand_code = 'HUMSS'), 40),
('Aristotle', 'Grade 11', 'HUMSS', (SELECT strand_id FROM strands WHERE strand_code = 'HUMSS'), 40),
('Socrates', 'Grade 12', 'HUMSS', (SELECT strand_id FROM strands WHERE strand_code = 'HUMSS'), 40),
('Confucius', 'Grade 12', 'HUMSS', (SELECT strand_id FROM strands WHERE strand_code = 'HUMSS'), 40);

-- GAS blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Galileo', 'Grade 11', 'GAS', (SELECT strand_id FROM strands WHERE strand_code = 'GAS'), 40),
('Copernicus', 'Grade 11', 'GAS', (SELECT strand_id FROM strands WHERE strand_code = 'GAS'), 40),
('Kepler', 'Grade 12', 'GAS', (SELECT strand_id FROM strands WHERE strand_code = 'GAS'), 40),
('Brahe', 'Grade 12', 'GAS', (SELECT strand_id FROM strands WHERE strand_code = 'GAS'), 40);

-- TVL-ICT blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Turing', 'Grade 11', 'TVL-ICT', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-ICT'), 40),
('Jobs', 'Grade 11', 'TVL-ICT', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-ICT'), 40),
('Gates', 'Grade 12', 'TVL-ICT', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-ICT'), 40),
('Zuckerberg', 'Grade 12', 'TVL-ICT', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-ICT'), 40);

-- TVL-HE blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Ferran', 'Grade 11', 'TVL-HE', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-HE'), 40),
('Child', 'Grade 11', 'TVL-HE', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-HE'), 40),
('Escoffier', 'Grade 12', 'TVL-HE', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-HE'), 40),
('Olney', 'Grade 12', 'TVL-HE', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-HE'), 40);

-- TVL-IA blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Wright', 'Grade 11', 'TVL-IA', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-IA'), 40),
('Edison', 'Grade 11', 'TVL-IA', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-IA'), 40),
('Bell', 'Grade 12', 'TVL-IA', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-IA'), 40),
('Ford', 'Grade 12', 'TVL-IA', (SELECT strand_id FROM strands WHERE strand_code = 'TVL-IA'), 40);

-- SPORTS blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Bolt', 'Grade 11', 'SPORTS', (SELECT strand_id FROM strands WHERE strand_code = 'SPORTS'), 40),
('Phelps', 'Grade 11', 'SPORTS', (SELECT strand_id FROM strands WHERE strand_code = 'SPORTS'), 40),
('Serena', 'Grade 12', 'SPORTS', (SELECT strand_id FROM strands WHERE strand_code = 'SPORTS'), 40),
('Jordan', 'Grade 12', 'SPORTS', (SELECT strand_id FROM strands WHERE strand_code = 'SPORTS'), 40);

-- ARTS blocks
INSERT INTO blocks (block_name, grade_level, strand, strand_id, max_students) VALUES
('Da Vinci', 'Grade 11', 'ARTS', (SELECT strand_id FROM strands WHERE strand_code = 'ARTS'), 40),
('Michelangelo', 'Grade 11', 'ARTS', (SELECT strand_id FROM strands WHERE strand_code = 'ARTS'), 40),
('Picasso', 'Grade 12', 'ARTS', (SELECT strand_id FROM strands WHERE strand_code = 'ARTS'), 40),
('Van Gogh', 'Grade 12', 'ARTS', (SELECT strand_id FROM strands WHERE strand_code = 'ARTS'), 40);
