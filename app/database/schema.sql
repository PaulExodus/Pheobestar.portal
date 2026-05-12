-- Phoebestar Royalty Schools - Complete Database Schema
-- Role-Based School Management System

CREATE DATABASE IF NOT EXISTS phoebestar_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE phoebestar_db;

-- ============================================
-- CORE USER MANAGEMENT & RBAC
-- ============================================

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_label VARCHAR(50) NOT NULL,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO roles (role_name, role_label, description) VALUES
('admin', 'Administrator', 'Full system access and control'),
('proprietor', 'Proprietor', 'School owner with oversight access'),
('director', 'Director', 'Strategic planning and oversight'),
('bursar', 'Bursar', 'Financial management and fee collection'),
('principal', 'Principal', 'Academic and disciplinary oversight'),
('vice_principal', 'Vice Principal', 'Assists principal in daily operations'),
('teacher', 'Teacher', 'Classroom management and grading'),
('student', 'Student', 'Access to learning materials and results'),
('parent', 'Parent', 'Track ward performance and communicate');

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL DEFAULT 8,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    gender ENUM('Male', 'Female') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    address TEXT,
    city VARCHAR(100) DEFAULT 'Osogbo',
    state VARCHAR(100) DEFAULT 'Osun State',
    passport_photo VARCHAR(255) DEFAULT NULL,
    barcode VARCHAR(100) UNIQUE,
    id_card_number VARCHAR(50) UNIQUE,
    status ENUM('active', 'inactive', 'suspended', 'graduated') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT
);

CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    admission_number VARCHAR(50) UNIQUE NOT NULL,
    class_id INT,
    section ENUM('Creche', 'Nursery', 'Basic', 'Secondary', 'Entrepreneurship') NOT NULL,
    sub_class VARCHAR(50),
    house VARCHAR(50),
    day_boarding ENUM('Day', 'Boarding') DEFAULT 'Day',
    admission_date DATE,
    parent_id INT,
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    guardian_email VARCHAR(150),
    guardian_address TEXT,
    previous_school VARCHAR(200),
    health_info TEXT,
    behavioral_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE teachers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    employee_number VARCHAR(50) UNIQUE,
    qualification VARCHAR(200),
    specialization VARCHAR(200),
    department VARCHAR(100),
    date_joined DATE,
    employment_type ENUM('Full-time', 'Part-time', 'Contract') DEFAULT 'Full-time',
    subjects JSON,
    classes_assigned JSON,
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    employee_number VARCHAR(50) UNIQUE,
    department VARCHAR(100),
    position VARCHAR(100),
    date_joined DATE,
    employment_type ENUM('Full-time', 'Part-time', 'Contract') DEFAULT 'Full-time',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- ACADEMIC STRUCTURE
-- ============================================

CREATE TABLE academic_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_name VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    status ENUM('upcoming', 'active', 'completed') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE terms (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id INT NOT NULL,
    term_name ENUM('First Term', 'Second Term', 'Third Term') NOT NULL,
    start_date DATE,
    end_date DATE,
    is_current BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE
);

CREATE TABLE classes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_name VARCHAR(50) NOT NULL,
    section ENUM('Creche', 'Nursery', 'Basic', 'Secondary', 'Entrepreneurship') NOT NULL,
    level INT,
    class_teacher_id INT,
    capacity INT DEFAULT 30,
    room VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject_name VARCHAR(100) NOT NULL,
    subject_code VARCHAR(20),
    category ENUM('Core', 'Elective', 'Vocational', 'Extra-curricular') DEFAULT 'Core',
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE class_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    teacher_id INT,
    UNIQUE KEY unique_class_subject (class_id, subject_id),
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

-- ============================================
-- RESULTS & BROADSHEET SYSTEM
-- ============================================

CREATE TABLE grading_scheme (
    id INT PRIMARY KEY AUTO_INCREMENT,
    min_score INT NOT NULL,
    max_score INT NOT NULL,
    grade VARCHAR(5) NOT NULL,
    remark VARCHAR(50) NOT NULL,
    grade_point DECIMAL(3,1) DEFAULT 0.0,
    session_id INT,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE SET NULL
);

