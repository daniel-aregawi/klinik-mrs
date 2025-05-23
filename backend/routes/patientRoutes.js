const express = require('express');
const router = express.Router();
const Patient = require("../models/patientModel");
const { generateOtpHandler, verifyOtpHandler, loginPatient } = require("../controllers/patientController");
const sendSMS = require("../utils/sendSms");
const jwt = require("jsonwebtoken");
require("dotenv").config();
const Appointment = require('../models/appointmentModel');
const Prescription = require('../models/prescriptionModel');
const LabReport = require('../models/labReportModel');
const { authMiddleware, protect } = require('../middlewares/authMiddleware');
const Notification = require('../models/notificationModel');
const path = require('path');
const fs = require('fs');
const patientController = require('../controllers/patientController');

// OTP Authentication
router.post('/send-email-verification', async (req, res) => { // Updated route to send email verification
  try {
    const { phone, contactNumber } = req.body;
    const phoneNumber = phone || contactNumber;
    
    if (!phoneNumber) {
      return res.status(400).json({ 
        success: false,
        message: 'Phone number is required' 
      });
    }

    // Validate phone number format
    if (!/^[0-9]{10}$/.test(phoneNumber)) {
      return res.status(400).json({ 
        success: false,
        message: 'Please provide a valid 10-digit phone number' 
      });
    }

    // Check if patient exists
    let patient = await Patient.findOne({ contactNumber: phoneNumber });
    
    if (!patient) {
      // If patient doesn't exist, create a temporary record
      patient = new Patient({
        contactNumber: phoneNumber,
        isTemporary: true, // Mark as temporary until registration is complete
        otpAttempts: 0,
        name: "Temporary User", // Provide a temporary name
        email: `temp_${Date.now()}@temp.com` // Provide a temporary email
      });
    } else if (patient.isTemporary) {
      // If patient is temporary, allow OTP resend
      patient.otpAttempts = 0;
    } else {
      // For existing patients, check OTP cooldown
      const lastOtpSent = patient.lastOtpSent;
      if (lastOtpSent) {
        const cooldownTime = new Date(lastOtpSent.getTime() + 60 * 1000); // 1 minute cooldown
        if (new Date() < cooldownTime) {
          return res.status(429).json({
            success: false,
            message: 'Please wait before requesting another OTP',
            cooldown: Math.ceil((cooldownTime - new Date()) / 1000)
          });
        }
      }
    }

    // Generate OTP
    const otp = Math.floor(100000 + Math.random() * 900000);
    const otpExpiry = new Date(Date.now() + 10 * 60 * 1000); // 10 minutes

    // Update patient with new OTP
    patient.otp = otp;
    patient.otpExpiry = otpExpiry;
    patient.lastOtpSent = new Date();
    await patient.save();

    // Send OTP via SMS
    const message = `Your DeccanCare OTP is: ${otp}. Valid for 10 minutes.`;
    const smsSent = await sendSMS(phoneNumber, message);

    if (!smsSent) {
      return res.status(500).json({
        success: false,
        message: 'Failed to send OTP via SMS'
      });
    }

    res.json({
      success: true,
      message: 'OTP sent successfully',
      cooldown: 60 // 1 minute cooldown
    });
  } catch (error) {
    console.error('Error sending OTP:', error);
    res.status(500).json({
      success: false,
      message: 'Error sending OTP',
      error: process.env.NODE_ENV === 'development' ? error.message : undefined
    });
  }
});

// Check if patient exists
router.post("/check", async (req, res) => {
    try {
        const { contactNumber } = req.body;
        
        if (!contactNumber) {
            return res.status(400).json({
                success: false,
                message: "Contact number is required"
            });
        }

        const patient = await Patient.findOne({ contactNumber });
        
        res.status(200).json({
            success: true,
            exists: !!patient
        });
    } catch (error) {
        console.error("Error checking patient:", error);
        res.status(500).json({
            success: false,
            message: "Error checking patient existence"
        });
    }
});

