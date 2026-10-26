import { useEffect, useState } from "react";
import { Button, ScrollView, Text, TextInput, View } from "react-native";

const API_BASE_URL = process.env.EXPO_PUBLIC_API_URL ?? "http://localhost:8000";

type Patient = {
  patient_id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone: string | null;
};

type Doctor = {
  doctor_id: number;
  first_name: string;
  last_name: string;
  specialty: string;
};

type Appointment = {
  appointment_id: number;
  patient_id: number;
  patient_name: string;
  doctor_id: number;
  doctor_name: string;
  specialty: string;
  appointment_datetime: string;
  reason: string | null;
  status: string;
};

// Returns parsed JSON, or throws an Error with the API's "error" message.
async function apiRequest(url: string, options?: RequestInit) {
  const response = await fetch(url, options);
  const data = await response.json().catch(() => null);

  if (!response.ok) {
    throw new Error(data?.error || `Server error: ${response.status}`);
  }

  return data;
}

function errorText(err: unknown, fallback: string) {
  return err instanceof Error ? err.message : fallback;
}

export default function HomeScreen() {
  const [patientId, setPatientId] = useState("");
  const [patientName, setPatientName] = useState("");
  const [patient, setPatient] = useState<Patient | null>(null);
  const [patients, setPatients] = useState<Patient[]>([]);
  const [error, setError] = useState("");
  const [successMessage, setSuccessMessage] = useState("");

  // Appointment state variables
  const [appointments, setAppointments] = useState<Appointment[]>([]);
  const [appointmentPatientId, setAppointmentPatientId] = useState("");
  const [appointmentDoctorId, setAppointmentDoctorId] = useState("");
  const [appointmentDate, setAppointmentDate] = useState("");
  const [appointmentTime, setAppointmentTime] = useState("");
  const [appointmentReason, setAppointmentReason] = useState("");
  const [appointmentError, setAppointmentError] = useState("");
  const [appointmentsLoadError, setAppointmentsLoadError] = useState("");
  const [appointmentMessage, setAppointmentMessage] = useState("");

  // Store the doctors retrieved from the PHP API
  const [doctors, setDoctors] = useState<Doctor[]>([]);

  //Add Patient Form Variables
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");

  //Update Patient Form Variables
  const [updatePatientId, setUpdatePatientId] = useState("");
  const [updateFirstName, setUpdateFirstName] = useState("");
  const [updateLastName, setUpdateLastName] = useState("");
  const [updateEmail, setUpdateEmail] = useState("");
  const [updatePhone, setUpdatePhone] = useState("");

  const [deletePatientId, setDeletePatientId] = useState("");

  const loadAppointments = async () => {
    try {
      const data = await apiRequest(`${API_BASE_URL}/appointments.php`);
      setAppointments(data);
      setAppointmentsLoadError("");
    } catch (err) {
      console.log("Load appointments failed:", err);
      setAppointmentsLoadError("Unable to load appointments.");
    }
  };

  const loadDoctors = async () => {
    try {
      const data = await apiRequest(`${API_BASE_URL}/doctors.php`);
      setDoctors(data);
    } catch (err) {
      console.log("Load doctors failed:", err);
    }
  };
  // Load appointments when the screen first opens
  useEffect(() => {
    loadAppointments();
    loadDoctors();
  }, []);

  const addPatient = async () => {
    setSuccessMessage("");

    if (firstName === "" || lastName === "" || email === "" || phone === "") {
      setError("Please fill in all patient fields.");
      return;
    }

    try {
      await apiRequest(`${API_BASE_URL}/patients.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          first_name: firstName,
          last_name: lastName,
          email: email,
          phone: phone,
        }),
      });

      setFirstName("");
      setLastName("");
      setEmail("");
      setPhone("");
      setError("");
      setSuccessMessage("Patient added successfully!");
    } catch (err) {
      setError(errorText(err, "Unable to add patient."));
      setSuccessMessage("");
    }
  };

  const updatePatient = async () => {
    if (
      updatePatientId === "" ||
      updateFirstName === "" ||
      updateLastName === "" ||
      updateEmail === "" ||
      updatePhone === ""
    ) {
      setError("Please fill in all update fields.");
      return;
    }

    try {
      await apiRequest(`${API_BASE_URL}/patients.php`, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          patient_id: updatePatientId,
          first_name: updateFirstName,
          last_name: updateLastName,
          email: updateEmail,
          phone: updatePhone,
        }),
      });

      setError("");
      setSuccessMessage("Patient updated successfully!");
      setUpdatePatientId("");
      setUpdateFirstName("");
      setUpdateLastName("");
      setUpdateEmail("");
      setUpdatePhone("");
    } catch (err) {
      setError(errorText(err, "Unable to update patient."));
      setSuccessMessage("");
    }
  };

  const deletePatient = async (idToDelete: number | string) => {
    if (String(idToDelete).trim() === "") {
      setError("Please enter a patient ID.");
      return;
    }

    try {
      await apiRequest(`${API_BASE_URL}/patients.php`, {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ patient_id: idToDelete }),
      });

      setError("");
      setSuccessMessage("Patient deleted successfully!");
      setPatients((current) =>
        current.filter((p) => String(p.patient_id) !== String(idToDelete)),
      );
      setPatient(null);
      setDeletePatientId("");
    } catch (err) {
      setSuccessMessage("");
      setError(errorText(err, "Unable to delete patient."));
    }
  };

  const searchPatient = async () => {
    setSuccessMessage("");

    try {
      if (patientId.trim() !== "") {
        const data = await apiRequest(
          `${API_BASE_URL}/patients.php?id=${encodeURIComponent(patientId.trim())}`,
        );
        setPatients([]);
        setPatient(data);
        setError("");
      } else if (patientName.trim() !== "") {
        const data: Patient[] = await apiRequest(
          `${API_BASE_URL}/patients.php?name=${encodeURIComponent(patientName.trim())}`,
        );
        setPatient(null);

        if (data.length === 0) {
          setPatients([]);
          setError("Patient not found");
        } else {
          setPatients(data);
          setError("");
        }
      } else {
        setPatient(null);
        setPatients([]);
        setError("Please enter a patient ID or name");
      }
    } catch (err) {
      setPatient(null);
      setPatients([]);
      setError(errorText(err, "Search failed."));
    }
  };

  // Schedule a new appointment
  const scheduleAppointment = async () => {
    if (
      !appointmentPatientId.trim() ||
      !appointmentDoctorId.trim() ||
      !appointmentDate.trim() ||
      !appointmentTime.trim()
    ) {
      setAppointmentError(
        "Please enter a patient ID, doctor ID, date, and time.",
      );
      setAppointmentMessage("");
      return;
    }

    const appointmentDatetime = `${appointmentDate.trim()} ${appointmentTime.trim()}:00`;

    try {
      const data = await apiRequest(`${API_BASE_URL}/appointments.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          patient_id: Number(appointmentPatientId),
          doctor_id: Number(appointmentDoctorId),
          appointment_datetime: appointmentDatetime,
          reason: appointmentReason.trim(),
        }),
      });

      setAppointmentMessage(data.message);
      setAppointmentError("");
      setAppointmentPatientId("");
      setAppointmentDoctorId("");
      setAppointmentDate("");
      setAppointmentTime("");
      setAppointmentReason("");
    } catch (err) {
      setAppointmentError(errorText(err, "Unable to schedule appointment."));
      setAppointmentMessage("");
      return;
    }

    await loadAppointments();
  };

  return (
    <ScrollView>
      <Text>Healthcare Operations</Text>

      {/* Search Patient Form */}
      <View
        style={{
          borderWidth: 1,
          padding: 20,
          marginBottom: 20,
          width: 350,
        }}
      >
        <Text>Search Patients</Text>
        <Text>Patient ID</Text>
        <TextInput
          placeholder="Enter patient ID"
          value={patientId}
          onChangeText={setPatientId}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />
        <Text>Patient Name</Text>
        <TextInput
          placeholder="Enter patient name"
          value={patientName}
          onChangeText={setPatientName}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />
        <Button title="Search Patient" onPress={searchPatient} />
      </View>

      {/* Update Patient Form */}
      <View
        style={{
          borderWidth: 1,
          padding: 20,
          marginBottom: 20,
          width: 350,
        }}
      >
        <Text>Add Patient</Text>

        <Text>First Name</Text>

        <TextInput
          placeholder="Enter first name"
          value={firstName}
          onChangeText={setFirstName}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Text>Last Name</Text>

        <TextInput
          placeholder="Enter last name"
          value={lastName}
          onChangeText={setLastName}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Text>Email</Text>

        <TextInput
          placeholder="Enter email"
          value={email}
          onChangeText={setEmail}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Text>Phone</Text>

        <TextInput
          placeholder="Enter phone"
          value={phone}
          onChangeText={setPhone}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Button title="Add Patient" onPress={addPatient} />
      </View>

      {/* Update Patient Form */}
      <View
        style={{
          borderWidth: 1,
          padding: 20,
          marginBottom: 20,
          width: 350,
        }}
      >
        <Text>Update Patient</Text>

        <Text>Patient ID</Text>
        <TextInput
          placeholder="Enter patient ID"
          value={updatePatientId}
          onChangeText={setUpdatePatientId}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Text>First Name</Text>
        <TextInput
          placeholder="Enter first name"
          value={updateFirstName}
          onChangeText={setUpdateFirstName}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Text>Last Name</Text>
        <TextInput
          placeholder="Enter last name"
          value={updateLastName}
          onChangeText={setUpdateLastName}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Text>Email</Text>
        <TextInput
          placeholder="Enter email"
          value={updateEmail}
          onChangeText={setUpdateEmail}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Text>Phone</Text>
        <TextInput
          placeholder="Enter phone"
          value={updatePhone}
          onChangeText={setUpdatePhone}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Button title="Update Patient" onPress={updatePatient} />
      </View>

      {/* Delete Patient Form */}
      <View
        style={{
          borderWidth: 1,
          padding: 20,
          marginBottom: 20,
          width: 350,
        }}
      >
        <Text>Delete Patient</Text>

        <Text>Patient ID</Text>

        <TextInput
          placeholder="Enter patient ID"
          value={deletePatientId}
          onChangeText={setDeletePatientId}
          style={{
            borderWidth: 2,
            padding: 10,
            width: 300,
            height: 50,
            marginBottom: 20,
          }}
        />

        <Button
          title="Delete Patient"
          onPress={() => {
            const confirmed = window.confirm(
              "Are you sure you want to delete this patient?",
            );

            if (confirmed) {
              deletePatient(deletePatientId);
            }
          }}
        />
      </View>

      {error ? <Text>{error}</Text> : null}

      {successMessage ? <Text>{successMessage}</Text> : null}

      {/* Display one patient from ID search */}
      {patient && (
        <View
          style={{
            borderWidth: 1,
            padding: 15,
            marginTop: 20,
            width: 300,
          }}
        >
          <Text>Patient Information</Text>

          <Text>Patient ID: {patient.patient_id}</Text>

          <Text>
            Name: {patient.first_name} {patient.last_name}
          </Text>

          <Text>Email: {patient.email}</Text>

          <Text>Phone: {patient.phone}</Text>

          <Button
            title="Edit Patient"
            onPress={() => {
              setUpdatePatientId(String(patient.patient_id));
              setUpdateFirstName(patient.first_name);
              setUpdateLastName(patient.last_name);
              setUpdateEmail(patient.email);
              setUpdatePhone(patient.phone ?? "");
            }}
          />

          <Button
            title="Delete Patient"
            onPress={() => {
              const confirmed = window.confirm(
                "Are you sure you want to delete this patient?",
              );

              if (confirmed) {
                deletePatient(patient.patient_id);
              }
            }}
          />
        </View>
      )}

      {/* Display multiple patients from name search */}
      {patients.map((patient) => {
        return (
          <View
            key={patient.patient_id}
            style={{
              borderWidth: 1,
              padding: 15,
              marginTop: 20,
              width: 300,
            }}
          >
            <Text>Patient Information</Text>

            <Text>Patient ID: {patient.patient_id}</Text>

            <Text>
              Name: {patient.first_name} {patient.last_name}
            </Text>

            <Text>Email:{patient.email}</Text>

            <Text>Phone: {patient.phone}</Text>

            <Button
              title="Edit Patient"
              onPress={() => {
                setUpdatePatientId(String(patient.patient_id));
                setUpdateFirstName(patient.first_name);
                setUpdateLastName(patient.last_name);
                setUpdateEmail(patient.email);
                setUpdatePhone(patient.phone ?? "");
              }}
            />
            <Button
              title="Delete Patient"
              onPress={() => {
                const confirmed = window.confirm(
                  "Are you sure you want to delete this patient?",
                );

                if (confirmed) {
                  deletePatient(patient.patient_id);
                }
              }}
            />
          </View>
        );
      })}

      {/* Appointments Section */}
      <View style={{ marginTop: 30, marginBottom: 20 }}>
        <Text style={{ fontSize: 24, fontWeight: "bold", marginBottom: 12 }}>
          Appointments
        </Text>

        {appointmentsLoadError ? (
          <Text style={{ color: "red", marginBottom: 10 }}>
            {appointmentsLoadError}
          </Text>
        ) : null}

        {appointments.length === 0 ? (
          <Text>No appointments found.</Text>
        ) : (
          appointments.map((appointment) => (
            <View
              key={appointment.appointment_id}
              style={{
                borderWidth: 1,
                borderColor: "#cccccc",
                borderRadius: 8,
                padding: 12,
                marginBottom: 12,
              }}
            >
              <Text style={{ fontWeight: "bold", fontSize: 18 }}>
                Appointment #{appointment.appointment_id}
              </Text>

              <Text>Patient: {appointment.patient_name}</Text>
              <Text>Doctor: {appointment.doctor_name}</Text>
              <Text>Specialty: {appointment.specialty}</Text>
              <Text>Date and time: {appointment.appointment_datetime}</Text>
              <Text>Reason: {appointment.reason || "Not provided"}</Text>
              <Text>Status: {appointment.status}</Text>
            </View>
          ))
        )}
      </View>

      {/* Schedule Appointment Form */}
      <View style={{ marginBottom: 24 }}>
        <Text style={{ fontSize: 20, fontWeight: "bold", marginBottom: 12 }}>
          Schedule an Appointment
        </Text>

        <Text>Patient ID</Text>
        <TextInput
          value={appointmentPatientId}
          onChangeText={setAppointmentPatientId}
          placeholder="Enter patient ID"
          keyboardType="numeric"
          style={{
            borderWidth: 1,
            borderColor: "#cccccc",
            borderRadius: 6,
            padding: 10,
            marginBottom: 12,
          }}
        />

        <Text>Select a Doctor</Text>

        {doctors.map((doctor) => (
          <Text
            key={doctor.doctor_id}
            onPress={() => setAppointmentDoctorId(String(doctor.doctor_id))}
            style={{
              borderWidth: 1,
              borderColor:
                appointmentDoctorId === String(doctor.doctor_id)
                  ? "#2563eb"
                  : "#cccccc",
              borderRadius: 6,
              padding: 12,
              marginBottom: 8,
              backgroundColor:
                appointmentDoctorId === String(doctor.doctor_id)
                  ? "#dbeafe"
                  : "white",
              color: "#222222",
            }}
          >
            {doctor.first_name} {doctor.last_name} - {doctor.specialty}
            {appointmentDoctorId === String(doctor.doctor_id) ? " ✓" : ""}
          </Text>
        ))}

        <Text>Appointment Date</Text>
        <TextInput
          value={appointmentDate}
          onChangeText={setAppointmentDate}
          placeholder="YYYY-MM-DD"
          style={{
            borderWidth: 1,
            borderColor: "#cccccc",
            borderRadius: 6,
            padding: 10,
            marginBottom: 12,
          }}
        />

        <Text>Appointment Time</Text>
        <TextInput
          value={appointmentTime}
          onChangeText={setAppointmentTime}
          placeholder="HH:MM (24-hour time)"
          style={{
            borderWidth: 1,
            borderColor: "#cccccc",
            borderRadius: 6,
            padding: 10,
            marginBottom: 12,
          }}
        />

        <Text>Reason for Appointment</Text>
        <TextInput
          value={appointmentReason}
          onChangeText={setAppointmentReason}
          placeholder="Enter reason (optional)"
          style={{
            borderWidth: 1,
            borderColor: "#cccccc",
            borderRadius: 6,
            padding: 10,
            marginBottom: 12,
          }}
        />

        <Button title="Schedule Appointment" onPress={scheduleAppointment} />

        {appointmentMessage ? (
          <Text style={{ color: "green", marginTop: 10 }}>
            {appointmentMessage}
          </Text>
        ) : null}

        {appointmentError ? (
          <Text style={{ color: "red", marginTop: 10 }}>
            {appointmentError}
          </Text>
        ) : null}
      </View>
    </ScrollView>
  );
}
