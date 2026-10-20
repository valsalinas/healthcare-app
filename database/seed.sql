USE scratch_test;

INSERT INTO patients (patient_id, first_name, last_name, email, phone) VALUES
  (1, 'John',   'Doe',     'john@example.com',   '210-555-1002'),
  (2, 'James',  'Smith',   'james@example.com',  '210-555-1003'),
  (3, 'Sarah',  'Johnson', 'sarah@example.com',  '210-555-1004'),
  (4, 'Maria',  'Smith',   'maria.smith@example.com', '210-555-1010'),
  (5, 'Olivia', 'Garcia',  'olivia.garcia@example.com', '210-555-1014');

INSERT INTO doctors (doctor_id, first_name, last_name, specialty) VALUES
  (1, 'Alex',   'Morgan', 'Family Medicine'),
  (2, 'Taylor', 'Reed',   'Cardiology'),
  (3, 'Jordan', 'Lee',    'Pediatrics'),
  (4, 'Casey',  'Patel',  'Dermatology'),
  (5, 'Riley',  'Brooks', 'Internal Medicine');

INSERT INTO appointments (patient_id, doctor_id, appointment_datetime, reason, status) VALUES
  (1, 1, '2026-11-10 10:00:00', 'Routine checkup', 'scheduled'),
  (2, 2, '2026-11-12 14:30:00', 'Routine checkup', 'scheduled');