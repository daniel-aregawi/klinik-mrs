Hospital Management System (HMS)
Overview
The Hospital Management System (HMS) is a comprehensive full-stack application built to handle patient records, appointment scheduling, and medical information within a hospital environment. It supports different users such as administrators, doctors, receptionists, and lab technicians, allowing them to efficiently manage hospital data. The backend is built using Node.js and Express.js, while MongoDB is used as the database.

Technologies
Node.js: Server-side JavaScript runtime environment.

Express.js: Framework to create REST APIs.

MongoDB: NoSQL database for storing hospital and patient data.

Mongoose: Library to work with MongoDB data through an object model.

CORS: Middleware to enable cross-origin requests.

Body-Parser: Middleware to handle incoming JSON and form data.

Key Features
Role-Based Access: Different user roles (Admin, Doctor, Receptionist, Lab Technician) with specific access permissions.

Full CRUD: Create, read, update, and delete patient data and other records.

Unique Patient IDs: Generates custom patient identifiers combining timestamps and random numbers.

Patient & Staff Management: Admin manages hospital staff, doctors can add diagnoses and prescriptions to patient profiles.

RESTful API: Provides endpoints for managing patients and accessing patient info by custom IDs.

Getting Started
1. Install dependencies
Go to the project directory and run:

bash
Copy
Edit
npm install
2. Set up MongoDB
Make sure MongoDB is running locally or connect to a cloud service like MongoDB Atlas. Update the database connection URL in server.js if needed.

3. Run the backend server
Start the server using:

bash
Copy
Edit
npm start
The API will be accessible at http://localhost:3001.

API Endpoints
POST /api/patients/add
Add a new patient. Example request body:

json
Copy
Edit
{
  "name": "John Doe",
  "age": 30,
  "gender": "Male",
  "diagnosis": "Flu"
}
GET /api/patients
Fetch all patient records.

GET /api/patients/custom/:customId
Get a patient by their custom patient ID. Example:
http://localhost:3001/api/patients/custom/P-1738520236948-502

PUT /api/patients/custom/:customId
Update patient details using their custom ID. Example request body:

json
Copy
Edit
{
  "name": "Jane Doe",
  "age": 32,
  "gender": "Female",
  "diagnosis": "Cold"
}
DELETE /api/patients/:id
Remove a patient record by their MongoDB-generated ID. Example:
http://localhost:3001/api/patients/5f8d0c5b2b1e6a3d4f3b8c21
