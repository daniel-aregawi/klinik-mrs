import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { FaFlask, FaHome, FaSignInAlt, FaLock, FaEye, FaEyeSlash } from 'react-icons/fa';
import { motion } from 'framer-motion';
import axios from './../config/axios';
import { toast } from 'react-toastify';

const LabTechnicianLogin = () => {
  const navigate = useNavigate();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);
  const [showPassword, setShowPassword] = useState(false);

  const handleLogin = async (e) => {
    e.preventDefault();
    if (!email.trim()) {
      toast.error("Email is required");
      return;
    }
    if (!password) {
      toast.error("Password is required");
      return;
    }
    setLoading(true);
    try {
      const response = await axios.post('/api/lab-technician/login', { email, password });
      localStorage.setItem('token', response.data.token);
      toast.success("Login successful!");
      navigate('/lab-technician/dashboard'); // Adjust as needed
    } catch (err) {
      toast.error(err.response?.data?.message || "Login failed");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900 to-indigo-900 flex items-center justify-center p-6 relative overflow-hidden">
      {/* Home Button */}
      <motion.button 
        onClick={() => navigate('/')}
        className="absolute top-6 left-6 flex items-center space-x-2 text-white p-2 rounded-full bg-white/10 hover:bg-white/20 transition-colors"
        whileHover={{ scale: 1.05 }}
      >
        <FaHome className="text-xl" />
        <span>Home</span>
      </motion.button>
      
      {/* Main Card */}
      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity:1, y:0 }}
        transition={{ duration: 0.5 }}
        className="relative bg-white/5 backdrop-blur-2xl rounded-3xl shadow-2xl p-8 w-full max-w-md border border-white/10 overflow-hidden"
      >
        <div className="text-center mb-6">
          <FaFlask className="text-5xl text-white mx-auto mb-4" />
          <h1 className="text-3xl font-bold text-white">Lab Technician Login</h1>
          <p className="text-white/70">Enter your email and password to login</p>
        </div>
        <form onSubmit={handleLogin} className="space-y-6">
          <div>
            <label className="block text-white/70 mb-2">Email Address</label>
            <input 
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-4 py-3 rounded-lg bg-white/10 text-white placeholder-white/50 border border-white/20 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all"
              placeholder="your-email@example.com"
              required
            />
          </div>
          <div className="relative">
            <label className="block text-white/70 mb-2">Password</label>
            <input
              type={showPassword ? "text" : "password"}
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-3 pr-12 rounded-lg bg-white/10 text-white placeholder-white/50 border border-white/20 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-transparent transition-all"
              placeholder="Enter your password"
              required
            />
            <button
              type="button"
              onClick={() => setShowPassword(!showPassword)}
              className="absolute right-3 top-9 transform -translate-y-1/2 text-white/70 hover:text-white"
              tabIndex={-1}
            >
              {showPassword ? <FaEyeSlash /> : <FaEye />}
            </button>
          </div>
          <button
            type="submit"
            disabled={loading}
            className="w-full py-4 px-6 bg-gradient-to-r from-[#FF5733] to-[#FF3300] text-white rounded-xl font-semibold hover:opacity-90 transition-all disabled:opacity-50 shadow-lg flex items-center justify-center"
          >
            {loading ? (
              <span>Logging in...</span>
            ) : (
              <>
                <FaSignInAlt className="mr-2" />
                <span>Login</span>
              </>
            )}
          </button>
        </form>
        <div className="mt-6 text-center">
          <p className="text-white/70 text-sm">
            Forgot your email or password? Please contact admin.
          </p>
        </div>
      </motion.div>
    </div>
  );
};

export default LabTechnicianLogin;
