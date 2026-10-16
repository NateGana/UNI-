-- =====================================================================
-- CuyoTech University Student Services Information System (SSIS)
-- database/schema.sql  -  Stage 1: approved 11-table design (3NF)
--
-- WARNING: this file DROPS and re-creates the 11 SSIS tables, so
-- running it again erases their data. Run seed.php afterwards.
-- Designed for MySQL 8 / MariaDB 10.4+ (XAMPP). CHECK constraints are
-- enforced on MySQL 8.0.16+ and MariaDB 10.2.1+.
--
-- Rules handled by the application (NOT by this schema):
--   * payments.student_id must equal document_requests.student_id
--     when payments.request_id is present ({same student} rule)
--   * one clearance record per department for a student within a
--     clearance cycle (no cycle/term field exists in the approved model)
--   * the Paid / Unpaid / N/A indicator is DERIVED, never stored
--   * role checks on staff foreign keys (e.g. processed_by = Registrar)
-- Delete rules: only (1) grades are deleted with their enrollment and
-- (2) departments never cascade to department_staff are approved.
-- No other delete rule is defined, so MySQL's default (RESTRICT) applies.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS cuyotech_ssis
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE cuyotech_ssis;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS payments, document_requests, document_types, clearances,
                     grades, enrollments, courses, department_staff,
                     departments, students, users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- 1. users  (one table for all account types; role tells them apart)
