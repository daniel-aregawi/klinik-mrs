import { useState, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import logo from './hospitallogo.png';
import backgroundVideo from './backgroundvideo.mp4';
import { FiUpload, FiSearch } from 'react-icons/fi';
import ReceptionistDashboard from './pages/ReceptionistDashboard';
import axios from 'axios';

function App() {
  return <HomePage />;
}

function HomePage() {
  const [activeNav, setActiveNav] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  
  const fileInputRef = useRef(null);
  const navigate = useNavigate();

  const leftNavItems = [
    { id: 'about', label: 'ABOUT' },
    { id: 'doctors', label: 'DOCTORS' },
    { id: 'patients', label: 'PATIENTS' }
  ];

  const rightNavItems = [
    { id: 'receptionists', label: 'RECEPTIONISTS' },
    { id: 'technicians', label: 'LAB TECHNICIANS' },
  ];

  const handleNavClick = (id) => {
    switch (id) {
      case 'doctors':
        navigate('/select-role');
        break;
      case 'patients':
        navigate('/patient/login');
        break;
      case 'receptionists':
        navigate('/auth/receptionist');
        break;
      case 'technicians':
        navigate('/technician/login');
        break;
      case 'about':
        navigate('/about-us');
        break;
      default:
        break;
    }
  };

  const handleSearch = (e) => {
    if (e.key === "Enter" && searchQuery.trim()) {
      navigate(`/search/${searchQuery.trim()}`);
    }
  };

  const handleUploadClick = () => {
    fileInputRef.current.click();
  };

  const handleFileUpload = async (e) => {
    const file = e.target.files[0];
    if (!file) return;
  
    try {
      setLoading(true);
      const formData = new FormData();
      formData.append("pdf", file);
  
      const response = await axios.post("api/upload", formData, {
        headers: {
          "Content-Type": "multipart/form-data",
        },
      });
      const data = await response.json();
      
      if (!response.ok) throw new Error(data.error || 'Upload failed');
      
      if (data.medicines && data.medicines.length > 0) {
        navigate(`/search/${data.medicines[0].medicine}`, {
          state: { rawResults: data.medicines }
        });
      } else {
        alert("No medicine names found in the PDF.");
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="relative w-full h-screen overflow-hidden flex flex-col font-['Arial,_sans-serif']">
      {/* Video Background */}
      <video 
        autoPlay 
        loop 
        muted 
        className="absolute top-0 left-0 w-full h-full object-cover z-0"
      >
        <source src={backgroundVideo} type="video/mp4" />
        Your browser does not support HTML5 video.
      </video>

      {/* Professional Navigation */}
      <nav className="fixed top-0 w-full z-50 py-0">
        <div className="backdrop-blur-md bg-white/30 shadow-lg border-b border-white/20">
          <div className="flex justify-between items-center max-w-7xl mx-auto px-8 py-2">
            {/* Left nav */}
            <div className="flex gap-2">
              {leftNavItems.map(item => (
                <button
                  key={item.id}
                  className={`relative px-5 py-2 text-base font-semibold tracking-wide transition-colors duration-200
                    text-slate-800 hover:text-blue-700 focus:outline-none
                    ${activeNav === item.id ? 'text-blue-700' : ''}
                  `}
                  onMouseEnter={() => setActiveNav(item.id)}
                  onMouseLeave={() => setActiveNav(null)}
                  onClick={() => handleNavClick(item.id)}
                >
                  {item.label}
                  <span className={`absolute left-1/2 -bottom-1.5 w-0 h-0.5 bg-gradient-to-r from-blue-400 to-blue-700 rounded-full transition-all duration-300
                    ${activeNav === item.id ? 'w-3/4 -translate-x-1/2' : 'w-0 -translate-x-1/2'}
                  `} />
                </button>
              ))}
            </div>

            {/* Logo */}
            <div className="flex-shrink-0 flex items-center">
              <img 
                src={logo} 
                alt="Hospital Logo" 
                className="w-auto h-16 max-h-20 rounded-full border-4 border-blue-200 shadow-lg bg-white object-contain mx-1"
              />
            </div>

            {/* Right nav */}
            <div className="flex gap-2">
              {rightNavItems.map(item => (
                <button
                  key={item.id}
                  className={`relative px-5 py-2 text-base font-semibold tracking-wide transition-colors duration-200
                    text-slate-800 hover:text-blue-700 focus:outline-none
                    ${activeNav === item.id ? 'text-blue-700' : ''}
                  `}
                  onMouseEnter={() => setActiveNav(item.id)}
                  onMouseLeave={() => setActiveNav(null)}
                  onClick={() => handleNavClick(item.id)}
                >
                  {item.label}
                  <span className={`absolute left-1/2 -bottom-1.5 w-0 h-0.5 bg-gradient-to-r from-blue-400 to-blue-700 rounded-full transition-all duration-300
                    ${activeNav === item.id ? 'w-3/4 -translate-x-1/2' : 'w-0 -translate-x-1/2'}
                  `} />
                </button>
              ))}
            </div>
          </div>
        </div>
      </nav>

      {/* Empty space to push search bar to bottom */}
      <div className="flex-grow"></div>

      {/* Search Bar - Now positioned at bottom */}
      <div className="relative z-10 w-full pb-16 px-4">
        <div className="max-w-3xl mx-auto">
          <div className="flex items-center bg-white/15 backdrop-blur-sm rounded-full border border-white/20 
            transition-all duration-300 hover:bg-white/25">
            <FiSearch className="text-white text-xl ml-6 mr-4" />
            <input
              type="text"
              placeholder="Search For Medicine Details / Upload The prescription..."
              className="flex-1 bg-transparent py-4 text-white placeholder-white/70 outline-none pr-4"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyDown={handleSearch}
            />
            <button 
              className="p-3 mr-2 text-white rounded-full hover:bg-white/20 transition-colors"
              onClick={handleUploadClick}
            >
              <FiUpload className="text-xl" />
            </button>
            <input
              type="file"
              accept="application/pdf"
              ref={fileInputRef}
              onChange={handleFileUpload}
              className="hidden"
            />
          </div>

          {/* Loading and error states */}
          {loading && (
            <p className="text-white text-center mt-4">Processing your prescription...</p>
          )}
          {error && (
            <p className="text-red-300 text-center mt-4">{error}</p>
          )}
        </div>
      </div>
    </div>
  );
}

export default App;