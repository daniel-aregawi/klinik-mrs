import React, { useState } from "react"; // Removed unused useEffect import
import axios from "axios"; // Use your configured axios instance
import { useNavigate } from "react-router-dom";
import { toast } from "react-toastify";
import "../../styles/RegisterPatient.css";

const RegisterPatient = () => {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    contactNumber: "",
  });

  const [error, setError] = useState("");
  const [registrationSuccess, setRegistrationSuccess] = useState(false);
  const navigate = useNavigate();

  const handleChange = (e) => {
    setFormData({ ...formData, [e.target.name]: e.target.value });
    setError("");
  };

  const handleRegister = async (e) => {
    e.preventDefault();
    try {
      // Register the patient and send email notification
      await axios.post("/api/patient/register", {
        name: formData.name,
        email: formData.email,
        contactNumber: formData.contactNumber,
      });

      toast.success("Patient registered successfully! Confirmation email sent.");
      setRegistrationSuccess(true);

      // Redirect to receptionist dashboard after 3 seconds
      setTimeout(() => {
        navigate("/receptionist/dashboard");
      }, 3000);
    } catch (err) {
      console.error(err);
      setError(err.response?.data?.message || "Failed to register patient. Please check the details.");
      toast.error(err.response?.data?.message || "Failed to register patient");
    }
  };

  if (registrationSuccess) {
    return (
      <div className="register-container">
        <div className="register-box success-box">
          <div className="success-icon">✓</div>
          <h2>Registration Successful!</h2>
          <p>Patient has been successfully registered.</p>
          <p>You can now proceed to book appointments.</p>
          <p>Redirecting to dashboard...</p>
        </div>
      </div>
    );
  }

  return (
    <div className="register-container">
      <div className="register-box">
        <h2>Register Patient</h2>
        <form onSubmit={handleRegister}>
          <div className="form-group">
            <label>Name</label>
            <input
              type="text"
              name="name"
              value={formData.name}
              onChange={handleChange}
              required
            />
          </div>
          <div className="form-group">
            <label>Email</label>
            <input
              type="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              required
            />
          </div>
          <div className="form-group">
            <label>Contact Number</label>
            <input
              type="text"
              name="contactNumber"
              value={formData.contactNumber}
              onChange={handleChange}
              required
              pattern="[0-9]{10}"
              maxLength="10"
            />
          </div>
          {error && <div className="error-message">{error}</div>}
          <button type="submit" className="btn-primary">
            Register
          </button>
        </form>
      </div>
    </div>
  );
};

export default RegisterPatient;