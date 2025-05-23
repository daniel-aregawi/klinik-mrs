const express = require("express");
const router = express.Router();
const labTechnicianController = require("../controllers/labTechnicianController");

// Lab Technician Login (email-only) route
router.post('/login', labTechnicianController.loginLabTechnician);

module.exports = router;