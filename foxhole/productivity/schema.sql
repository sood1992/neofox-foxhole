-- Foxhole Productivity Management Platform Schema (extended)

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','manager','employee') NOT NULL DEFAULT 'employee',
    title VARCHAR(120) DEFAULT NULL,
    weekly_capacity_minutes INT DEFAULT 2400,
    focus_color CHAR(7) DEFAULT '#5BC0BE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    industry VARCHAR(120) DEFAULT NULL,
    relationship_status ENUM('prospect','active','paused','closed') NOT NULL DEFAULT 'active',
    account_lead INT DEFAULT NULL,
    retainer_hours INT DEFAULT NULL,
    retainer_value DECIMAL(10,2) DEFAULT NULL,
    brand_vibe VARCHAR(160) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_lead) REFERENCES users(id)
);

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    description TEXT,
    status ENUM('not_started','in_progress','at_risk','complete') NOT NULL DEFAULT 'not_started',
    pipeline_stage ENUM('pitch','discovery','production','review','launch','retainer') NOT NULL DEFAULT 'production',
    health ENUM('thriving','steady','watch','critical') NOT NULL DEFAULT 'steady',
    priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    start_date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    manager_id INT DEFAULT NULL,
    client_id INT DEFAULT NULL,
    budget_hours INT DEFAULT NULL,
    budget_amount DECIMAL(10,2) DEFAULT NULL,
    creative_theme VARCHAR(160) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(id),
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

CREATE TABLE project_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE project_milestones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    title VARCHAR(160) NOT NULL,
    owner_id INT DEFAULT NULL,
    status ENUM('planned','in_progress','complete','blocked') NOT NULL DEFAULT 'planned',
    highlight VARCHAR(255) DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id)
);

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    assigned_to INT NOT NULL,
    title VARCHAR(160) NOT NULL,
    description TEXT,
    status ENUM('not_started','in_progress','blocked','complete') NOT NULL DEFAULT 'not_started',
    estimated_minutes INT DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE time_entries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    user_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME DEFAULT NULL,
    duration_minutes INT DEFAULT NULL,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE resource_capacity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    week_start DATE NOT NULL,
    planned_minutes INT NOT NULL,
    meeting_minutes INT DEFAULT 0,
    focus_theme VARCHAR(160) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_capacity (user_id, week_start),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE wellbeing_checkins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    energy_level TINYINT NOT NULL,
    mood ENUM('energized','steady','stretched','drained') NOT NULL,
    blockers TEXT,
    focus_goal VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE meeting_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    facilitator_id INT DEFAULT NULL,
    note_date DATE NOT NULL,
    summary TEXT NOT NULL,
    next_steps TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (facilitator_id) REFERENCES users(id)
);