INSERT INTO grading_scheme (min_score, max_score, grade, remark, grade_point) VALUES
(75, 100, 'A1', 'Excellent', 5.0),
(70, 74, 'B2', 'Very Good', 4.5),
(65, 69, 'B3', 'Good', 4.0),
(60, 64, 'C4', 'Credit', 3.5),
(55, 59, 'C5', 'Credit', 3.0),
(50, 54, 'C6', 'Credit', 2.5),
(45, 49, 'D7', 'Pass', 2.0),
(40, 44, 'E8', 'Pass', 1.5),
(0, 39, 'F9', 'Fail', 0.0);

CREATE TABLE assessments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject_id INT NOT NULL,
    class_id INT NOT NULL,
    session_id INT NOT NULL,
    term_id INT NOT NULL,
    ca_score DECIMAL(5,2) DEFAULT 0,
    exam_score DECIMAL(5,2) DEFAULT 0,
    total_score DECIMAL(5,2) DEFAULT 0,
    grade VARCHAR(5),
    remark VARCHAR(50),
    teacher_id INT,
    class_teacher_remark TEXT,
    principal_remark TEXT,
    position_in_class INT,
    position_in_subject INT,
    is_locked BOOLEAN DEFAULT FALSE,
    lock_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE behavioral_assessment (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    term_id INT NOT NULL,
    punctuality INT DEFAULT 5,
    attendance INT DEFAULT 5,
    attentiveness INT DEFAULT 5,
    neatness INT DEFAULT 5,
    politeness INT DEFAULT 5,
    obedience INT DEFAULT 5,
    cooperation INT DEFAULT 5,
    leadership INT DEFAULT 5,
    honesty INT DEFAULT 5,
    self_control INT DEFAULT 5,
    initiative INT DEFAULT 5,
    perseverance INT DEFAULT 5,
    social_skills INT DEFAULT 5,
    sports_arts INT DEFAULT 5,
    class_teacher_comment TEXT,
    principal_comment TEXT,
    total_score INT DEFAULT 0,
    average_score DECIMAL(4,2) DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE
);

CREATE TABLE report_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    term_id INT NOT NULL,
    class_id INT NOT NULL,
    pdf_path VARCHAR(255),
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'published', 'printed') DEFAULT 'draft',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- FEES & FINANCIAL CONTROL
-- ============================================

CREATE TABLE fee_categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_mandatory BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO fee_categories (category_name, description) VALUES
('Tuition Fee', 'Core academic instruction fee'),
('Development Levy', 'Infrastructure development contribution'),
('Examination Fee', 'Internal and external examination charges'),
('PTA Levy', 'Parent-Teacher Association dues'),
('Sports Fee', 'Sports and physical education'),
('ICT Fee', 'Computer and technology access'),
('Library Fee', 'Library resources and materials'),
('Medical Fee', 'Health services and first aid'),
('Boarding Fee', 'Hostel accommodation and meals'),
('Uniform Fee', 'School uniforms and sportswear'),
('Transportation Fee', 'School bus service'),
('Extra-curricular Fee', 'Clubs and special activities');

CREATE TABLE fee_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    class_id INT,
    section ENUM('Creche', 'Nursery', 'Basic', 'Secondary', 'Entrepreneurship'),
    day_boarding ENUM('Day', 'Boarding'),
    amount DECIMAL(12,2) NOT NULL,
    session_id INT NOT NULL,
    term_id INT,
    due_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES fee_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL
);

CREATE TABLE student_fees (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    amount_due DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) DEFAULT 0,
    scholarship_amount DECIMAL(12,2) DEFAULT 0,
    status ENUM('pending', 'partial', 'paid', 'overdue', 'waived') DEFAULT 'pending',
    payment_deadline DATE,
    lock_results BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE CASCADE
);

CREATE TABLE fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_fee_id INT NOT NULL,
    amount_paid DECIMAL(12,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'POS', 'Paystack', 'Flutterwave', 'School Account') NOT NULL,
    transaction_reference VARCHAR(100),
    payment_gateway_reference VARCHAR(100),
    bank_name VARCHAR(100),
    account_name VARCHAR(200),
    account_number VARCHAR(50),
    teller_number VARCHAR(50),
    payment_date DATE NOT NULL,
    processed_by INT,
    notes TEXT,
    receipt_number VARCHAR(50) UNIQUE,
    status ENUM('pending', 'confirmed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_fee_id) REFERENCES student_fees(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- CBT EXAMS & E-LEARNING
-- ============================================

CREATE TABLE cbt_exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_title VARCHAR(200) NOT NULL,
    subject_id INT,
    class_id INT,
    session_id INT,
    term_id INT,
    teacher_id INT,
    duration_minutes INT NOT NULL DEFAULT 60,
    total_questions INT NOT NULL DEFAULT 50,
    total_marks DECIMAL(6,2) DEFAULT 100,
    pass_mark DECIMAL(5,2) DEFAULT 40,
    instructions TEXT,
    start_time DATETIME,
    end_time DATETIME,
    shuffle_questions BOOLEAN DEFAULT TRUE,
    shuffle_options BOOLEAN DEFAULT TRUE,
    show_result_immediately BOOLEAN DEFAULT TRUE,
    allow_multiple_attempts BOOLEAN DEFAULT FALSE,
    max_attempts INT DEFAULT 1,
    is_published BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'published', 'ongoing', 'completed', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL
);

