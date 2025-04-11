<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SseController;
use App\Http\Controllers\UpdateUserInfo;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NurseController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\VaccineController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\BloodBankController;
use App\Http\Controllers\OurOffersController;
use App\Http\Controllers\BloodDonorController;
use App\Http\Controllers\PharmacistController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BirthReportController;
use App\Http\Controllers\DeathReportController;
use App\Http\Controllers\DepartmentsController;
use App\Http\Controllers\AppointmentReservation;
use App\Http\Controllers\BedAllotmentController;
use App\Http\Controllers\LaboratoristController;
use App\Http\Controllers\OperationReportController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\DoctorAppointmentController;
use App\Http\Controllers\OrderNotificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\AppointmentNotification;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('v1')->group(function () use ($router) {
    // Authentication EndPoints
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('guest')
        ->name('register');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth:sanctum');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('guest')
        ->name('password.email');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('guest')
        ->name('password.store');



    // Route::get('/login/facebook', [AuthenticatedSessionController::class, 'authFacebook']);
    // Route::get('/authentication/login/facebook', [AuthenticatedSessionController::class, 'handleProviderCallback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/update-password', [UpdateUserInfo::class, 'updatePassword']);
        Route::post('/update-name', [UpdateUserInfo::class, 'updateName']);
        Route::post('/update-email', [UpdateUserInfo::class, 'updateEmail']);
        Route::post('/update-social-links', [UpdateUserInfo::class, 'updateSocialLinks']);
        Route::post('/update-image', [UpdateUserInfo::class, 'updateImage']);
        Route::get('/get-user-photo', [UserController::class, 'getUserProfilePhoto']);
        Route::get('/is-user-admin', [UserController::class, 'isUserAdmin']);
    });
    Route::get('/fetch-user-role', [UserController::class, 'getUserRole']);



    Route::middleware('admin')->group(function () {
        Route::get('/fetch-users', [UsersController::class, 'getUsers']);
        Route::get('/fetch-searched-users', [UsersController::class, 'getSearchedUsers']);
        Route::get('/fetch-users-by-role', [UsersController::class, 'getUsersByRoles']);
        Route::get('/fetch-all-users', [UsersController::class, 'getAllUsers']);
        Route::get('/count-users', [UsersController::class, 'countUsers']);
        Route::post('/change-user-role', [UserController::class, 'changeUserRole']);
        Route::post('/delete-user', [UserController::class, 'deleteUser']);
    });


    Route::middleware('admin_doctor')->group(function () {

        // doctors endpoints
        Route::get('/fetch-doctors', [DoctorController::class, 'getDoctors']);
        Route::post('/add-doctor', [DoctorController::class, 'addDoctor']);
        Route::get('/fetch-searched-doctors', [DoctorController::class, 'getSearchedDoctors']);
        Route::post('/delete-doctor', [DoctorController::class, 'deleteDoctor']);
        Route::get('/fetch-all-doctors', [DoctorController::class, 'getAllDoctors']);
        Route::get('/fetch-departments-in-doctors', [DoctorController::class, 'getAllDepartments']);

        //end doctors endpoints




        // patients endpoints

        Route::post('/add-patient', [PatientController::class, 'addPatient']);
        Route::get('/fetch-patients', [PatientController::class, 'getPatients']);
        Route::get('/fetch-searched-patients', [PatientController::class, 'getSearchedPatients']);
        Route::post('/delete-patient', [PatientController::class, 'deletePatient']);
        Route::get('/fetch-all-patients', [PatientController::class, 'getAllPatients']);
        Route::post('/fetch-patient-appointments-and-orders', [PatientController::class, 'getPatientAppointmentsAndOrders']);

        //end patients endpoint


        // fetch appointments  endpoints
        Route::get('/fetch-appointments', [AppointmentController::class, 'getAppointments']);
        Route::get('/fetch-searched-appointments', [AppointmentController::class, 'getSearchedAppointments']);
        Route::get('/fetch-appointments-by-status', [AppointmentController::class, 'getAppointmentsByStatus']);
        Route::get('/fetch-all-appointments', [AppointmentController::class, 'getAllAppointments']);
        Route::get('/count-appointments', [AppointmentController::class, 'countAppointments']);
        Route::post('/change-appointment-status', [AppointmentController::class, 'changeAppointmentStatus']);
        Route::post('/delete-appointment', [AppointmentController::class, 'deleteAppointment']);
        Route::post('/update-appointment', [AppointmentController::class, 'updateAppointment']);
        // end fetch appointments endpoints
    });


    Route::middleware('admin_nurse')->group(function () {


        // blood bank endpoints

        Route::post('/add-blood', [BloodBankController::class, 'addBlood']);
        Route::get('/fetch-bloods', [BloodBankController::class, 'getBloods']);
        Route::get('/fetch-searched-bloods', [BloodBankController::class, 'getSearchedBloods']);
        Route::post('/delete-blood', [BloodBankController::class, 'deleteBlood']);
        Route::get('/fetch-all-bloods', [BloodBankController::class, 'getAllBloods']);
        Route::post('/update-blood', [BloodBankController::class, 'updateBlood']);

        //end blood bank endpoints


        // blood donor endpoints

        Route::post('/add-blood-donor', [BloodDonorController::class, 'addBloodDonor']);
        Route::get('/fetch-blood-donors', [BloodDonorController::class, 'getBloodDonors']);
        Route::get('/fetch-searched-blood-donors', [BloodDonorController::class, 'getSearchedBloodDonors']);
        Route::post('/delete-blood-donor', [BloodDonorController::class, 'deleteBloodDonor']);
        Route::get('/fetch-all-blood-donors', [BloodDonorController::class, 'getAllBloodDonors']);
        Route::post('/update-blood-donor', [BloodDonorController::class, 'updateBloodDonor']);

        //end blood bank endpoints


        // operation report endpoints

        Route::post('/add-operation-report', [OperationReportController::class, 'addOperationReport']);
        Route::get('/fetch-operation-reports', [OperationReportController::class, 'getOperationReports']);
        Route::get('/fetch-searched-operation-reports', [OperationReportController::class, 'getSearchedOperationReports']);
        Route::post('/delete-operation-report', [OperationReportController::class, 'deleteOperationReport']);
        Route::get('/fetch-all-operation-reports', [OperationReportController::class, 'getAllOperationReports']);
        Route::post('/update-operation-report', [OperationReportController::class, 'updateOperationReport']);
        Route::get('/fetch-departments-in-operation-reports', [DoctorController::class, 'getAllDepartments']);

        //end operation report endpoints



        // birth report endpoints

        Route::post('/add-birth-report', [BirthReportController::class, 'addBirthReport']);
        Route::get('/fetch-birth-reports', [BirthReportController::class, 'getBirthReports']);
        Route::get('/fetch-searched-birth-reports', [BirthReportController::class, 'getSearchedBirthReports']);
        Route::post('/delete-birth-report', [BirthReportController::class, 'deleteBirthReport']);
        Route::get('/fetch-all-birth-reports', [BirthReportController::class, 'getAllBirthReports']);
        Route::post('/update-birth-report', [BirthReportController::class, 'updateBirthReport']);

        //end birth report endpoints


        // death report endpoints

        Route::post('/add-death-report', [DeathReportController::class, 'addDeathReport']);
        Route::get('/fetch-death-reports', [DeathReportController::class, 'getDeathReports']);
        Route::get('/fetch-searched-death-reports', [DeathReportController::class, 'getSearchedDeathReports']);
        Route::post('/delete-death-report', [DeathReportController::class, 'deleteDeathReport']);
        Route::get('/fetch-all-death-reports', [DeathReportController::class, 'getAllDeathReports']);
        Route::post('/update-death-report', [DeathReportController::class, 'updateDeathReport']);

        //end death report endpoints


        // vaccine endpoints

        Route::post('/add-vaccine', [VaccineController::class, 'addVaccine']);
        Route::get('/fetch-vaccines', [VaccineController::class, 'getVaccines']);
        Route::get('/fetch-searched-vaccines', [VaccineController::class, 'getSearchedVaccines']);
        Route::post('/delete-vaccine', [VaccineController::class, 'deleteVaccine']);
        Route::get('/fetch-all-vaccines', [VaccineController::class, 'getAllVaccines']);
        Route::post('/update-vaccine', [VaccineController::class, 'updateVaccine']);

        //end vaccine endpoints

    });

    Route::middleware('admin_doctor_nurse')->group(function () {
        // bed allotment endpoints

        Route::post('/add-bed-allotment', [BedAllotmentController::class, 'addBedAllotment']);
        Route::get('/fetch-bed-allotments', [BedAllotmentController::class, 'getBedAllotments']);
        Route::get('/fetch-searched-bed-allotments', [BedAllotmentController::class, 'getSearchedBedAllotments']);
        Route::post('/delete-bed-allotment', [BedAllotmentController::class, 'deleteBedAllotment']);
        Route::get('/fetch-all-bed-allotments', [BedAllotmentController::class, 'getAllBedAllotments']);
        Route::post('/update-bed-allotment', [BedAllotmentController::class, 'updateBedAllotment']);

        //end bed allotment endpoints
    });



    Route::middleware('admin_nurse_pharmacist')->group(function () {
        // medicine endpoints

        Route::post('/add-medicine', [MedicineController::class, 'addMedicine']);
        Route::get('/fetch-medicines', [MedicineController::class, 'getMedicines']);
        Route::get('/fetch-searched-medicines', [MedicineController::class, 'getSearchedMedicines']);
        Route::post('/delete-medicine', [MedicineController::class, 'deleteMedicine']);
        Route::get('/fetch-all-medicines', [MedicineController::class, 'getAllMedicines']);
        Route::post('/update-medicine', [MedicineController::class, 'updateMedicine']);

        //end bed allotment endpoints
    });

    Route::middleware('pharmacist')->group(function () {
        Route::get('/pharmacist-dashboard-data', [PharmacistController::class, 'getAllPharmacistDashboardData']);
    });
    Route::middleware('patient')->group(function () {
        Route::get('/patient-dashboard-data', [PatientController::class, 'getPatientDashboardData']);
        Route::get('/fetch-patient-appointments', [PatientController::class, 'getPatientAppointments']);
        Route::get('/fetch-searched-patient-appointments', [PatientController::class, 'getSearchedPatientAppointments']);
        Route::get('/fetch-patient-appointments-by-status', [PatientController::class, 'getPatientAppointmentsByStatus']);
        Route::get('/count-patient-appointments', [PatientController::class, 'countPatientAppointments']);
    
    

        Route::get('/fetch-patient-orders', [PatientController::class, 'getOrders']);
        Route::get('/fetch-searched-patient-orders', [PatientController::class, 'getSearchedOrders']);
        Route::get('/fetch-patient-orders-by-status', [PatientController::class, 'getOrdersByStatus']);
        Route::get('/fetch-all-patient-orders', [PatientController::class, 'getAllOrders']);
        Route::get('/count-patient-orders', [PatientController::class, 'countOrders']);
    });
    Route::middleware('admin')->group(function () {
        // departments endpoints
        Route::post('/add-department', [DepartmentsController::class, 'addDepartment']);
        Route::get('/fetch-departments', [DepartmentsController::class, 'getDepartments']);
        Route::get('/fetch-searched-departments', [DepartmentsController::class, 'getSearchedDepartments']);
        Route::get('/delete-department', [DepartmentsController::class, 'deleteDepartment']);
        Route::post('/edit-department', [DepartmentsController::class, 'editDepartment']);
        Route::get('/fetch-all-departments', [DepartmentsController::class, 'getAllDepartments']);
        //end departments endpoints



        // nurses endpoints

        Route::post('/add-nurse', [NurseController::class, 'addNurse']);
        Route::get('/fetch-nurses', [NurseController::class, 'getNurses']);
        Route::get('/fetch-searched-nurses', [NurseController::class, 'getSearchedNurses']);
        Route::post('/delete-nurse', [NurseController::class, 'deleteNurse']);
        Route::get('/fetch-all-nurses', [NurseController::class, 'getAllNurses']);

        //end nurses endpoints


        // laboratorists endpoints

        Route::post('/add-laboratorist', [LaboratoristController::class, 'addLaboratorist']);
        Route::get('/fetch-laboratorists', [LaboratoristController::class, 'getLaboratorists']);
        Route::get('/fetch-searched-laboratorists', [LaboratoristController::class, 'getSearchedLaboratorists']);
        Route::post('/delete-laboratorist', [LaboratoristController::class, 'deleteLaboratorist']);
        Route::get('/fetch-all-laboratorists', [LaboratoristController::class, 'getAllLaboratorists']);

        //end laboratorists endpoints


        // pharmacists endpoints

        Route::post('/add-pharmacist', [PharmacistController::class, 'addPharmacist']);
        Route::get('/fetch-pharmacists', [PharmacistController::class, 'getPharmacists']);
        Route::get('/fetch-searched-pharmacists', [PharmacistController::class, 'getSearchedPharmacists']);
        Route::post('/delete-pharmacist', [PharmacistController::class, 'deletePharmacist']);
        Route::get('/fetch-all-pharmacists', [PharmacistController::class, 'getAllPharmacists']);

        //end laboratorists endpoints


        // service endpoints

        Route::post('/add-service', [ServiceController::class, 'addService']);
        Route::get('/fetch-services', [ServiceController::class, 'getServices']);
        Route::get('/fetch-searched-services', [ServiceController::class, 'getSearchedServices']);
        Route::post('/delete-service', [ServiceController::class, 'deleteService']);
        Route::get('/fetch-all-services', [ServiceController::class, 'getAllServices']);
        Route::post('/update-service', [ServiceController::class, 'updateService']);

        //end service endpoints


        // offer endpoints

        Route::post('/add-offer', [OfferController::class, 'addOffer']);
        Route::get('/fetch-offers', [OfferController::class, 'getOffers']);
        Route::get('/fetch-searched-offers', [OfferController::class, 'getSearchedOffers']);
        Route::post('/delete-offer', [OfferController::class, 'deleteOffer']);
        Route::get('/fetch-all-offers', [OfferController::class, 'getAllOffers']);
        Route::post('/update-offer', [OfferController::class, 'updateOffer']);
        // Route::post('/update-services', [OfferController::class, 'getServices']);
        Route::get('/fetch-services-in-offers', [OfferController::class, 'getServices']);

        //end offer endpoints


        // fetch Orders  endpoints
        Route::get('/fetch-orders', [OrderController::class, 'getOrders']);
        Route::get('/fetch-searched-orders', [OrderController::class, 'getSearchedOrders']);
        Route::get('/fetch-orders-by-status', [OrderController::class, 'getOrdersByStatus']);
        Route::get('/fetch-all-orders', [OrderController::class, 'getAllOrders']);
        Route::get('/count-orders', [OrderController::class, 'countOrders']);
        // Route::post('/change-order-status', [OrderController::class, 'changeOrderStatus']);
        Route::post('/delete-order', [OrderController::class, 'deleteOrder']);
        Route::post('/update-order', [OrderController::class, 'updateOrder']);
        // end fetch Orders endpoints



        // order notifications endpoints
        Route::get('/read-order-notifications', [OrderNotificationController::class, 'setNotificationsToRead']);
        // end order notifications endpoints



        // appointments events endpoints
        Route::get('/appointments-events', [AppointmentController::class, 'getEvents']);
        Route::get('/doctor-appointments-for-charts', [AppointmentController::class, 'getAppointmentsForDoctorCharts']);
        Route::get('/doctor-upcoming-appointments', [AppointmentController::class, 'getUpcomingAppointmentsAndTopPayingPatientsForDoctorCharts']);
        Route::get('/admin-appointments-for-charts', [AppointmentController::class, 'getAppointmentsAndOrdersStatsForAdminCharts']);

        // end appointments events endpoints


    });



    //Our offers endpoints 
    Route::get('/fetch-our-offers', [OurOffersController::class, 'getOffers']);
    //end our offers endpoints


    //Order Checkout endpoints 
    Route::post('/order-checkout', [PaymentController::class, 'orderCheckout']);
    Route::post('/order-success-payment', [PaymentController::class, 'success']);
    //end Order Checkout endpoints


    Route::middleware(['doctor'])->group(function () {


        // doctor schedules endpoints
        Route::post('/doctor-schedules-set-times', [ScheduleController::class, 'setTimes']);
        Route::get('/doctor-schedules-fetch-times', [ScheduleController::class, 'getTimes']);
        Route::get('/doctor-schedules-fetch-availability', [ScheduleController::class, 'getAvailability']);
        Route::post('/doctor-schedules-toggle-availability', [ScheduleController::class, 'toggleAvailability']);
        // end doctor schedules endpoints



        // fetch doctor appointments endpoints
        Route::get('/fetch-doctor-appointments', [DoctorAppointmentController::class, 'getDoctorAppointments']);
        Route::get('/fetch-searched-doctor-appointments', [DoctorAppointmentController::class, 'getSearchedDoctorAppointments']);
        Route::get('/fetch-doctor-appointments-by-status', [DoctorAppointmentController::class, 'getDoctorAppointmentsByStatus']);
        Route::get('/fetch-all-doctor-appointments', [DoctorAppointmentController::class, 'getAllDoctorAppointments']);
        Route::get('/count-doctor-appointments', [DoctorAppointmentController::class, 'countDoctorAppointments']);
        // Route::post('/change-doctor-appointment-status', [DoctorAppointmentController::class, 'changeDoctorAppointmentStatus']);
        Route::post('/delete-doctor-appointment', [DoctorAppointmentController::class, 'deleteDoctorAppointment']);
        Route::post('/update-doctor-appointment', [DoctorAppointmentController::class, 'updateDoctorAppointment']);
        // end fetch doctor appointments endpoints


        // order notifications endpoints
        Route::get('/read-appointment-notifications', [AppointmentController::class, 'setNotificationsToRead']);
        // end order notifications endpoints


        // appointments events endpoints
        Route::get('/appointments-events', [AppointmentController::class, 'getEvents']);
        Route::get('/doctor-appointments-for-charts', [AppointmentController::class, 'getAppointmentsForDoctorCharts']);
        Route::get('/doctor-upcoming-appointments', [AppointmentController::class, 'getUpcomingAppointmentsAndTopPayingPatientsForDoctorCharts']);
        // end appointments events endpoints
    });


    Route::middleware(['nurse'])->group(function () {
        Route::get('/nurse-dashboard-data', [NurseController::class, 'getAllNurseDashboardData']);
    });


    // appointment reservasion endpoints
    Route::get('/appointments-fetch-departments', [AppointmentReservation::class, 'getDepartments']);
    Route::post('/appointments-fetch-doctors', [AppointmentReservation::class, 'getDoctors']);
    Route::post('/appointments-search-availability', [AppointmentReservation::class, 'searchAvailability']);
    Route::post('/appointments-register-appointment', [AppointmentReservation::class, 'registerAppointment']);
    Route::post('/appointment-success-payment', [AppointmentReservation::class, 'success']);
    // end appointment reservasion endpoints




    // Route::get('/auth/facebook', [AuthenticatedSessionController::class, 'authFacebook'])->name('authFacebook');
    // Route::get('/auth/facebook/callback', [AuthenticatedSessionController::class, 'callBackSocialite'])->name('callBackSocialite');

    // Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
    //     ->middleware(['auth', 'signed', 'throttle:6,1'])
    //     ->name('verification.verify');



    // Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    //     ->middleware(['auth', 'throttle:6,1'])
    //     ->name('verification.send');

    // End Authentication EndPoints

    // Route::get('/users/{limit}/{skip}', [UsersController::class, 'index']);
    Route::get('/check-user-auth', [UserController::class, 'checkAuth']);
    Route::get('/get-user-data', [UserController::class, 'getUserData']);

    Route::get('/sse', [SseController::class, 'streamNotifications']);


    // Route::get('/user', [AuthUserController::class, 'getUserInfo']);
});
