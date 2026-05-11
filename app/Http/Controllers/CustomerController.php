<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Hash;
use App\Helpers\ImageHelper;

class CustomerController extends Controller
{
    //Redirect to Google
        public function redirect()
        {
            return Socialite::driver('google')->redirect();
        }

        //Callback from Google
        public function callback()
    {
        try {
            $socialUser = Socialite::driver('google')->user();

            // Cek apakah email sudah terdaftar
            $registeredUser = User::where('email', $socialUser->email)->first();

            if (!$registeredUser) {
                // Buat user baru
                $user = User::create([
                    'nama' => $socialUser->name,
                    'email' => $socialUser->email,
                    'role' => '2',
                    'status' => 1,
                    'password' => Hash::make('default_password'),
                ]);

                // Buat data customer
                Customer::create([
                    'user_id' => $user->id,
                    'google_id' => $socialUser->id,
                    'google_token' => $socialUser->token
                ]);

                Auth::login($user);
            } else {
                // Cek apakah data customer sudah ada
                $customer = Customer::where('user_id', $registeredUser->id)->first();
                if (!$customer) {
                    Customer::create([
                        'user_id' => $registeredUser->id,
                        'google_id' => $socialUser->id,
                        'google_token' => $socialUser->token
                    ]);
                }
                Auth::login($registeredUser);
            }

            return redirect()->intended('beranda');

        } catch (\Exception $e) {
            // dd($e->getMessage());
            return redirect('/')->with('error', 'Terjadi kesalahan saat login dengan google.');
        }
}

    public function logout (Request $request)
    {
        Auth::logout(); //logout pengguna
        $request->session()->invalidate(); //Hapus session
        $request->session()->regenerateToken(); //regenerate token CSRF

        return redirect('/')->with('Success', 'Anda telah berhasil logout.');
    }

    public function index()
    {
        $customer = Customer::orderBy('id', 'desc')->get();
        return view('backend.v_customer.index', [
            'judul' => 'Customer',
            'sub' => 'Halaman Customer',
            'index' => $customer
        ]);
    }

