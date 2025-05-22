// Add a route to serve the login page for lab technicians
router.get('/login', (req, res) => {
  res.status(200).json({ message: 'Lab Technician Login Page' });
});