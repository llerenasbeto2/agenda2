<?php
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SamlController;
use App\Http\Controllers\AdminSuperController;
use App\Http\Controllers\AdminController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\StatisticController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\MyReservationsclassroomController;
use App\Http\Controllers\CalendarioController;

use Inertia\Inertia;
use App\Http\Controllers\EscenariosDisponiblesController;
use App\Http\Controllers\AdminEstatalEscenariosController;
use App\Http\Controllers\WelcomeController;


use App\Http\Controllers\Estatal\EstatalCategoryController;
use App\Http\Controllers\Estatal\EstatalUsersController;
use App\Http\Controllers\Estatal\EstatalStatisticsController;
use App\Http\Controllers\Estatal\Estatal_Reservation_ClassroomController;
use App\Http\Controllers\Estatal\EstatalFacultyController;

use App\Http\Controllers\Area\AStatisticController;
use App\Http\Controllers\Area\Area_Reservation_ClassroomController;
use App\Http\Controllers\Users\UsersReservationsController;
use App\Http\Controllers\Users\QuejasController;


use App\Http\Controllers\General\GeneralStatisticsController;
use App\Http\Controllers\General\Reservation_ClassroomController;
use App\Http\Controllers\General\ReservationCommentController;
use App\Http\Controllers\General\AdminGeneralEscenariosController;
use App\Http\Controllers\General\FacultyController;
//use App\Http\Controllers\WelcomeController;

Route::prefix('api')->middleware(['api'])->group(function () {
    Route::get('/faculties/{faculty}/classrooms', 
        [EscenariosDisponiblesController::class, 'getClassroomsByFaculty'])
        ->name('api.faculties.classrooms');

    Route::get('/approved-reservations', [\App\Http\Controllers\General\Reservation_ClassroomController::class, 'getApprovedReservations'])
        ->name('api.approved.reservations');
});

   /* Route::get('/', function () {
        return Inertia::render('Welcome', [
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('home');*/

    
    //Route::get('/', [WelcomeController::class, 'index']);
    Route::get('/', [WelcomeController::class, 'index'])->name('home');
    //Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

    Route::get('/presentacion', function () {
        return Inertia::render('presentacion');
    });
    
    // Otras rutas específicas para usuarios
    Route::get('/entrenamiento', function () {
        return Inertia::render('Entrenamiento');
    })->name('entrenamiento');
    
    // Rutas de escenarios disponibles para usuarios
    Route::get('/escenariosDisponibles', [EscenariosDisponiblesController::class, 'index'])
        ->name('escenariosDisponibles.index');

        Route::get('/quejas', [QuejasController::class, 'index'])->name('quejas.index');
        Route::post('/quejas', [QuejasController::class, 'store'])->name('quejas.store');
        // Rutas de los chicos
    Route::get('/agendar-evento', [MyReservationsclassroomController::class, 'create'])
    ->name('agenda.evento.public');


    
    // Rutas para obtener datos dinámicos (disponibles para todos los usuarios)   //ESTO MODIFICQUE BETO:
Route::get('/faculties-by-municipality', [MyreservationsclassroomController::class, 'getFaculties'])
    ->name('faculties.by.municipality');

Route::get('/classrooms-by-faculty', [MyreservationsclassroomController::class, 'getClassrooms'])
    ->name('classrooms.by.faculty');

// Ruta para obtener reservaciones existentes de un aula (disponible para todos)
Route::get('/existing-reservations', [MyreservationsclassroomController::class, 'getExistingReservations'])
    ->name('reservations.existing');

// Ruta para verificar conflictos de horarios (disponible para todos)
Route::post('/check-reservation-conflicts', [MyreservationsclassroomController::class, 'checkConflicts'])
    ->name('reservations.check.conflicts');

Route::post('/myreservationsclassroom', [MyReservationsclassroomController::class, 'store'])
    ->name('myreservationsclassroom.store');
//SOLO LAS RUTAS DE ARRIBA BETO


    Route::get('/quejas-sugerencias', [QuejasController::class, 'index'])
    ->name('quejas.sugerencias');




// Rutas de SAML
Route::get('/saml/callback', [SamlController::class, 'callback'])->name('saml.callback');

// Rutas comunes para cualquier usuario autenticado
Route::middleware(['auth', 'verified'])->group(function () {
    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/classrooms', [MyReservationsclassroomController::class, 'getClassrooms'])
    ->name('api.classrooms');
    //Ruta para que se muestren las imágenes en escenarios disponibles
    Route::post('/classrooms/{classroom}/image-url', [EscenariosDisponiblesController::class, 'updateImageUrl'])
     ->name('classrooms.image-url.update')
     ->middleware('auth');
});

