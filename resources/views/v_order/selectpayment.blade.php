@extends('v_layouts.app')

@section('content')
<div class="container py-4">
    <h2>KERANJANG</h2>
    <h4>Keranjang Belanja</h4>
    <hr>

    @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if($order && $order->orderItems->count() > 0)
    @php
    $totalHarga = 0;
    $totalBerat = 0;
    @endphp

    <div class="table-responsive">
        <table class="table table-bordered align-middle">
            <thead class="table-light">
                <tr>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th style="width: 100px;">Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItems as $item)
                @php
                $totalHarga += $item->harga * $item->quantity;
                $totalBerat += $item->produk->berat * $item->quantity;
                @endphp
                <tr>
                    <td>
                        <strong>{{ $item->produk->nama_produk }}</strong><br>
                        <small class="text-muted">Berat: {{ $item->produk->berat }} Gram</small><br>
                        <small class="text-muted">Stok: {{ $item->produk->stok }} Pcs</small>
                    </td>
                    <td>Rp. {{ number_format($item->harga, 0, ',', '.') }}</td>
                    <td>
                        {{ $item->quantity }}
                    </td>
                    <td>Rp. {{ number_format($item->harga * $item->quantity, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="row justify-content-end mt-4">
        <div class="col-md-5">
            <div class="card p-3 shadow-sm">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td><strong>SUBTOTAL</strong></td>
                        <td class="text-end">Rp. {{ number_format($totalHarga, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>
                            <strong>Ongkos Kirim</strong><br>
                            <small class="text-muted">
                                {{ $order->kurir . ' - ' . $order->layanan_ongkir . ' *estimasi ' . $order->estimasi_ongkir . ' Hari' }}
                            </small>
                            @if(session('origin'))
                            <br><small class="text-muted">Kota asal: {{ $originName }}</small>
                            @endif
                        </td>
                        <td class="text-end text-success">Rp. {{ number_format($order->biaya_ongkir, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="border-top">
                        <td>
                            <h5><strong>TOTAL BAYAR</strong></h5>
                        </td>
                        <td class="text-end">
                            <h5><strong>Rp. {{ number_format($totalHarga + $order->biaya_ongkir, 0, ',', '.') }}</strong></h5>
                        </td>
                    </tr>
                </table>
                <div class="mt-3">
                    <a href="#" class="btn btn-primary w-100 py-2">Bayar Sekarang</a>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="alert alert-warning text-center my-4">
        Keranjang belanja kosong.
    </div>
    @endif
</div>
@endsection