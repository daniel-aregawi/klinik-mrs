require('dotenv').config();
const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const path = require('path');
const fs = require('fs');
const nodemailer = require('nodemailer'); // Import nodemailer

// Import routes
const patientRoutes = require('./routes/patientRoutes');
const adminRoutes = require('./routes/adminRoutes');
const staffRoutes = require("./routes/staffRoutes");
const appointmentRoutes = require("./routes/appointmentRoutes");
const authRoutes = require("./routes/authRoutes");
const receptionistRoutes = require("./routes/receptionistRoutes");
const labRoutes = require("./routes/labRoutes");
const doctorRoutes = require("./routes/doctorRoutes");
const prescriptionRoutes = require('./routes/prescriptionRoutes');

const app = express();

app.use(cors({
    origin: 'http://localhost:5173', // EXACTLY match your frontend origin
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
    credentials: true,
    allowedHeaders: ['Content-Type', 'Authorization']
}));

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Create uploads directory if it doesn't exist
const uploadsDir = path.join(__dirname, 'uploads');
const prescriptionsDir = path.join(uploadsDir, 'prescriptions');

if (!fs.existsSync(uploadsDir)) {
  fs.mkdirSync(uploadsDir);
}
if (!fs.existsSync(prescriptionsDir)) {
  fs.mkdirSync(prescriptionsDir);
}

// Serve static files from uploads directory
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

// MongoDB Connection
const mongoURI = process.env.MONGO_URL || 'mongodb://localhost:27017/opensource-hms';
mongoose.connect(mongoURI, {
    useNewUrlParser: true,
    useUnifiedTopology: true,
    serverSelectionTimeoutMS: 10000
})
.then(() => console.log('MongoDB connected'))
.catch((err) => console.error('MongoDB Error:', err));

// Email configuration test
async function testEmailConfig() {
    console.log('Testing email configuration...');
    const emailConfig = {
        service: 'gmail',
        auth: {
            user: process.env.SMTP_USERNAME,
            pass: process.env.SMTP_APP_PASSWORD, // Use Gmail App Password
        },
    };

    console.log('Using email:', process.env.SMTP_USERNAME);

    try {
        const transporter = nodemailer.createTransport(emailConfig);
        await transporter.verify();
        console.log('Email configuration test passed!');
    } catch (error) {
        console.error('Email configuration test failed:', error);
        console.error('Email system configuration failed. Please check your settings.');
    }
}

testEmailConfig();

// Add this before your API routes
app.get('/doctor/patients/booked', (req, res) => {
  res.redirect('/api/doctor/patients/booked');
});

// API Routes
// API Routes - SPECIFIC FIRST
app.use("/api/admin", adminRoutes);
app.use("/api/staff", staffRoutes);
app.use("/api/patient", patientRoutes);
app.use("/api/doctor", doctorRoutes);
app.use("/api/appointments", appointmentRoutes);
app.use("/api/prescriptions", prescriptionRoutes);
app.use("/api/auth", authRoutes); // <-- Add this line to register auth routes
app.use("/api", receptionistRoutes);  // GENERAL ROUTE LAST

// Optional: Add this to support /auth/me directly
app.use('/auth', authRoutes);

// Default Route
app.get('/', (req, res) => {
    res.send('Hospital Management API is Running...');
});

const { errorHandler } = require('./middlewares/errorMiddleware');

// Error handling middleware
app.use(errorHandler);

// Start Server
const PORT = process.env.PORT || 3001;
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));

// Handle unhandled promise rejections
process.on('unhandledRejection', (err) => {
  console.error('Unhandled Promise Rejection:', err);
  // Close server & exit process
  app.close(() => process.exit(1));
});
