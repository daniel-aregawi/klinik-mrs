# 🏥 Hospital Management System (HMS) or klinik-mrs

## 📖 Overview

The **Hospital Management System (HMS)** is a full-stack application designed to streamline operations in healthcare facilities. It facilitates efficient handling of patient records, appointment scheduling, medical data, and staff management. The system supports role-based access for various user types such as **Administrators**, **Doctors**, **Receptionists**, and **Lab Technicians**.

* **Backend**: Node.js + Express.js
* **Frontend**: React
* **Database**: MongoDB + Mongoose

---

## 🚀 Technologies Used

| Technology      | Description                                          |
| --------------- | ---------------------------------------------------- |
| **Node.js**     | JavaScript runtime environment for server-side logic |
| **Express.js**  | Minimalist web framework for creating REST APIs      |
| **MongoDB**     | NoSQL database to store hospital-related data        |
| **Mongoose**    | ODM to interact with MongoDB using object modeling   |
| **CORS**        | Middleware to enable cross-origin resource sharing   |
| **Body-Parser** | Middleware to parse incoming request bodies          |

---

## 🔑 Key Features

* **🔐 Role-Based Access Control**: Admin, Doctor, Receptionist, and Lab Technician roles with tailored permissions
* **📋 Full CRUD Operations**: Manage patients, appointments, and staff records
* **🆔 Unique Patient Identifiers**: Combines timestamps and randomness for ID generation
* **👥 Staff & Patient Management**: Manage staff roles and patient profiles with diagnosis and prescriptions
* **📡 RESTful API**: Clean and well-structured endpoints to access and manipulate data

---

## ⚙️ Getting Started

Follow these steps to get the project up and running locally:

### 1. Clone the Repository

```bash
git clone https://github.com/your-username/klinik-mrs.git
cd hospital-management-system
```

### 2. Install Dependencies

```bash
npm install
```

### 3. Set Up MongoDB

* Ensure MongoDB is running locally, or connect to MongoDB Atlas.
* Update the MongoDB connection string in `server.js`:

  ```js
  mongoose.connect('your-mongodb-connection-url');
  ```

### 4. Start the Server

```bash
npm start
```

* The API will be available at: `http://localhost:3001`

---

## 📡 API Endpoints

### ➕ Add a New Patient

**POST** `/api/patients/add`
**Request Body**:

```json
{
  "name": "John Doe",
  "age": 30,
  "gender": "Male",
  "diagnosis": "Flu"
}
```

---

### 📄 Fetch All Patients

**GET** `/api/patients`

---

### 🔍 Get Patient by Custom ID

**GET** `/api/patients/custom/:customId`
**Example**:

```
http://localhost:3001/api/patients/custom/P-1738520236948-502
```

---

### ✏️ Update Patient Info by Custom ID

**PUT** `/api/patients/custom/:customId`
**Request Body**:

```json
{
  "name": "Jane Doe",
  "age": 32,
  "gender": "Female",
  "diagnosis": "Cold"
}
```

---

### ❌ Delete a Patient by ID

**DELETE** `/api/patients/:id`
**Example**:

```
http://localhost:3001/api/patients/5f8d0c5b2b1e6a3d4f3b8c21
```

---

## 📬 Contribution

Contributions, issues, and feature requests are welcome!
Feel free to open a [Pull Request](https://github.com/your-username/klinik-mrs/pulls) or submit [Issues](https://github.com/your-username/klinik-mrs/issues).

---

## 📝 License

This project is licensed under the [MIT License](LICENSE).
