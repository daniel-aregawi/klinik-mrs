const User = require("../models/userModel");
const jwt = require("jsonwebtoken");
require("dotenv").config();

exports.loginLabTechnician = async (req, res) => {
  try {
    const { email, password } = req.body;
    if (!email) {
      return res.status(400).json({ message: "Email is required." });
    }
    if (!password) {
      return res.status(400).json({ message: "Password is required." });
    }
    const labTech = await User.findOne({ email, role: "LabTechnician" });
    if (!labTech) {
      return res.status(404).json({ message: "Lab Technician not found." });
    }
    // Check password (assuming userModel has a comparePassword method)
    const isMatch = await labTech.comparePassword(password);
    if (!isMatch) {
      return res.status(401).json({ message: "Invalid password." });
    }
    // Generate JWT token
    const token = jwt.sign(
      { id: labTech._id, role: labTech.role },
      process.env.JWT_SECRET,
      { expiresIn: "1d" }
    );
    res.status(200).json({
      message: "Login successful.",
      token,
      labTechnician: {
        id: labTech._id,
        username: labTech.username,
        email: labTech.email,
        role: labTech.role,
      },
    });
  } catch (error) {
    console.error("Error logging in lab technician:", error);
    res.status(500).json({ message: "Server error." });
  }
};