-- ---------------------------------------------------------------------
CREATE TABLE users (
  user_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username       VARCHAR(50)  NOT NULL,
  password_hash  VARCHAR(255) NOT NULL,
  full_name      VARCHAR(100),
  email          VARCHAR(100),
  role           VARCHAR(20)  NOT NULL,
  account_status VARCHAR(10)  NOT NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_username (username),
  CONSTRAINT chk_users_role
    CHECK (role IN ('Student', 'Registrar', 'Cashier', 'Department Staff', 'Admin')),
  CONSTRAINT chk_users_account_status
    CHECK (account_status IN ('Active', 'Inactive'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. students  (required one-to-one link to users)
-- ---------------------------------------------------------------------
CREATE TABLE students (
  student_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED NOT NULL,
  PRIMARY KEY (student_id),
  UNIQUE KEY uq_students_user_id (user_id),
  CONSTRAINT fk_students_user
    FOREIGN KEY (user_id) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 3. departments
-- ---------------------------------------------------------------------
CREATE TABLE departments (
  department_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  department_name VARCHAR(100),
  PRIMARY KEY (department_id),
  UNIQUE KEY uq_departments_name (department_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 4. department_staff  (no cascade delete from departments)
-- ---------------------------------------------------------------------
CREATE TABLE department_staff (
  user_id       INT UNSIGNED NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id),
  CONSTRAINT fk_department_staff_user
    FOREIGN KEY (user_id) REFERENCES users (user_id),
  CONSTRAINT fk_department_staff_department
    FOREIGN KEY (department_id) REFERENCES departments (department_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 5. courses
-- ---------------------------------------------------------------------
CREATE TABLE courses (
  course_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  course_code VARCHAR(20),
  course_name VARCHAR(150),
  units       INT UNSIGNED,
  PRIMARY KEY (course_id),
  UNIQUE KEY uq_courses_code (course_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 6. enrollments  (NO unique on student_id + course_id, per D-9)
-- ---------------------------------------------------------------------
CREATE TABLE enrollments (
  enrollment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id    INT UNSIGNED NOT NULL,
  course_id     INT UNSIGNED NOT NULL,
  request_date  DATE,
  status        VARCHAR(10),
  decision_date DATE,
  decided_by    INT UNSIGNED NULL,
  PRIMARY KEY (enrollment_id),
  CONSTRAINT chk_enrollments_status
    CHECK (status IN ('Pending', 'Approved', 'Rejected')),
  CONSTRAINT fk_enrollments_student
    FOREIGN KEY (student_id) REFERENCES students (student_id),
  CONSTRAINT fk_enrollments_course
    FOREIGN KEY (course_id) REFERENCES courses (course_id),
  CONSTRAINT fk_enrollments_decided_by
    FOREIGN KEY (decided_by) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 7. grades  (composition: deleted together with its enrollment)
-- ---------------------------------------------------------------------
CREATE TABLE grades (
  grade_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  enrollment_id INT UNSIGNED NOT NULL,
  encoded_by    INT UNSIGNED NOT NULL,
  grade_value   VARCHAR(10),
  date_encoded  DATETIME,
  last_updated  DATETIME,
  PRIMARY KEY (grade_id),
  UNIQUE KEY uq_grades_enrollment (enrollment_id),
  CONSTRAINT fk_grades_enrollment
    FOREIGN KEY (enrollment_id) REFERENCES enrollments (enrollment_id)
    ON DELETE CASCADE,
  CONSTRAINT fk_grades_encoded_by
    FOREIGN KEY (encoded_by) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 8. clearances  (NO unique on student_id + department_id)
-- ---------------------------------------------------------------------
CREATE TABLE clearances (
  clearance_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id    INT UNSIGNED NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  request_date  DATE,
  status        VARCHAR(10),
  remarks       VARCHAR(255),
  decision_date DATE,
  decided_by    INT UNSIGNED NULL,
  PRIMARY KEY (clearance_id),
  CONSTRAINT chk_clearances_status
    CHECK (status IN ('Pending', 'Approved', 'Rejected')),
  CONSTRAINT fk_clearances_student
    FOREIGN KEY (student_id) REFERENCES students (student_id),
  CONSTRAINT fk_clearances_department
    FOREIGN KEY (department_id) REFERENCES departments (department_id),
  CONSTRAINT fk_clearances_decided_by
    FOREIGN KEY (decided_by) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 9. document_types  (reference rows: TOR, COR, Certification)
--    fee_required is only 'Yes' or 'No'. There is NO fee amount column.
-- ---------------------------------------------------------------------
CREATE TABLE document_types (
  document_type_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  type_name        VARCHAR(50),
  fee_required     VARCHAR(3),
  PRIMARY KEY (document_type_id),
  UNIQUE KEY uq_document_types_name (type_name),
  CONSTRAINT chk_document_types_fee
    CHECK (fee_required IN ('Yes', 'No'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 10. document_requests  (request_id is a plain unique number, no format)
-- ---------------------------------------------------------------------
CREATE TABLE document_requests (
  request_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  student_id       INT UNSIGNED NOT NULL,
  document_type_id INT UNSIGNED NOT NULL,
  purpose          VARCHAR(255) NOT NULL,
  status           VARCHAR(10),
  rejection_remark VARCHAR(255) NULL,
  request_date     DATE,
  processed_by     INT UNSIGNED NULL,
  last_updated     DATETIME,
  PRIMARY KEY (request_id),
  CONSTRAINT chk_document_requests_status
    CHECK (status IN ('Submitted', 'Processing', 'Ready', 'Released', 'Rejected')),
  CONSTRAINT fk_document_requests_student
    FOREIGN KEY (student_id) REFERENCES students (student_id),
  CONSTRAINT fk_document_requests_type
    FOREIGN KEY (document_type_id) REFERENCES document_types (document_type_id),
  CONSTRAINT fk_document_requests_processed_by
    FOREIGN KEY (processed_by) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 11. payments  (request_id is optional; one payment per request)
--     There is NO payment_status column (the indicator is derived).
-- ---------------------------------------------------------------------
CREATE TABLE payments (
  payment_id      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  student_id      INT UNSIGNED  NOT NULL,
  request_id      INT UNSIGNED  NULL,
  amount          DECIMAL(10,2),
  payment_date    DATE,
  payment_purpose VARCHAR(255),
  receipt_number  VARCHAR(50),
  recorded_by     INT UNSIGNED  NOT NULL,
  PRIMARY KEY (payment_id),
  UNIQUE KEY uq_payments_request (request_id),
  UNIQUE KEY uq_payments_receipt (receipt_number),
  CONSTRAINT fk_payments_student
    FOREIGN KEY (student_id) REFERENCES students (student_id),
  CONSTRAINT fk_payments_request
    FOREIGN KEY (request_id) REFERENCES document_requests (request_id),
  CONSTRAINT fk_payments_recorded_by
    FOREIGN KEY (recorded_by) REFERENCES users (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
