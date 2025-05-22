// filepath: c:\Installation\2\HospitalManagementSystemPersonal-main\frontend\src\config\axios.js
import axios from 'axios';
console.log("VITE_HOST:", import.meta.env.VITE_HOST); // Add this line
const baseURL = import.meta.env.VITE_HOST || 'http://localhost:3001';

const instance = axios.create({
  baseURL,
  headers: {
    'Content-Type': 'application/json'
  }
});

// Add a request interceptor to add the auth token to requests
// instance.interceptors.request.use(
//   (config) => {
//     console.log(`Axios request: ${config.method.toUpperCase()} ${config.url}`);
//     const token = localStorage.getItem('token');
//     if (token) {
//       config.headers.Authorization = `Bearer ${token}`;  // Add Bearer prefix
//       console.log('Token added to request headers');
//     } else {
//       console.warn('No token found in localStorage');
//     }
//     return config;
//   },
//   (error) => {
//     console.error('Axios request error:', error);
//     return Promise.reject(error);
//   }
// );
// filepath: c:\Installation\2\HospitalManagementSystemPersonal-main\frontend\src\config\axios.js
instance.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`; // Correct prefix
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Add a response interceptor to log responses
instance.interceptors.response.use(
  (response) => {
    console.log(`Axios response: ${response.status} ${response.config.url}`);
    return response;
  },
  (error) => {
    console.error('Axios response error:', error);
    if (error.response) {
      console.error('Error response data:', error.response.data);
      console.error('Error response status:', error.response.status);
    }
    return Promise.reject(error);
  }
);

export default instance;