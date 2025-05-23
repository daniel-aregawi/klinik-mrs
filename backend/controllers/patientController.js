const Patient = require("../models/patientModel");
const nodemailer = require('nodemailer');
const jwt = require('jsonwebtoken');
require('dotenv').config();

const sendEmailNotification = async (email, subject, message) => {
  try {
    const transporter = nodemailer.createTransport({
      service: 'gmail',
      auth: {
        user: 'danielaregawi50@gmail.com', // Sender's email address
        pass: 'your-app-password', // Replace with your Gmail App Password
      },
    });

    const mailOptions = {
      from: 'danielaregawi50@gmail.com', // Sender's email address
      to: email,
      subject,
      text: message,
    };

    await transporter.sendMail(mailOptions);
    console.log(`Email sent to ${email}`);
  } catch (error) {
    console.error('Error sending email:', error);
    throw new Error('Failed to send email notification');
  }
};

// Register patient
const registerPatient = async (req, res) => {
  try {
    const { name, email, contactNumber } = req.body;

    const existingPatient = await Patient.findOne({ email });
    if (existingPatient) {
      return res.status(400).json({ message: 'Email already registered.' });
    }

    const patient = new Patient({ name, email, contactNumber });
    await patient.save();

    const subject = 'Registration Confirmation';
    const message = `Dear ${name},\n\nThank you for registering with our hospital. Your registration is now complete.\n\nBest regards,\nHospital Management Team`;
    await sendEmailNotification(email, subject, message);

    res.status(201).json({ message: 'Registration successful. Confirmation email sent.' });
  } catch (error) {
    console.error('Error registering patient:', error);
    res.status(500).json({ message: 'Server error' });
  }
};

// Login patient using email only
const loginPatient = async (req, res) => {
  try {
    const { email } = req.body;

    if (!email) {
      return res.status(400).json({ message: 'Email is required.' });
    }

    const patient = await Patient.findOne({ email });
    if (!patient) {
      return res.status(404).json({ message: 'Patient not found.' });
    }

    // Generate JWT token
    const token = jwt.sign({ id: patient._id }, process.env.JWT_SECRET, {
      expiresIn: '1d',
    });

    res.status(200).json({
      message: 'Login successful.',
      token,
      patient: {
        id: patient._id,
        name: patient.name,
        email: patient.email,
        contactNumber: patient.contactNumber,
      },
    });
  } catch (error) {
    console.error('Error logging in patient:', error);
    res.status(500).json({ message: 'Server error.' });
  }
};

module.exports = { registerPatient, loginPatient };