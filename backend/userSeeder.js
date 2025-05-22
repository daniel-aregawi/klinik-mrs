const mongoose = require("mongoose");
const User = require("./models/userModel");
require("dotenv").config();

async function seedUsers() {
  const mongoURI = process.env.MONGO_URL || "mongodb://localhost:27017/opensource-hms";

  try {
    await mongoose.connect(mongoURI, {
      useNewUrlParser: true,
      useUnifiedTopology: true,
    });

    await User.deleteMany({});

    // Admin User
    const adminUser = new User({
      username: "Admin User",
      email: "admin@example.com",
      password: "Admin123", // plain text, let pre-save hook hash it
      role: "Admin",
      status: "Approved",
    });
    await adminUser.save();

    // Doctor User
    const doctorUser = new User({
      username: "Doctor User",
      email: "doctor@example.com",
      password: "Doctor123", // plain text, let pre-save hook hash it
      role: "Doctor",
      specialization: "Cardiologist",
      status: "Approved",
    });
    await doctorUser.save();

    // Receptionist User
    const receptionistUser = new User({
      username: "Receptionist User",
      email: "receptionist@example.com",
      password: "Receptionist123", // plain text, let pre-save hook hash it
      role: "Receptionist",
      status: "Approved",
    });
    await receptionistUser.save();

    // Lab Technician User
    const labTechUser = new User({
      username: "Lab Technician User",
      email: "labtech@example.com",
      password: "LabTech123", // plain text, let pre-save hook hash it
      role: "Lab Technician",
      status: "Pending",
    });
    await labTechUser.save();

    console.log("Seeding complete!");
  } catch (error) {
    console.error("Seeding error:", error);
  } finally {
    mongoose.disconnect();
  }
}

seedUsers();

