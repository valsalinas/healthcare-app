CREATE DATABASE IF NOT EXISTS healthcare_app
  CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE healthcare_app;

CREATE TABLE IF NOT EXISTS patients (
  patient_id INT NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(50) NOT NULL,
  last_name  VARCHAR(50) NOT NULL,
  email      VARCHAR(100) NOT NULL,
  phone      VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (patient_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS doctors (
  doctor_id  INT NOT NULL AUTO_INCREMENT,
  first_name VARCHAR(50) NOT NULL,
  last_name  VARCHAR(50) NOT NULL,
  specialty  VARCHAR(100) NOT NULL,
  email      VARCHAR(100) DEFAULT NULL,
  phone      VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (doctor_id),
  UNIQUE KEY email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
  appointment_id       INT NOT NULL AUTO_INCREMENT,
  patient_id           INT NOT NULL,
  doctor_id            INT NOT NULL,
  appointment_datetime DATETIME NOT NULL,
  reason               VARCHAR(255) DEFAULT NULL,
  status ENUM('scheduled','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at           TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (appointment_id),
  UNIQUE KEY doctor_id (doctor_id, appointment_datetime),
  KEY patient_id (patient_id),
  CONSTRAINT appointments_ibfk_1 FOREIGN KEY (patient_id) REFERENCES patients (patient_id),
  CONSTRAINT appointments_ibfk_2 FOREIGN KEY (doctor_id)  REFERENCES doctors (doctor_id)
) ENGINE=InnoDB;