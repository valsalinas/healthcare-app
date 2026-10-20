# Healthcare App

A full stack patient and appointment management app, built as a portfolio project to practice frontend, backend, and database development. All data is fictional.

## Features

- **Patients:** search by ID or name (first or last), add, edit, and delete with confirmation
- **Doctors:** list providers and their specialties
- **Appointments:** view appointments with patient and doctor details; the database prevents double-booking a doctor at the same time
- **Validation and feedback:** required-field checks and success/error messages

## Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | React Native, Expo, Expo Router, TypeScript |
| Backend | PHP 8.2 REST-style API using PDO |
| Database | MySQL 8.0 |

## Architecture

React Native app → HTTP/JSON → PHP API → PDO → MySQL

## Screenshots

[Add 2 or 3 screenshots here: patient search, patient form, appointments list]

## Setup

### Prerequisites
Node.js, PHP 8.2+, MySQL 8.0, and Git.

### Start the backend
```bash
cd backend
php -S 0.0.0.0:8000
```

### Start the frontend
```bash
cd mobile
npm install
npx expo start
```
When testing from a phone or another device, set the API base URL in the frontend to your computer's current local IP address.

## Roadmap

- [ ] Appointment booking and editing from the app
- [ ] Doctor availability
- [ ] Authentication and patient accounts
- [ ] Automated tests and CI/CD
- [ ] Caching
- [ ] Deployment

## Note

This is a learning project and is not intended for real patient data.