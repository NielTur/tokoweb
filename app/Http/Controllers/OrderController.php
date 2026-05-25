<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Kategori;
use App\Models\Produk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Contracts\Service\Attribute\Required;
use Illuminate\Support\Facades\Http;
use Midtrans\Snap;
use Midtrans\Config;
use function PHPUnit\Framework\returnArgument;

class OrderController extends Controller
{
    // --- CART CORE FUNCTIONS ---

    public function statusProses()
    {
        //backend
        $order = Order::whereIn('status', ['Paid', 'Kirim'])->orderBy('id', 'desc')->get();
        return view('backend.v_pesanan.proses', [
            'judul' => 'Pesanan',
            'subJudul' => 'Pesanan Proses',
            'index' => $order
        ]);
    }

    public function statusSelesai()
    {
        // backend
        $order = Order::where('status', 'Selesai')->orderBy('id', 'desc')->get();
        return view('backend.v_pesanan.selesai', [
            'judul' => 'Pesanan',
            'subJudul' => 'Pesanan Proses',
            'judul' => 'Data Transaksi',
            'index' => $order
        ]);
    }

    public function statusDetail($id)
    {
        $order = Order::findeOrFail($id);
        return view('backend.v_pesanan.detail', [
            'judul' => 'Pesanan',
            'subjudul' => 'Pesanan Proses',
            'judul' => 'Data Transaksi',
            'order' => $order,
        ]);
    }

    public function statusUpdate(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        $rules = [
            'alamat' => 'required',
        ];
        if ($request->status != $order->status) {
            $rules['status'] = 'required';
        }
        if ($request->noresi != $order->noresi) {
            $rules['noresi'] = 'required';
        }
        if ($request->pos != $order->pos) {
            $rules['pos'] = 'required';
        }
        $validatedData = $request->validate($rules);
        Order::where('id', $id)->update($validatedData);
        return redirect()->route('pesanan.proses')->with('success', 'Data berhasil diperbaharui');
    }

    public function addToCart($id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $produk = Produk::findOrFail($id);

        $order = Order::firstOrCreate(
            ['customer_id' => $customer->id, 'status' => 'pending'],
            ['total_harga' => 0]
        );

        $orderItem = OrderItem::firstOrCreate(
            ['order_id' => $order->id, 'produk_id' => $produk->id],
            ['quantity' => 1, 'harga' => $produk->harga]
        );

        if (!$orderItem->wasRecentlyCreated) {
            $orderItem->increment('quantity');
        }

        $order->increment('total_harga', $produk->harga);

        return redirect()->route('order.cart')->with('success', 'Produk berhasil ditambahkan ke keranjang');
    }

    public function viewCart()
    {
        $customer = Customer::where('user_id', Auth::id())->first();

        // Perbaikan logic: Biasanya cart cuma ambil yang 'pending'
        $order = Order::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->with('orderItems.produk')
            ->first();

        return view('v_order.cart', compact('order'));
    }

    public function updateCart(Request $request, $id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $order = Order::where('customer_id', $customer->id)->where('status', 'pending')->first();

        if ($order) {
            $orderItem = $order->orderItems()->where('id', $id)->first();

            if ($orderItem) {
                $quantity = $request->input('quantity');

                if ($quantity > $orderItem->produk->stok) {
                    return redirect()->route('order.cart')->with('error', 'Jumlah produk melebihi stok tersedia');
                }

                // Kalkulasi ulang total harga order
                $order->total_harga -= ($orderItem->harga * $orderItem->quantity);

                $orderItem->update(['quantity' => $quantity]);

                $order->total_harga += ($orderItem->harga * $quantity);
                $order->save();
            }
        }

        return redirect()->route('order.cart')->with('success', 'Jumlah produk berhasil diperbarui');
    }

    public function checkout()
    {
        $customer = Customer::where('user_id', Auth::id())->first();;;
        $order = Order::where('customer_id', $customer->id)->where('status', 'pending')->first();
        // kurangi stok
        if ($order) {
            foreach ($order->orderItems as $item) {
                $produk = $item->produk;
                if ($produk->stok >= $item->quantity) {
                    $produk->stok
                        -= $item->quantity;
                    $produk->save();
                } else {
                    return redirect()->route('order.cart')->with('error', 'stok produk' . $produk->nama_produk . 'tidak mencukupi');
                }
            }
            $order->status = 'completed';
            $order->save();
        }
        return redirect()->route('order.history')->with('success', 'CheckOut berhasil');
    }

