<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pesanan; // Pastikan model Pesanan sudah dibuat sesuai instruksi modul

class PesananAdminController extends Controller
{
    public function index()
    {
        // Mengambil semua data pesanan urut terbaru
        $pesanan = Pesanan::orderBy('created_at', 'desc')->paginate(10);
        return view('backend.v_pesanan.index', [
            'judul' => 'Manajemen Pemesanan',
            'sub' => 'Daftar Pesanan Masuk',
            'pesanan' => $pesanan
        ]);
    }

    public function show($id)
    {
        $pesanan = Pesanan::findOrFail($id);
        return view('backend.v_pesanan.show', [
            'judul' => 'Detail Pemesanan',
            'sub' => 'Invoice #' . $pesanan->id,
            'pesanan' => $pesanan
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $pesanan = Pesanan::findOrFail($id);
        $pesanan->update([
            'status' => $request->status // status bisa berupa: 'Menunggu Pembayaran', 'Diproses', 'Dikirim', 'Selesai'
        ]);

        return redirect()->back()->with('sukses', 'Status pesanan berhasil diperbarui!');
    }
}