CREATE TABLE cbt_questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'fill_blank', 'theory') DEFAULT 'multiple_choice',
    option_a TEXT,
    option_b TEXT,
    option_c TEXT,
    option_d TEXT,
    option_e TEXT,
    correct_answer VARCHAR(10),
    correct_answer_text TEXT,
    marks DECIMAL(5,2) DEFAULT 1,
    explanation TEXT,
    question_image VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES cbt_exams(id) ON DELETE CASCADE
);

CREATE TABLE cbt_student_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    attempt_number INT DEFAULT 1,
    answers JSON,
    score DECIMAL(6,2) DEFAULT 0,
    percentage DECIMAL(5,2) DEFAULT 0,
    grade VARCHAR(5),
    remark VARCHAR(50),
    time_spent_seconds INT DEFAULT 0,
    started_at DATETIME,
    submitted_at DATETIME,
    ip_address VARCHAR(45),
    status ENUM('in_progress', 'submitted', 'graded', 'timed_out') DEFAULT 'in_progress',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES cbt_exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- ============================================
-- ASSIGNMENTS & TASKS
-- ============================================

CREATE TABLE assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    subject_id INT,
    class_id INT NOT NULL,
    teacher_id INT NOT NULL,
    session_id INT,
    term_id INT,
    total_marks DECIMAL(5,2) DEFAULT 100,
    due_date DATE,
    attachment VARCHAR(255),
    assignment_type ENUM('homework', 'classwork', 'project', 'test') DEFAULT 'homework',
    status ENUM('draft', 'published', 'closed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE SET NULL
);

CREATE TABLE assignment_submissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    assignment_id INT NOT NULL,
    student_id INT NOT NULL,
    submission_text TEXT,
    attachment VARCHAR(255),
    marks_obtained DECIMAL(5,2),
    grade VARCHAR(5),
    remark VARCHAR(255),
    feedback TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    graded_by INT,
    graded_at DATETIME,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- DIGITAL ADMISSIONS
-- ============================================