    public function orderHistory()
    {
        $customer = Customer::where('user_id', Auth::id())->first();;;
        //$order = Order::where('customer_id', $customer->id)->where('status', 'completed')->get();
        $statuses = ['paid', 'Kirim', 'Selesai'];
        $orders = Order::where('customer_id', $customer->id)
            ->whereIn('status', $statuses)
            ->orderby('id', 'desc')
            ->get();
        return view('v_order.history', compact('orders'));
    }

    public function removeFromCart(Request $request, $id)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $order = Order::where('customer_id', $customer->id)->where('status', 'pending')->first();

        if ($order) {
            $orderItem = OrderItem::where('order_id', $order->id)->where('produk_id', $id)->first();

            if ($orderItem) {
                $order->total_harga -= ($orderItem->harga * $orderItem->quantity);
                $orderItem->delete();

                if ($order->total_harga <= 0) {
                    $order->delete();
                } else {
                    $order->save();
                }
            }
        }

        return redirect()->route('order.cart')->with('success', 'Produk berhasil dihapus dari keranjang');
    }

    // --- SHIPPING FUNCTIONS ---

    public function selectShipping(Request $request)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $order = Order::where('customer_id', $customer->id)->where('status', 'pending')->first();

        if (!$order || $order->orderItems->count() == 0) {
            return redirect()->route('order.cart')->with('error', 'Keranjang belanja kosong.');
        }

        return view('v_order.select_shipping', compact('order'));
    }

    public function selectPayment()
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $order = Order::where('customer_id', $customer->id)->where('status', 'pending')->first();
        if ($order) {
            $order->load('orderItems.produk');
        }

        // Pastikan totall price sudah dihitung dengan benar

        $totalHarga = 0;
        foreach ($order->orderItems as $item) {
            $totalHarga += $item->harga * $item->quantity;
        }

        // Tambahkan biaya ongkir ke total harga
        $grossAmount = $totalHarga + $order->biaya_onkir;

        // Midtrans configuration
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Generate unique order_id
        $orderId = $order->id . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $grossAmount, // Pastikasn gross_aount adalah integer 
            ],
            'customer_details' => [
                'first_name' => $customer->nama,
                'email' => $customer->email,
                'phone' => $customer->hp,
            ],
        ];

        $snapToken = Snap::getSnapToken($params);
        return view('v_order.selectpayment', [
            'order' => $order,
            'snaptoken' => $snapToken,
        ]);
    }

    public function callback(Request $request)
    {
        dd($request->all());
        $severKey = config('midtrans.server_key');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        if ($hashed == $request->signature_key) {
            $order = Order::find($request->order_id);
            if ($order) {
                $order->update(['status' => 'Paid']);
            }
        }
    }

    public function complete()
    {
        return redirect()->route('order.history')->with('success', 'Checkout berhasil');
    }
    // Logika untuk halaman setelah pembayaran berhasil
    // return view('v_order.complete');



    public function formOrderProses()
    {
        return view('backend.v_pesanan.formproses', [
            'judul' => 'Laporan',
            'subJudul' => 'Laporan Pesanan Proses',
        ]);
    }

    public function cetakOrderProses(Request $request)
    {
        $request->validate([
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
        ], [
            'tanggal_awal.required' => 'Tanggal Awal harus diisi.',
            'tanggal_akhir.required' => 'Tanggal Akhir harus diisi.',
            'tanggal_akhir.after_or_equal' => 'Tanggal Akhir harus lebih besar atau sama dengan Tanggal Awal.',
        ]);

        $tanggalAwal = $request->input('tanggal_awal');
        $tanggalAkhir = $request->input('tanggal_akhir');

        $order = Order::whereIn('status', ['Paid', 'Kirim'])
            ->orderBy('id', 'desc')
            ->get();

        return view('backend.v_pesanan.cetakproses', [
            'judul' => 'Laporan',
            'subJudul' => 'Laporan Pesanan Proses',
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'cetak' => $order,
        ]);
    }

    public function formOrderSelesai()
    {
        return view('backend.v_pesanan.formselesai', [
            'judul' => 'Laporan',
            'subJudul' => 'Laporan Pesanan Selesai',
        ]);
    }

    public function cetakOrderSelesai(Request $request)
    {
        $request->validate([
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
        ], [
            'tanggal_awal.required' => 'Tanggal Awal harus diisi.',
            'tanggal_akhir.required' => 'Tanggal Akhir harus diisi.',
            'tanggal_akhir.after_or_equal' => 'Tanggal Akhir harus lebih besar atau sama dengan Tanggal Awal.',
        ]);

        $tanggalAwal = $request->input('tanggal_awal');
        $tanggalAkhir = $request->input('tanggal_akhir');

        $order = Order::where('status', 'Selesai')
            ->whereBetween('created_at', [$tanggalAwal, $tanggalAkhir])
            ->orderBy('id', 'desc')
            ->get();

        $totalPendapatan = $order->sum(fn($row) => $row->total_harga + $row->biaya_ongkir);

        return view('backend.v_pesanan.cetakselesai', [
            'judul' => 'Laporan',
            'subJudul' => 'Laporan Pesanan Selesai',
            'tanggalAwal' => $tanggalAwal,
            'tanggalAkhir' => $tanggalAkhir,
            'cetak' => $order,
            'totalPendapatan' => $totalPendapatan,
        ]);
    }

    public function invoiceBackend($id)
    {
        $order = Order::findOrFail($id);

        return view('backend.v_pesanan.invoice', [
            'judul' => 'Data Transaksi',
            'subJudul' => 'Pesanan Proses',
            'order' => $order,
        ]);
    }

    public function invoiceFrontend($id)
    {
        $order = Order::findOrFail($id);

        return view('backend.v_pesanan.invoice', [
            'judul' => 'Data Transaksi',
            'subJudul' => 'Pesanan Proses',
            'order' => $order,
        ]);
    }


    public function updateOngkir(Request $request)
    {
        $customer = Customer::where('user_id', Auth::id())->first();
        $order = Order::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->first();

        $kota_asal = $request->input('kota_asal');
        $kota_tujuan = $request->input('kota_tujuan');

        if ($order) {
            // Simpan data ongkir ke dalam order
            $order->kurir           = $request->input('kurir');
            $order->layanan_ongkir  = $request->input('layanan_ongkir');
            $order->biaya_ongkir    = $request->input('biaya_ongkir');
            $order->estimasi_ongkir = $request->input('estimasi_ongkir');
            $order->total_berat     = $request->input('total_berat');
            $order->alamat          = $request->input('alamat') . ', ' . $request->input('city_name') . ', ' . $request->input('province_name');
            $order->pos             = $request->input('pos');
            $order->save();

            // Simpan ke session flash agar bisa diakses di halaman tujuan
            return redirect()->route('order.selectpayment')
                ->with('kota_asal', $kota_asal)
                ->with('kota_tujuan', $kota_tujuan);
        }

        return back()->with('error', 'Gagal menyimpan data ongkir');
    }


    // --- RAJAONGKIR API WRAPPERS ---

    public function getProvinces()
    {
        $response = Http::withHeaders(['key' => env('RAJAONGKIR_API_KEY')])
            ->get(env('RAJAONGKIR_BASE_URL') . '/province');

        return response()->json($response->json());
    }

    public function getCities(Request $request)
    {
        $response = Http::withHeaders(['key' => env('RAJAONGKIR_API_KEY')])
            ->get(env('RAJAONGKIR_BASE_URL') . '/city', [
                'province' => $request->input('province_id')
            ]);

        return response()->json($response->json());
    }

    public function getCost(Request $request)
    {
        $response = Http::withHeaders(['key' => env('RAJAONGKIR_API_KEY')])
            ->post(env('RAJAONGKIR_BASE_URL') . '/cost', [
                'origin' => $request->input('origin'),
                'destination' => $request->input('destination'),
                'weight' => $request->input('weight'),
                'courier' => $request->input('courier'),
            ]);

        return response()->json($response->json());
    }
}
