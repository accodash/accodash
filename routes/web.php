<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/test', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh');
    /** @var \App\Models\Country $country */
    $country = App\Models\Country::create(['name' => 'Poland']);
    /** @var \App\Models\City $city */
    $city = $country->cities()->create(['name' => 'Łódź']);
    /** @var \App\Models\BuildingType $type */
    $type = \App\Models\BuildingType::create(['name' => 'Hotel']);
    /** @var \App\Models\Building $building */
    $building = $city->buildings()->create([
        'name' => 'Test building',
        'description' => 'Test description',
        'address' => 'Kolejowa 13',
        'main_image_url' => 'url.jpg',
        'type_id' => $type->id,
        'city_id' => 2
    ]);
    $building->save();
    /** @var \App\Models\Accommodation $acco */
    $acco = $building->accommodations()->create([
        'info' => 'lore',
        'price' => 3000,
        'adults_capacity' => 3,
        'children_capacity' => 2
    ]);
//    /** @var \App\Models\Booking $booking */
//    $booking = $acco->bookings()->create([
//        'check_in' => now(),
//        'check_out' => now()->addDay()
//    ]);
//    $booking->refresh();
//    dump($booking->created_at);
});