CREATE TABLE admission_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    other_names VARCHAR(100),
    gender ENUM('Male', 'Female') NOT NULL,
    date_of_birth DATE NOT NULL,
    place_of_birth VARCHAR(100),
    nationality VARCHAR(50) DEFAULT 'Nigerian',
    state_of_origin VARCHAR(50),
    lga VARCHAR(100),
    religion ENUM('Christianity', 'Islam', 'Traditional', 'Other'),
    email VARCHAR(150),
    phone VARCHAR(20),
    address TEXT NOT NULL,
    city VARCHAR(100),
    state VARCHAR(100),
    section_applied ENUM('Creche', 'Nursery', 'Basic', 'Secondary', 'Entrepreneurship') NOT NULL,
    class_applied VARCHAR(50),
    day_boarding ENUM('Day', 'Boarding') DEFAULT 'Day',
    parent_name VARCHAR(200) NOT NULL,
    parent_phone VARCHAR(20) NOT NULL,
    parent_email VARCHAR(150),
    parent_occupation VARCHAR(100),
    parent_address TEXT,
    previous_school VARCHAR(200),
    previous_class VARCHAR(50),
    reason_for_transfer TEXT,
    passport_photo VARCHAR(255),
    birth_certificate VARCHAR(255),
    last_report_sheet VARCHAR(255),
    transfer_certificate VARCHAR(255),
    exam_score DECIMAL(5,2),
    exam_date DATE,
    status ENUM('pending', 'exam_scheduled', 'exam_taken', 'accepted', 'rejected', 'enrolled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================
-- ATTENDANCE SYSTEM
-- ============================================

CREATE TABLE attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    user_type ENUM('student', 'teacher', 'staff') NOT NULL,
    class_id INT,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    check_in TIME,
    check_out TIME,
    marked_by INT,
    barcode_scanned BOOLEAN DEFAULT FALSE,
    notes VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- COMMUNICATION HUB
-- ============================================

CREATE TABLE blog_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE,
    excerpt TEXT,
    content LONGTEXT NOT NULL,
    featured_image VARCHAR(255),
    category ENUM('Academic', 'Event', 'Admission', 'Achievement', 'General') DEFAULT 'General',
    author_id INT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at DATETIME,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE newsletters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    subject VARCHAR(200) NOT NULL,
    content LONGTEXT NOT NULL,
    sent_by INT,
    sent_at TIMESTAMP,
    recipient_count INT DEFAULT 0,
    status ENUM('draft', 'sent') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE newsletter_subscribers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(150) NOT NULL UNIQUE,
    name VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================
-- PRIVATE & COMMUNITY CHAT
-- ============================================

CREATE TABLE chat_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_type ENUM('private', 'group') DEFAULT 'private',
    group_name VARCHAR(200),
    group_description TEXT,
    group_icon VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE chat_participants (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    user_id INT NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_read_at TIMESTAMP,
    UNIQUE KEY unique_participant (conversation_id, user_id),
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE chat_messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_type ENUM('text', 'image', 'file', 'voice') DEFAULT 'text',
    content TEXT NOT NULL,
    file_url VARCHAR(255),
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- DIGITAL NOTICE BOARD
-- ============================================

CREATE TABLE notices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('general', 'academic', 'sports', 'event', 'fee', 'exam', 'urgent') DEFAULT 'general',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    target_roles JSON,
    target_classes JSON,
    attachment VARCHAR(255),
    posted_by INT,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    publish_at DATETIME,
    expire_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE user_notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    notice_id INT,
    title VARCHAR(200),
    message TEXT,
    type ENUM('notice', 'result', 'fee', 'message', 'assignment', 'exam', 'general') DEFAULT 'general',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE SET NULL
);

-- ============================================
-- GALLERY
-- ============================================

CREATE TABLE gallery (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200),
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    file_type ENUM('image', 'video') DEFAULT 'image',
    category ENUM('All', 'Photos', 'Events', 'Sports', 'Academics', 'Facilities', 'Graduation') DEFAULT 'Photos',
    uploaded_by INT,
    is_featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- ACADEMIC VAULT (E-NOTES, VIDEOS, EXAM PREP)
-- ============================================

CREATE TABLE e_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    subject_id INT,
    class_id INT,
    topic VARCHAR(200),
    sub_topic VARCHAR(200),
    content LONGTEXT,
    file_path VARCHAR(255),
    file_type ENUM('pdf', 'doc', 'ppt', 'text', 'link') DEFAULT 'pdf',
    curriculum_standard ENUM('NERDC', 'WAEC', 'NECO', 'JAMB', 'BECE', 'IELTS', 'IJMB', 'TOEFL', 'JUPEB', 'General') DEFAULT 'NERDC',
    uploaded_by INT,
    download_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE video_lessons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    subject_id INT,
    class_id INT,
    topic VARCHAR(200),
    video_url VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255),
    duration VARCHAR(20),
    curriculum_standard ENUM('NERDC', 'WAEC', 'NECO', 'JAMB', 'BECE', 'IELTS', 'IJMB', 'TOEFL', 'JUPEB', 'General') DEFAULT 'NERDC',
    uploaded_by INT,
    view_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE exam_prep (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    exam_type ENUM('BECE', 'WAEC', 'NECO', 'JAMB', 'IELTS', 'IJMB', 'TOEFL', 'JUPEB', 'Mock') NOT NULL,
    subject_id INT,
    year INT,
    file_path VARCHAR(255),
    file_type ENUM('pdf', 'doc', 'image') DEFAULT 'pdf',
    answer_key_path VARCHAR(255),
    uploaded_by INT,
    download_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- EDUBOT AI CONVERSATIONS
-- ============================================

CREATE TABLE edubot_conversations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_id VARCHAR(100),
    message TEXT NOT NULL,
    response TEXT,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================
-- SYSTEM SETTINGS & LOGS
-- ============================================

CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO system_settings (setting_key, setting_value, setting_group) VALUES
('school_name', 'Phoebestar Royalty Schools', 'general'),
('school_motto', 'Nurturing Kingship', 'general'),
('school_address', 'Plot M3 & M5 School Avenue, By Ring Road, Osogbo, Osun State. P.M.B. 4375, Osogbo.', 'general'),
('school_phone', '08102552066, 08023762899', 'general'),
('school_email', 'phoebestarschools@gmail.com', 'general'),
('school_website', 'www.phoebestarroyaltyschools.sch.ng', 'general'),
('current_session', '2024/2025', 'academic'),
('current_term', 'Second Term', 'academic'),
('payment_gateway', 'paystack', 'payment'),
('paystack_public_key', '', 'payment'),
('paystack_secret_key', '', 'payment'),
('flutterwave_public_key', '', 'payment'),
('flutterwave_secret_key', '', 'payment'),
('school_account_name', '', 'payment'),
('school_account_number', '', 'payment'),
('school_bank_name', '', 'payment'),
('enable_fee_lock', '1', 'payment'),
('result_publish_date', '', 'academic'),
('admission_open', '1', 'admission'),
('maintenance_mode', '0', 'system');

CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(200) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================
-- SAMPLE DATA
-- ============================================

-- Sample Admin User (password: Admin@123)
INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, status) 
VALUES (1, 'System', 'Administrator', 'admin@phoebestar.sch.ng', '08102552066', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');

-- Sample academic session
INSERT INTO academic_sessions (session_name, start_date, end_date, is_current, status) 
VALUES ('2024/2025', '2024-09-01', '2025-07-31', TRUE, 'active');

INSERT INTO terms (session_id, term_name, start_date, end_date, is_current) 
VALUES (1, 'First Term', '2024-09-01', '2024-12-20', FALSE),
       (1, 'Second Term', '2025-01-06', '2025-04-15', TRUE),
       (1, 'Third Term', '2025-04-22', '2025-07-31', FALSE);

-- Sample classes
INSERT INTO classes (class_name, section, level, capacity) VALUES
('Pre-Nursery', 'Creche', 1, 20),
('Nursery 1', 'Nursery', 1, 25),
('Nursery 2', 'Nursery', 2, 25),
('Primary 1', 'Basic', 1, 30),
('Primary 2', 'Basic', 2, 30),
('Primary 3', 'Basic', 3, 30),
('Primary 4', 'Basic', 4, 30),
('Primary 5', 'Basic', 5, 30),
('Primary 6', 'Basic', 6, 30),
('JSS 1', 'Secondary', 7, 35),
('JSS 2', 'Secondary', 8, 35),
('JSS 3', 'Secondary', 9, 35),
('SS 1', 'Secondary', 10, 35),
('SS 2', 'Secondary', 11, 35),
('SS 3', 'Secondary', 12, 35);

-- Sample subjects
INSERT INTO subjects (subject_name, subject_code, category) VALUES
('Mathematics', 'MTH', 'Core'),
('English Language', 'ENG', 'Core'),
('Physics', 'PHY', 'Core'),
('Chemistry', 'CHM', 'Core'),
('Biology', 'BIO', 'Core'),
('Agricultural Science', 'AGR', 'Elective'),
('Economics', 'ECO', 'Elective'),
('Government', 'GOV', 'Elective'),
('Literature in English', 'LIT', 'Elective'),
('Christian Religious Studies', 'CRS', 'Core'),
(' Civic Education', 'CIV', 'Core'),
('Computer Studies', 'COM', 'Core'),
('Fine Arts', 'ART', 'Elective'),
('Music', 'MUS', 'Elective'),
('Physical Education', 'PED', 'Extra-curricular'),
('French Language', 'FRE', 'Elective'),
('Hausa Language', 'HAU', 'Elective'),
('Igbo Language', 'IGB', 'Elective'),
('Yoruba Language', 'YOR', 'Elective'),
('Further Mathematics', 'FMA', 'Elective'),
('Technical Drawing', 'TDR', 'Elective'),
('Food and Nutrition', 'FAN', 'Elective'),
('Home Economics', 'HEC', 'Elective'),
('Commerce', 'COM', 'Elective'),
('Geography', 'GEO', 'Elective'),
('History', 'HIS', 'Elective'),
('Principle of Accounts', 'POA', 'Elective'),
('Shorthand', 'SHN', 'Elective'),
('Entrepreneurship', 'ENT', 'Vocational'),
('Marketing', 'MKT', 'Vocational');

-- Sample blog posts
INSERT INTO blog_posts (title, slug, excerpt, content, category, author_id, status, published_at) VALUES
('2025 WAEC Results: 100% Pass Rate Achieved', '2025-waec-results', 'Our SS3 students have once again demonstrated academic excellence with a perfect pass rate in this year WAEC examinations.', '<h2>Outstanding Achievement</h2><p>Phoebestar Royalty Schools is proud to announce that all our SS3 students passed the 2025 WAEC examinations with flying colors. This remarkable achievement is a testament to the dedication of our students, the hard work of our teachers, and the supportive learning environment we provide.</p><h3>Key Highlights</h3><ul><li>100% pass rate in all subjects</li><li>15 students scored distinctions in Mathematics</li><li>12 students achieved A1 in English Language</li><li>Overall best student: 8 A1s and 1 B2</li></ul><p>We congratulate all our students and wish them success in their future endeavors.</p>', 'Academic', 1, 'published', NOW()),
('Annual Inter-House Sports Competition', 'inter-house-sports-2025', 'Join us for a day of athletic excellence and house spirit at our annual sports competition.', '<h2>Inter-House Sports 2025</h2><p>The annual Inter-House Sports Competition is here again! This year event promises to be bigger and better, featuring various track and field events, team sports, and fun activities for the whole family.</p><h3>Event Details</h3><p><strong>Date:</strong> Saturday, 15th March 2025<br><strong>Time:</strong> 9:00 AM<br><strong>Venue:</strong> School Sports Complex</p><p>All parents, guardians, and well-wishers are invited to come and cheer for their favorite houses.</p>', 'Event', 1, 'published', NOW()),
('2025/2026 Admission Now Open', 'admission-2025-2026', 'Applications are invited from qualified candidates for all classes for the 2025/2026 academic session.', '<h2>Admission for 2025/2026 Academic Session</h2><p>Phoebestar Royalty Schools is now accepting applications for the 2025/2026 academic session. We offer quality education from Crèche to SS3, with both day and boarding options.</p><h3>How to Apply</h3><ol><li>Fill the online application form</li><li>Upload required documents</li><li>Pay the application fee</li><li>Schedule entrance examination</li></ol><p><strong>Application Deadline:</strong> 30th June 2025</p>', 'Admission', 1, 'published', NOW());

-- Sample notices
INSERT INTO notices (title, content, category, priority, target_roles, posted_by, status, publish_at) VALUES
('Resumption Date for Third Term', 'All students are expected to resume for the Third Term on Monday, 22nd April 2025. Boarding students should arrive between 2:00 PM and 6:00 PM on Sunday, 21st April 2025.', 'academic', 'high', '["student","parent"]', 1, 'published', NOW()),
('School Fees Payment Reminder', 'This is a gentle reminder that Second Term school fees are due. Please ensure payment is made before the end of term to avoid any inconvenience.', 'fee', 'medium', '["parent"]', 1, 'published', NOW()),
('Parent-Teacher Meeting', 'The Parent-Teacher Meeting for the Second Term will hold on Saturday, 5th April 2025 at 10:00 AM in the school hall. All parents are encouraged to attend.', 'general', 'medium', '["parent","teacher"]', 1, 'published', NOW());

-- Sample gallery items
INSERT INTO gallery (title, description, file_path, file_type, category, uploaded_by, is_featured) VALUES
('School Assembly', 'Morning assembly at the school quadrangle', 'assets/main-building.jpg', 'image', 'Photos', 1, TRUE),
('Science Laboratory', 'Students conducting experiments', 'assets/science-lab.jpg', 'image', 'Academics', 1, TRUE),
('ICT Center', 'Modern computer laboratory', 'assets/ict-center.jpg', 'image', 'Facilities', 1, TRUE),
('School Campus', 'Aerial view of the school campus', 'assets/campus-main.jpg', 'image', 'Facilities', 1, TRUE),
('Sports Day', 'Annual inter-house sports competition', 'assets/sports-field.jpg', 'image', 'Sports', 1, FALSE),
('School Library', 'Students reading in the library', 'assets/library.jpg', 'image', 'Academics', 1, FALSE),
('School Entrance', 'Main entrance gate', 'assets/campus-entrance.jpg', 'image', 'Photos', 1, FALSE),
('Graduation Ceremony', 'SS3 graduating class of 2024', 'assets/school-building.jpg', 'image', 'Photos', 1, FALSE);