CREATE TABLE idea_bank (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT DEFAULT NULL,
    owner_id INT DEFAULT NULL,
    title VARCHAR(160) NOT NULL,
    impact ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
    effort ENUM('light','moderate','heavy') NOT NULL DEFAULT 'moderate',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE freelancers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(160) NOT NULL,
    specialty VARCHAR(160) DEFAULT NULL,
    email VARCHAR(160) DEFAULT NULL,
    status ENUM('available','booked','cooldown') NOT NULL DEFAULT 'available',
    hourly_rate DECIMAL(8,2) DEFAULT NULL,
    location VARCHAR(120) DEFAULT NULL,
    preferred_workload INT DEFAULT NULL,
    available_from DATE DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE freelancer_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    freelancer_id INT NOT NULL,
    project_id INT NOT NULL,
    role VARCHAR(120) DEFAULT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    committed_hours INT DEFAULT NULL,
    FOREIGN KEY (freelancer_id) REFERENCES freelancers(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

CREATE TABLE invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    project_id INT DEFAULT NULL,
    issue_date DATE NOT NULL,
    due_date DATE DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('draft','sent','paid','overdue','void') NOT NULL DEFAULT 'sent',
    notes VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT DEFAULT NULL,
    incurred_date DATE NOT NULL,
    category ENUM('freelancer','production','software','travel','misc') NOT NULL DEFAULT 'misc',
    amount DECIMAL(10,2) NOT NULL,
    vendor VARCHAR(160) DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE SET NULL
);

CREATE TABLE alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hash CHAR(32) NOT NULL UNIQUE,
    category ENUM('deadline','budget','wellbeing','invoice','retainer') NOT NULL,
    message VARCHAR(255) NOT NULL,
    severity ENUM('info','watch','urgent') NOT NULL DEFAULT 'info',
    related_project_id INT DEFAULT NULL,
    related_user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (related_project_id) REFERENCES projects(id) ON DELETE SET NULL,
    FOREIGN KEY (related_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Seed admin, project manager, and employee demo users
INSERT INTO users (name, email, password_hash, role, title, weekly_capacity_minutes, focus_color) VALUES
('Ava Reynolds', 'admin@neofox.io', '$2y$12$sgMYT1C1KHYzr5bgo6oQAuoZmeLblmj.UQDWOSMCoHYmxlFCaiN2y', 'admin', 'CEO', 2400, '#FF8A65'),
('Milo Carter', 'pm@neofox.io', '$2y$12$sgMYT1C1KHYzr5bgo6oQAuoZmeLblmj.UQDWOSMCoHYmxlFCaiN2y', 'manager', 'Project Maestro', 2100, '#FFD166'),
('Riley Chen', 'employee@neofox.io', '$2y$12$sgMYT1C1KHYzr5bgo6oQAuoZmeLblmj.UQDWOSMCoHYmxlFCaiN2y', 'employee', 'Creative Strategist', 1980, '#06D6A0');

-- Password for demo users: foxhole2024 (override with FOXHOLE_DEMO_PASSWORD environment variable)

-- Seed sample clients
INSERT INTO clients (name, industry, relationship_status, account_lead, retainer_hours, retainer_value, brand_vibe) VALUES
('Lumen Labs', 'SaaS', 'active', 2, 120, 18500.00, 'Neon futurist'),
('Juniper & Co.', 'Lifestyle Retail', 'active', 2, 90, 14200.00, 'Calm minimal'),
('Orbit Snacks', 'CPG', 'prospect', 2, 60, 9800.00, 'Playful cosmic');

-- Seed resource capacity for the current week
INSERT INTO resource_capacity (user_id, week_start, planned_minutes, meeting_minutes, focus_theme) VALUES
(2, DATE_SUB(DATE(NOW()), INTERVAL WEEKDAY(NOW()) DAY), 2100, 540, 'Campaign Launch Sprint'),
(3, DATE_SUB(DATE(NOW()), INTERVAL WEEKDAY(NOW()) DAY), 1980, 420, 'Q4 Evergreen Refresh');

-- Seed a couple of wellbeing check-ins to power dashboards
INSERT INTO wellbeing_checkins (user_id, energy_level, mood, blockers, focus_goal, created_at) VALUES
(3, 8, 'energized', 'Awaiting brand assets from client', 'Ship ad concepts for Juniper', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(3, 5, 'steady', 'Need final CTA approval', 'Outline Orbit launch deck', NOW());

-- Seed demo projects and milestones
INSERT INTO projects (name, description, status, pipeline_stage, health, priority, start_date, due_date, manager_id, client_id, budget_hours, budget_amount, creative_theme)
VALUES
('Juniper Holiday Campaign', 'Seasonal omnichannel campaign with shoppable video', 'in_progress', 'production', 'steady', 'high', DATE_SUB(NOW(), INTERVAL 15 DAY), DATE_ADD(NOW(), INTERVAL 20 DAY), 2, 2, 320, 28000.00, 'Warm minimal'),
('Orbit Snacks TikTok Sprint', 'Rapid content sprint to boost organic reach', 'at_risk', 'production', 'watch', 'urgent', DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 10 DAY), 2, 3, 160, 9500.00, 'Cosmic playful');

INSERT INTO project_milestones (project_id, title, owner_id, status, highlight, due_date) VALUES
(1, 'Storyboard approval', 3, 'complete', 'Client approved revised storyboards.', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(1, 'Asset delivery', 3, 'in_progress', 'Waiting on product flat lays.', DATE_ADD(NOW(), INTERVAL 3 DAY)),
(2, 'Influencer shortlist', 3, 'in_progress', 'Need PM approval on final picks.', DATE_ADD(NOW(), INTERVAL 2 DAY));

-- Example tasks tied to demo projects
INSERT INTO tasks (project_id, assigned_to, title, description, status, estimated_minutes, due_date)
VALUES
(1, 3, 'Draft long-form sales page', 'Collaborate with copy to craft high-converting landing page.', 'in_progress', 420, DATE_ADD(NOW(), INTERVAL 14 DAY)),
(1, 3, 'Design paid social variants', 'Develop 5 motion-first creative options.', 'not_started', 360, DATE_ADD(NOW(), INTERVAL 7 DAY)),
(2, 3, 'Shoot lo-fi product teasers', 'Batch record 6 snackable TikTok clips.', 'in_progress', 300, DATE_ADD(NOW(), INTERVAL 5 DAY));

-- Idea bank entries keep creativity flowing
INSERT INTO idea_bank (project_id, owner_id, title, impact, effort, description)
VALUES
(1, 3, 'Holiday AR filter collab', 'high', 'moderate', 'Partner with Pinterest to launch interactive try-on filter.'),
(2, 3, 'Creator duet challenge', 'medium', 'light', 'Invite Orbit fans to remix the hero jingle for organic reach.');

-- Meeting notes sample data
INSERT INTO meeting_notes (project_id, facilitator_id, note_date, summary, next_steps)
VALUES
(1, 2, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Weekly sync covered ad sequencing and logistics.', 'Confirm inventory thresholds with client ops team.'),
(2, 2, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Creative jam to unblock TikTok ideation.', 'Schedule micro-shoot once prop list finalized.');

INSERT INTO freelancers (name, specialty, email, status, hourly_rate, location, preferred_workload, available_from, notes)
VALUES
('Harper Voss', 'Motion Design', 'harper@craftcollab.io', 'booked', 85.00, 'Toronto', 25, DATE_ADD(NOW(), INTERVAL 5 DAY), 'Loves kinetic typography.'),
('Santi Alvarez', 'Paid Media Buying', 'santi@adsculpt.com', 'available', 95.00, 'Remote - GMT-3', 30, DATE_SUB(NOW(), INTERVAL 2 DAY), 'Meta + TikTok certified.'),
('Mei Tanaka', 'Photographer', 'mei@studiozen.jp', 'cooldown', 120.00, 'Tokyo', 20, DATE_ADD(NOW(), INTERVAL 14 DAY), 'Available for remote edit support.');

INSERT INTO freelancer_assignments (freelancer_id, project_id, role, start_date, end_date, committed_hours)
VALUES
(1, 1, 'Lead animator', DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 12 DAY), 35),
(3, 2, 'Product photographer', DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 6 DAY), 18);

INSERT INTO invoices (client_id, project_id, issue_date, due_date, amount, status, notes)
VALUES
(2, 1, DATE_SUB(NOW(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), 14500.00, 'overdue', 'Waiting on AP remittance.'),
(2, 1, DATE_SUB(NOW(), INTERVAL 32 DAY), DATE_SUB(NOW(), INTERVAL 14 DAY), 13500.00, 'paid', 'Holiday kickoff milestone.'),
(3, 2, DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_ADD(NOW(), INTERVAL 8 DAY), 5200.00, 'sent', 'TikTok sprint deliverable.');

INSERT INTO expenses (project_id, incurred_date, category, amount, vendor, description)
VALUES
(1, DATE_SUB(NOW(), INTERVAL 4 DAY), 'production', 2100.00, 'Storyboard Studio', 'Illustration polish + layering'),
(2, DATE_SUB(NOW(), INTERVAL 2 DAY), 'freelancer', 1450.00, 'Orbit Creator Collective', 'Creator stipends wave 1'),
(2, DATE_SUB(NOW(), INTERVAL 9 DAY), 'software', 320.00, 'EditFlow', 'Pro license upgrade for sprint');

INSERT INTO alerts (hash, category, message, severity, related_project_id, related_user_id)
VALUES
(MD5('invoice|1|Juniper Holiday Campaign overdue'), 'invoice', 'Juniper Holiday Campaign invoice is overdue — nudge finance.', 'watch', 1, NULL),
(MD5('wellbeing|3|needs recharge'), 'wellbeing', 'Riley Chen reported energy 5/10 yesterday — schedule a focus reset.', 'info', NULL, 3);
