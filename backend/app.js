const express = require('express');
const router = express.Router();
const doctorController = require('../controllers/doctorController');

// @desc    Get all doctors
// @route   GET /api/doctors
// @access  Public
router.get('/', doctorController.getAllDoctors);

// @desc    Get doctor by ID
// @route   GET /api/doctors/:id
// @access  Public
router.get('/:id', doctorController.getDoctorById);

// @desc    Create a new doctor
// @route   POST /api/doctors
// @access  Private/Admin
router.post('/', doctorController.createDoctor);

// @desc    Update doctor by ID
// @route   PUT /api/doctors/:id
// @access  Private/Admin
router.put('/:id', doctorController.updateDoctor);

// @desc    Delete doctor by ID
// @route   DELETE /api/doctors/:id
// @access  Private/Admin
router.delete('/:id', doctorController.deleteDoctor);

module.exports = router;

// In your main app.js or server.js file
const doctorRoutes = require('./routes/doctorRoutes');

// Register the doctors route BEFORE any global authentication middleware
app.use('/api/doctors', doctorRoutes);

// ...then your global authentication middleware, if any...
// app.use(protect); // If you have this, it should come after the above line