// Register new patient
router.post("/register", async (req, res) => {
  try {
    const { name, email, contactNumber } = req.body;
    
    if (!name || !email || !contactNumber) {
      return res.status(400).json({ 
        success: false,
        message: 'Name, email and contact number are required' 
      });
    }

    // Check if patient already exists
    const existingPatient = await Patient.findOne({ 
      $or: [{ email }, { contactNumber }] 
    });

    if (existingPatient) {
      return res.status(400).json({ 
        success: false,
        message: 'Patient with this email or phone number already exists' 
      });
    }

    // Create new patient with all required fields
    const patient = new Patient({
      name,
      email,
      contactNumber,
      customId: `P-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
      medicalHistory: {
        appointments: [],
        prescriptions: [],
        labReports: [],
        allergies: [],
        conditions: [],
        medications: []
      }
    });

    await patient.save();

    // Send registration success SMS with customId
    const smsMessage = `Registration successful. Your Hospital ID: ${patient.customId}. Please keep this ID safe for future reference.`;
    await sendSMS(contactNumber, smsMessage);

    res.status(201).json({
      success: true,
      message: 'Patient registered successfully',
      patient: {
        id: patient._id,
        name: patient.name,
        email: patient.email,
        phone: patient.contactNumber,
        customId: patient.customId
      }
    });
  } catch (error) {
    console.error('Error registering patient:', error);
    res.status(500).json({ 
      success: false,
      message: 'Error registering patient',
      error: error.message 
    });
  }
});

// Patient login route
router.post('/login', loginPatient);

// Protected routes
// router.use(authMiddleware);
console.log('Protected routes initialized:', authMiddleware);
// Get patient profile
router.get('/profile', authMiddleware, async (req, res) => {
  try {
    const patient = await Patient.findById(req.user.id)
      .select('-password -otp -otpExpiry');
    if (!patient) {
      return res.status(404).json({ 
        success: false,
        message: 'Patient not found' 
      });
    }
    res.json({
      success: true,
      data: patient
    });
  } catch (error) {
    console.error('Error fetching patient profile:', error);
    res.status(500).json({ 
      success: false,
      message: 'Error fetching patient profile' 
    });
  }
});

// Update patient profile
router.put('/profile', authMiddleware, async (req, res) => {
  try {
    const updatedPatient = await Patient.findByIdAndUpdate(
      req.user.id,
      { $set: req.body },
      { new: true }
    ).select('-password -otp -otpExpiry');
    if (!updatedPatient) {
      return res.status(404).json({ success: false, message: 'Patient not found' });
    }
    res.json({ success: true, data: updatedPatient });
  } catch (error) {
    console.error('Error updating profile:', error);
    res.status(500).json({ message: 'Error updating profile' });
  }
});

// Get patient appointments
router.get('/appointments', authMiddleware, async (req, res) => {
  try {
    const appointments = await Appointment.find({ patientId: req.user.id })
      .populate('doctorId', 'username specialization')
      .sort({ date: -1 });
    res.json(Array.isArray(appointments) ? appointments : []);
  } catch (error) {
    console.error('Error fetching appointments:', error);
    res.status(500).json({ message: 'Error fetching appointments' });
  }
});

// Get patient prescriptions
router.get('/prescriptions', authMiddleware, async (req, res) => {
  try {
    const prescriptions = await Prescription.find({ patientId: req.user.id })
      .populate('doctorId', 'username specialization')
      .populate('appointmentId', 'date time')
      .sort({ createdAt: -1 });

    res.status(200).json({ success: true, data: Array.isArray(prescriptions) ? prescriptions : [] });
  } catch (error) {
    console.error('Error fetching prescriptions:', error);
    res.status(500).json({ success: false, message: 'Error fetching prescriptions' });
  }
});

// Get patient lab reports
router.get('/lab-reports', authMiddleware, async (req, res) => {
  try {
    const labReports = await LabReport.find({ patientId: req.user.id })
      .populate('doctorId', 'username specialization')
      .sort({ date: -1 });
    res.json(Array.isArray(labReports) ? labReports : []);
  } catch (error) {
    console.error('Error fetching lab reports:', error);
    res.status(500).json({ message: 'Error fetching lab reports' });
  }
});

// Get patient notifications
router.get('/notifications', authMiddleware, async (req, res) => {
  try {
    const notifications = await Notification.find({ patientId: req.user.id })
      .sort({ date: -1 });
    res.json(Array.isArray(notifications) ? notifications : []);
  } catch (error) {
    console.error('Error fetching notifications:', error);
    res.status(500).json({ message: 'Error fetching notifications' });
  }
});

// Download lab report
router.get('/lab-reports/:reportId/download', authMiddleware, async (req, res) => {
  try {
    const report = await LabReport.findOne({
      _id: req.params.reportId,
      patientId: req.user._id
    });
    
    if (!report) {
      return res.status(404).json({ message: 'Report not found' });
    }
    
    res.download(report.filePath);
  } catch (error) {
    console.error('Error downloading lab report:', error);
    res.status(500).json({ message: 'Error downloading lab report' });
  }
});

// Download prescription
router.get('/prescriptions/:prescriptionId/download', authMiddleware, async (req, res) => {
  try {
    const prescription = await Prescription.findOne({
      _id: req.params.prescriptionId,
      patientId: req.user._id
    }).populate('doctorId', 'username specialization');
    
    if (!prescription) {
      return res.status(404).json({ message: 'Prescription not found or unauthorized' });
    }

    if (!prescription.filePath) {
      return res.status(404).json({ message: 'Prescription file not found' });
    }
    
    const filePath = path.join(__dirname, '..', prescription.filePath);
    if (!fs.existsSync(filePath)) {
      return res.status(404).json({ message: 'Prescription file not found on server' });
    }
    
    res.download(filePath);
  } catch (error) {
    console.error('Error downloading prescription:', error);
    res.status(500).json({ message: 'Error downloading prescription' });
  }
});

// Get patient medical history
router.get('/medical-history', authMiddleware, async (req, res) => {
  try {
    const patient = await Patient.findById(req.user.id)
      .populate('medicalHistory.appointments')
      .populate('medicalHistory.prescriptions')
      .populate('medicalHistory.labReports');

    if (!patient) {
      return res.status(404).json({ 
        success: false,
        message: 'Patient not found' 
      });
    }

    res.json({
      success: true,
      data: patient.medicalHistory
    });
  } catch (error) {
    console.error('Error fetching medical history:', error);
    res.status(500).json({ 
      success: false,
      message: 'Error fetching medical history' 
    });
  }
});

// Get patient by customId (for doctor/patient view)
router.get('/:customId', async (req, res) => {
  try {
    const patient = await Patient.findOne({ customId: req.params.customId }).select('-password -otp -otpExpiry');
    if (!patient) {
      return res.status(404).json({ success: false, message: 'Patient not found' });
    }

    // Get upcoming appointments
    const now = new Date();
    const upcomingAppointments = await Appointment.find({
      patientId: patient._id,
      date: { $gte: now }
    })
      .populate('doctorId', 'name specialization')
      .sort({ date: 1 });

    // Prepare response object with all expected fields
    res.json({
      ...patient.toObject(),
      allergies: patient.allergies || [],
      chronicConditions: patient.chronicConditions || [],
      medications: patient.medications || [],
      medicalHistory: patient.medicalHistory || [],
      upcomingAppointments
    });
  } catch (error) {
    console.error('Error getting patient:', error);
    res.status(500).json({ success: false, message: 'Error getting patient' });
  }
});

// Test email route
router.get('/test-email', async (req, res) => {
  try {
    const email = "danielaregawi50@gmail.com"; // Replace with your test email
    const subject = "Test Email from Hospital Management System";
    const message = "This is a test email to verify the SMTP configuration.";

    const nodemailer = require('nodemailer');
    const transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: process.env.SMTP_PORT,
      auth: {
        user: process.env.SMTP_USERNAME,
        pass: process.env.SMTP_PASSWORD,
      },
    });

    const mailOptions = {
      from: process.env.EMAIL_FROM,
      to: email,
      subject,
      text: message,
    };

    await transporter.sendMail(mailOptions);
    console.log(`Test email sent to ${email}`);
    res.status(200).json({ message: "Test email sent successfully." });
  } catch (error) {
    console.error("Error sending test email:", error);
    res.status(500).json({ message: "Failed to send test email.", error: error.message });
  }
});

module.exports = router;