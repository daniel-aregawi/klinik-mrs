const express = require('express');
const app = express();
const doctorRoutes = require('./routes/doctorRoutes');
const labTechnicianRoutes = require('./routes/labTechnicianRoutes');

// Middleware to parse JSON requests
app.use(express.json());

// Register the doctors route
app.use('/api/doctors', doctorRoutes);

// Mount lab technician routes at /api/lab-technician
app.use('/api/lab-technician', labTechnicianRoutes);

// Export the app for testing or further configuration
module.exports = app; // or app.listen(...) if this is your entry point