// Rutas específicas para usuarios comunes
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard principal para usuarios normales
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::post('/reservations/classrooms', [UsersReservationsController::class, 'store'])
        ->name('reservations.classrooms.store');

    //Ruta de usuarios especificos Reacomodar despues de pruebas
    Route::get('/misReservaciones', [UsersReservationsController::class, 'index'])
        ->name('misReservaciones.index');

    // Ruta para crear eventos (solo autenticados)
    Route::post('/eventos', [MyReservationsclassroomController::class, 'store'])->name('eventos.store');
});


Route::post('/check-reservation-conflicts', [MyreservationsclassroomController::class, 'checkConflicts']);

// Rutas para cualquier administrador (General, Estatal o Área)
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard');
    })->name('admin.dashboard');

    // Rutas comunes para todos los administradores
    Route::get('/escenariosDisponibles', [EscenariosDisponiblesController::class, 'index'])
        ->name('admin.escenariosDisponibles.index');
});

// Rutas exclusivas para Administrador General
Route::middleware(['auth', 'verified', 'admin.general'])->prefix('admin/general')->group(function () {
    

    
        // Rutas de reservaciones
    Route::get('/reservaciones', [Reservation_ClassroomController::class, 'index'])->name('admin.general.dashboard');
    Route::delete('/reservaciones/{id}', [Reservation_ClassroomController::class, 'destroy'])->name('admin.general.events.destroy');
    Route::post('/reservaciones', [Reservation_ClassroomController::class, 'store'])->name('admin.general.events.store');
    Route::patch('/reservaciones/{id}/estado', [Reservation_ClassroomController::class, 'cambiarEstado'])->name('admin.general.events.cambiarEstado');
    Route::get('/reservaciones/{reservacion}/edit', [Reservation_ClassroomController::class, 'edit'])->name('admin.general.events.edit');
    Route::put('/reservaciones/{reservacion}', [Reservation_ClassroomController::class, 'update'])->name('admin.general.events.update');
    Route::post('/reservations/send-comment', [Reservation_ClassroomController::class, 'sendComment'])->name('admin.general.events.send-comment');
    Route::post('events/update-payment', [Reservation_ClassroomController::class, 'updatePayment'])->name('admin.general.events.update-payment');

    // Acceso completo al CRUD de escenarios
    Route::get('/escenariosDisponibles', [AdminGeneralEscenariosController::class, 'index'])
        ->name('admin.general.escenariosDisponibles.index');
    Route::get('/escenariosDisponibles/create', [AdminGeneralEscenariosController::class, 'create'])
        ->name('admin.general.escenariosDisponibles.create');
    Route::post('/escenariosDisponibles', [AdminGeneralEscenariosController::class, 'store'])
        ->name('admin.general.escenariosDisponibles.store');
    Route::get('/escenariosDisponibles/{space}/edit', [AdminGeneralEscenariosController::class, 'edit'])
        ->name('admin.general.escenariosDisponibles.edit');
    Route::put('/escenariosDisponibles/{space}', [AdminGeneralEscenariosController::class, 'update'])
        ->name('admin.general.escenariosDisponibles.update');
    Route::delete('/escenariosDisponibles/{space}', [AdminGeneralEscenariosController::class, 'destroy'])
        ->name('admin.general.escenariosDisponibles.destroy');
      

    // Acceso completo al CRUD de categorias
    Route::get('/categories', [CategoryController::class, 'index'])
        ->name('admin.general.categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])
        ->name('admin.general.categories.create');
    Route::post('/categories', [CategoryController::class, 'store'])
        ->name('admin.general.categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
        ->name('admin.general.categories.edit');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])
        ->name('admin.general.categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
        ->name('admin.general.categories.destroy');    

    // Acceso completo al CRUD de roles
  /*  Route::get('/roles', [RoleController::class, 'index'])
        ->name('admin.general.roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])
        ->name('admin.general.roles.create');
    Route::post('/roles', [RoleController::class, 'store'])
        ->name('admin.general.roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
        ->name('admin.general.roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])
        ->name('admin.general.roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
        ->name('admin.general.roles.destroy'); */
        
       


    Route::get('/faculties', [FacultyController::class, 'index'])->name('admin.general.faculties.index');
    Route::get('/faculties/create', [FacultyController::class, 'create'])->name('admin.general.faculties.create');
    Route::post('/faculties', [FacultyController::class, 'store'])->name('admin.general.faculties.store');
    Route::get('/faculties/{faculty}/edit', [FacultyController::class, 'edit'])->name('admin.general.faculties.edit');
    Route::put('/faculties/{faculty}', [FacultyController::class, 'update'])->name('admin.general.faculties.update');
    Route::delete('/faculties/{faculty}', [FacultyController::class, 'destroy'])->name('admin.general.faculties.destroy');




    Route::get('/usuarios', [UsersController::class, 'index'])
        ->name('admin.general.usuarios.index');
    //Route::get('/usuarios/create', [UsersController::class, 'create'])
      //  ->name('admin.general.usuarios.create');
   // Route::post('/usuarios', [UsersController::class, 'store'])
    //    ->name('admin.general.usuarios.store');
    Route::get('/usuarios/{usuario}/edit', [UsersController::class, 'edit'])
        ->name('admin.general.usuarios.edit');
    Route::put('/usuarios/{usuario}', [UsersController::class, 'update'])
        ->name('admin.general.usuarios.update');
    Route::delete('/usuarios/{usuario}', [UsersController::class, 'destroy'])
        ->name('admin.general.usuarios.destroy'); 
        
    // Acceso completo al Estaditicas ADMIN-GENERAL
    Route::get('/statistics', [GeneralStatisticsController::class, 'index'])
        ->name('admin.general.statistics.index');
});

// Rutas exclusivas para Administrador Estatal
Route::middleware(['auth', 'verified', 'admin.estatal'])->prefix('admin/estatal')->group(function () {
    //Route::get('/dashboard', [Estatal_Reservation_ClassroomController::class, 'index'])
    //->name('admin.estatal.dashboard.index');
/*
    Route::get('/reservaciones', [Estatal_Reservation_ClassroomController::class, 'index'])->name('admin.estatal.dashboard.index');
    Route::delete('/reservaciones/{id}', [Estatal_Reservation_ClassroomController::class, 'destroy'])->name('admin.estatal.events.destroy');
    Route::post('/reservaciones', [Estatal_Reservation_ClassroomController::class, 'store'])->name('admin.estatal.events.store');
    Route::patch('/reservaciones/{id}/estado', [Estatal_Reservation_ClassroomController::class, 'cambiarEstado'])->name('admin.estatal.events.cambiarEstado');
    Route::get('/reservaciones/{reservacion}/edit', [Estatal_Reservation_ClassroomController::class, 'edit'])->name('admin.estatal.events.edit');
    Route::put('/reservaciones/{reservacion}', [Estatal_Reservation_ClassroomController::class, 'update'])->name('admin.estatal.events.update');
    Route::post('/reservations/send-comment', [Estatal_Reservation_ClassroomController::class, 'sendComment'])->name('admin.estatal.events.send-comment');
    Route::post('events/update-payment', [Estatal_Reservation_ClassroomController::class, 'updatePayment'])->name('admin.estatal.events.update-payment');
*/


    Route::get('/dashboard', [Estatal_Reservation_ClassroomController::class, 'index'])->name('admin.estatal.dashboard.index');
    Route::patch('/events/{id}/cambiarEstado', [Estatal_Reservation_ClassroomController::class, 'cambiarEstado'])->name('admin.estatal.events.cambiarEstado');
    Route::get('/events/{reservacion}/edit', [Estatal_Reservation_ClassroomController::class, 'edit'])->name('admin.estatal.events.edit');
    Route::put('/events/{reservacion}', [Estatal_Reservation_ClassroomController::class, 'update'])->name('admin.estatal.events.update');
    Route::delete('/events/{id}', [Estatal_Reservation_ClassroomController::class, 'destroy'])->name('admin.estatal.events.destroy');
    Route::post('/events', [Estatal_Reservation_ClassroomController::class, 'store'])->name('admin.estatal.events.store');
    Route::post('/events/comment', [Estatal_Reservation_ClassroomController::class, 'sendComment'])->name('admin.estatal.events.comment');
    Route::get('/reservations/approved', [Estatal_Reservation_ClassroomController::class, 'getApprovedReservations'])->name('admin.estatal.reservations.approved');
    Route::post('events/update-payment', [Estatal_Reservation_ClassroomController::class, 'updatePayment'])->name('admin.estatal.events.update-payment');
    // Acceso limitado a escenarios (sin eliminar)

    /*
    Route::get('/escenariosDisponibles', [AdminEstatalEscenariosController::class, 'index'])
        ->name('admin.estatal.escenariosDisponibles.index');
    Route::get('/escenariosDisponibles/create', [AdminEstatalEscenariosController::class, 'create'])
        ->name('admin.estatal.escenariosDisponibles.create');
    Route::post('/escenariosDisponibles', [AdminEstatalEscenariosController::class, 'store'])
        ->name('admin.estatal.escenariosDisponibles.store');
    Route::get('/escenariosDisponibles/{space}/edit', [AdminEstatalEscenariosController::class, 'edit'])
        ->name('admin.estatal.escenariosDisponibles.edit');
    Route::put('/escenariosDisponibles/{space}', [AdminEstatalEscenariosController::class, 'update'])
        ->name('admin.estatal.escenariosDisponibles.update');
    Route::delete('/escenariosDisponibles/{space}', [AdminEstatalEscenariosController::class, 'destroy'])
        ->name('admin.estatal.escenariosDisponibles.destroy');*/

    Route::get('/faculties', [EstatalFacultyController::class, 'index'])->name('admin.estatal.faculties.index');
    Route::get('/classrooms/create', [EstatalFacultyController::class, 'createClassroom'])->name('admin.estatal.faculties.create');
    Route::post('/classrooms', [EstatalFacultyController::class, 'storeClassroom'])->name('admin.estatal.faculties.store');
    Route::get('/classrooms/{classroom}/edit', [EstatalFacultyController::class, 'editClassroom'])->name('admin.estatal.faculties.edit');
    Route::put('/classrooms/{classroom}', [EstatalFacultyController::class, 'updateClassroom'])->name('admin.estatal.faculties.update');
    Route::delete('/classrooms/{classroom}', [EstatalFacultyController::class, 'destroyClassroom'])->name('admin.estatal.faculties.destroy');

    // Acceso completo al CRUD de categorias
    Route::get('/categories', [EstatalCategoryController::class, 'index'])
        ->name('admin.estatal.categories.index');
    Route::get('/categories/create', [EstatalCategoryController::class, 'create'])
        ->name('admin.estatal.categories.create');
    Route::post('/categories', [EstatalCategoryController::class, 'store'])
        ->name('admin.estatal.categories.store');
    Route::get('/categories/{category}/edit', [EstatalCategoryController::class, 'edit'])
        ->name('admin.estatal.categories.edit');
    Route::put('/categories/{category}', [EstatalCategoryController::class, 'update'])
        ->name('admin.estatal.categories.update');
    Route::delete('/categories/{category}', [EstatalCategoryController::class, 'destroy'])
        ->name('admin.estatal.categories.destroy');  

    // Acceso completo al CRUD de usuarios
    Route::get('/usuarios', [EstatalUsersController::class, 'index'])
    ->name('admin.estatal.usuarios.index');
    Route::get('/usuarios/create', [EstatalUsersController::class, 'create'])
    ->name('admin.estatal.usuarios.create');
    Route::post('/usuarios', [EstatalUsersController::class, 'store'])
    ->name('admin.estatal.usuarios.store');
    Route::get('/usuarios/{usuario}/edit', [EstatalUsersController::class, 'edit'])
    ->name('admin.estatal.usuarios.edit');
    Route::put('/usuarios/{usuario}', [EstatalUsersController::class, 'update'])
    ->name('admin.estatal.usuarios.update');
    Route::delete('/usuarios/{usuario}', [EstatalUsersController::class, 'destroy'])
    ->name('admin.estatal.usuarios.destroy'); 



    //Estadisticas
    Route::get('/statistics', [EstatalStatisticsController::class, 'index'])
    ->name('admin.estatal.statistics.index');
});
    

// Rutas exclusivas para Administrador de Área
Route::middleware(['auth', 'verified', 'admin.area'])->prefix('admin/area')->group(function () {
    
    Route::get('/reservaciones', [Area_Reservation_ClassroomController::class, 'index'])->name('admin.area.dashboard');
    Route::delete('/reservaciones/{id}', [Area_Reservation_ClassroomController::class, 'destroy'])->name('admin.area.events.destroy');
    Route::post('/reservaciones', [Area_Reservation_ClassroomController::class, 'store'])->name('admin.area.events.store');
    Route::patch('/reservaciones/{id}/estado', [Area_Reservation_ClassroomController::class, 'cambiarEstado'])->name('admin.area.events.cambiarEstado');
    Route::get('/reservaciones/{reservacion}/edit', [Area_Reservation_ClassroomController::class, 'edit'])->name('admin.area.events.edit');
    Route::put('/reservaciones/{reservacion}', [Area_Reservation_ClassroomController::class, 'update'])->name('admin.area.events.update');
    Route::post('/reservations/send-comment', [Area_Reservation_ClassroomController::class, 'sendComment'])->name('admin.area.events.send-comment');
    Route::post('events/update-payment', [Area_Reservation_ClassroomController::class, 'updatePayment'])->name('admin.area.events.update-payment');
    

    Route::get('/statistics', [AStatisticController::class, 'index'])
    ->name('admin.area.statistics.index');
});

// Ruta para redirigir a los usuarios según su rol después del login
Route::middleware(['auth', 'verified', 'redirect.by.role'])->group(function () {
    // Esta ruta será procesada por el middleware para redirigir según el rol
    Route::get('/redirect', function () {
        return redirect()->route('dashboard');
    })->name('redirect');
});



require __DIR__.'/auth.php';