    public function create()
    {
        return view('backend.v_customer.create', [
            'judul' => 'Tambah Customer',
            'sub' => 'Tambah Customer'
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nama' => 'required|max:255',
            'email' => 'required|max:255|email|unique:user',
            'role' => 'required',
            'hp' => 'required|min:10|max:13',
            'password' => 'required|min:4|confirmed',
            'foto' => 'image|mimes:jpeg,jpg,png,gif|file|max:1024',
        ], $messages = [
            'foto.image' => 'Format gambar gunakan file dengan ekstensi jpeg, jpg, png, atau gif.',
            'foto.max' => 'Ukuran file gambar Maksimal adalah 1024 KB.'
        ]);

        // Upload foto
        $fotoName = null;
        if ($request->file('foto')) {
            $file = $request->file('foto');
            $extension = $file->getClientOriginalExtension();
            $originalFileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $directory = 'storage/img-customer/';
            ImageHelper::uploadAndResize($file, $directory, $originalFileName, 385, 400);
            $fotoName = $originalFileName;
        }

        // Validasi password
        $password = $request->input('password');
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/';
        if (!preg_match($pattern, $password)) {
            return redirect()->back()->withErrors([
                'password' => 'Password harus terdiri dari kombinasi huruf besar, huruf kecil, angka, dan simbol.'
            ]);
        }

        // Buat User dulu
        $user = User::create([
            'nama' => $validatedData['nama'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => $validatedData['role'],
            'hp' => $validatedData['hp'],
            'status' => 0,
            'foto' => $fotoName,
        ]);

        // Baru buat Customer
        Customer::create([
            'user_id' => $user->id,
        ]);

        return redirect()->route('backend.customer.index')->with('success', 'Data berhasil tersimpan');
    }

    public function show(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('backend.v_customer.show', [
            'judul' => 'Detail Customer',
            'show' => $customer
        ]);
    }

    public function edit(string $id)
    {
        $customer = Customer::findOrFail($id);
        return view('backend.v_customer.edit', [
            'judul' => 'Ubah Customer',
            'edit' => $customer
        ]);
    }

    public function update(Request $request, string $id)
    {
        $customer = Customer::findOrFail($id);
        $user = User::findOrFail($customer->user_id); // ← ambil user dari customer

        $rules = [
            'nama' => 'required|max:255',
            'role' => 'required',
            'status' => 'required',
            'hp' => 'required|min:10|max:13',
            'foto' => 'image|mimes:jpeg,jpg,png,gif|file|max:1024',
        ];
        $messages = [
            'foto.image' => 'Format gambar gunakan file dengan ekstensi jpeg, jpg, png, atau gif.',
            'foto.max' => 'Ukuran file gambar Maksimal adalah 1024 KB.'
        ];

        if ($request->email != $user->email) {
            $rules['email'] = 'required|max:255|email|unique:user';
        }

        $validatedData = $request->validate($rules, $messages);

        // Upload foto baru
        if ($request->file('foto')) {
            if ($user->foto) {
                $oldImagePath = public_path('storage/img-customer/') . $user->foto;
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $file = $request->file('foto');
            $extension = $file->getClientOriginalExtension();
            $originalFileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $directory = 'storage/img-customer/';
            ImageHelper::uploadAndResize($file, $directory, $originalFileName, 385, 400);
            $validatedData['foto'] = $originalFileName;
        }

        $user->update($validatedData);
        return redirect()->route('backend.customer.index')->with('success', 'Data berhasil diperbaharui');
    }

    public function destroy(string $id)
    {
        $customer = Customer::findOrFail($id);
        $user = User::findOrFail($customer->user_id); // ← ambil user dari customer

        if ($user->foto) {
            $oldImagePath = public_path('storage/img-customer/') . $user->foto;
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        $customer->delete(); // hapus customer dulu (cascade ke user)
        return redirect()->route('backend.customer.index')->with('success', 'Data berhasil dihapus');
    }

        public function akun($id)
    {
        $loggedInCustomerId = Auth::user()->id;
        if ($id != $loggedInCustomerId) {
            return redirect()->route('customer.akun', ['id' => $loggedInCustomerId])
                ->with('msgError', 'Anda tidak berhak mengakses akun ini.');
        }
        $customer = Customer::where('user_id', $id)->firstOrFail();
        return view('v_customer.edit', [
            'judul' => 'Customer',
            'subjudul' => 'Akun Customer',
            'edit' => $customer
        ]);
    }

    public function updateAkun(Request $request, $id)
    {
        $customer = Customer::where('user_id', $id)->firstOrFail();
        $rules = [
            'nama' => 'required|max:255',
            'hp' => 'required|min:10|max:13',
            'foto' => 'image|mimes:jpeg,jpg,png,gif|file|max:1024', 
        ];
        $messages = [
            'foto.image' => 'Format gambar gunakan file dengan ekstensi jpeg, png, atau gif.',
            'foto.max' => 'Ukuran file gambar maksimal adalah 1024 KB.'
        ];

        if ($request->email != $customer->user->email) {
            $rules['email'] = 'required|max:255|email|unique:user';
        }

        if ($request->alamat != $customer->alamat) {
            $rules['alamat'] = 'required';
        }

        if ($request->pos != $customer->pos) {
            $rules['pos'] = 'required';
        }

        $validatedData = $request->validate($rules, $messages); 

        // Upload foto
        if ($request->file('foto')) { 
            if ($customer->user->foto) {
                $oldImagePath = public_path('storage/img-customer/') . $customer->user->foto; //
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            $file = $request->file('foto');
            $extension = $file->getClientOriginalExtension(); 
            $originalFileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
            $directory = 'storage/img-customer/'; 
            ImageHelper::uploadAndResize($file, $directory, $originalFileName, 385, 400);
            $validatedData['foto'] = $originalFileName;
        }

        $customer->user->update($validatedData);

        $customer->update([
            'alamat' => $request->input('alamat'),
            'pos' => $request->input('pos'),
        ]);

        return redirect()->route('customer.akun', $id)->with('success', 'Data berhasil diperbarui');
    }
}