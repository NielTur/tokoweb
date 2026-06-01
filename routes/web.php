<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Models\Order;
use Illuminate\Auth\Events\Logout;
use App\Http\Controllers\RajaOngkirController;
use App\Http\Controllers\Backend\PesananAdminController;

Route::get('/', function () {
    // return view('welcome');
    return redirect()->route('beranda');
});
Route::get('backend/beranda', [BerandaController::class, 'berandaBackend'])->name('backend.beranda')->middleware('auth');

Route::get('backend/login', [LoginController::class, 'loginBackend'])->name('backend.login');
Route::post('backend/login', [LoginController::class, 'authenticateBackend'])->name('backend.login');
Route::post('backend/logout', [LoginController::class, 'logoutBackend'])->name('backend.logout');

// Route untuk User
// Route::resource('backend/user', UserController::class)->middleware('auth');
Route::resource('backend/user', UserController::class, ['as' => 'backend'])->middleware('auth');
// Route untuk laporan user
Route::get('backend/laporan/formuser', [UserController::class, 'formUser'])->name('backend.laporan.formuser')->middleware('auth');
Route::post('backend/laporan/cetakuser', [UserController::class, 'cetakUser'])->name('backend.laporan.cetakuser')->middleware('auth');

// Route untuk Kategori
Route::resource('backend/kategori', KategoriController::class, ['as' => 'backend'])->middleware('auth');

// Route untuk Produk
Route::resource('backend/produk', ProdukController::class, ['as' => 'backend'])->middleware('auth');
// Route untuk menambahkan foto
Route::post('foto-produk/store', [ProdukController::class, 'storeFoto'])->name('backend.foto_produk.store')->middleware('auth');
// Route untuk menghapus foto
Route::delete('foto-produk/{id}', [ProdukController::class, 'destroyFoto'])->name('backend.foto_produk.destroy')->middleware('auth');

// Route untuk laporan produk
Route::get('backend/laporan/formproduk', [ProdukController::class, 'formProduk'])->name('backend.laporan.formproduk')->middleware('auth');
Route::post('backend/laporan/cetakproduk', [ProdukController::class, 'cetakProduk'])->name('backend.laporan.cetakproduk')->middleware('auth');

Route::get('backend/pesanan/proses', [OrderController::class, 'statusProses'])->name('pesanan.proses')->middleware('auth');
Route::get('backend/pesanan/selesai', [OrderController::class, 'statusSelesai'])->name('pesanan.selesai')->middleware('auth');
Route::get('backend/pesanan/detail/{id}', [OrderController::class, 'statusDetail'])->name('pesanan.detail')->middleware('auth');
Route::post('backend/pesanan/update/{id}', [OrderController::class, 'statusUpdate'])->name('pesanan.update')->middleware('auth');


Route::group(['middleware' => 'auth'], function () {
    // Route Admin untuk mengelola pesanan
    Route::get('backend/pesanan', [PesananAdminController::class, 'index'])->name('backend.pesanan.index');
    Route::get('backend/pesanan/{id}', [PesananAdminController::class, 'show'])->name('backend.pesanan.show');
    Route::post('backend/pesanan/{id}/update-status', [PesananAdminController::class, 'updateStatus'])->name('backend.pesanan.updateStatus');
});


// Frontend
Route::get('/beranda', [BerandaController::class, 'index'])->name('beranda');
Route::get('/produk/detail/{id}', [ProdukController::class, 'detail'])->name('produk.detail');
Route::get('/produk/kategori/{id}', [ProdukController::class, 'produkKategori'])->name('produk.kategori');
Route::get('/produk/all', [ProdukController::class, 'produkAll'])->name('produk.all');

//API google
Route::get('/auth/redirect', [CustomerController::class, 'redirect'])->name('auth.redirect');
Route::get('/auth/google/callback', [CustomerController::class, 'callback'])->name('auth.callback');

//Logout
Route::post('/logout', [CustomerController::class, 'logout'])->name('customer.logout');

//Route untuk customer
Route::resource('backend/customer', CustomerController::class, ['as' => 'backend'])->middleware('auth');

// Group route untuk customer
Route::middleware('is.customer')->group(function () {
    // Route untuk menampilkan halaman akun customer
    Route::get('/customer/akun/{id}', [CustomerController::class, 'akun'])->name('customer.akun');
    // Route untuk mengupdate data akun customer
    Route::put('/customer/updateakun/{id}', [CustomerController::class, 'updateAkun'])->name('customer.updateakun');
    // Route untuk menambahkan produk ke keranjang
    Route::get('cart', [OrderController::class, 'viewCart'])->name('order.cart');
    Route::post('add-to-cart/{id}', [OrderController::class, 'addToCart'])->name('order.addToCart');
    Route::post('cart/update/{id}', [OrderController::class, 'updateCart'])->name('order.updateCart');
    Route::post('remove/{id}', [OrderController::class, 'removeFromCart'])->name('order.remove');
    // rajaongkir
    Route::post('select-shipping', [OrderController::class, 'selectShipping'])->name('order.selectShipping');
    Route::post('update-ongkir', [OrderController::class, 'updateOngkir'])->name('order.update-ongkir');
    // pembayaran
    Route::get('select-payment', [OrderController::class, 'selectPayment'])->name('order.selectpayment');
    Route::post('order/complete', [OrderController::class, 'complete'])->name('order.complete');
    Route::get('history', [OrderController::class, 'orderHistory'])->name('order.history');
});

// API RAJAONGKIR
Route::get('/list-ongkir', function () {
    $response = Http::withHeaders([
        'key' => 'RAJAONGKIR_API_KEY'
    ])->get('https://rajaongkir.komerce.id/api/v1/destination/province'); //ganti 'province' atau 'city' 
    dd($response->json());
});
Route::get('/cek-ongkir', function () {
    return view('ongkir');
});


//Ongkir
Route::post('select-shipping', [OrderController::class, 'selectShipping'])->name('order.selectShipping');
Route::get('provinces', [RajaOngkirController::class, 'getProvinces']);
Route::get('cities', [RajaOngkirController::class, 'getCities']);
Route::post('cost', [RajaOngkirController::class, 'getCost']);
Route::post('updateongkir', [OrderController::class, 'updateongkir'])->name('order.updateongkir');
Route::post('get-ongkir', [RajaOngkirController::class, 'getOngkir']);
