@extends('v_layouts.app')
@section('content')

<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>PENGIRIMAN</p>
            <h3 class="title">Pilih Pengiriman</h3>
        </div>

        @if(session()->has('error'))
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            <strong>{{ session('error') }}</strong>
        </div>
        @endif

        <form action="{{ route('order.updateongkir') }}" method="post" id="shippingForm">
            @csrf
            <input type="hidden" id="total_berat" name="total_berat"
                value="{{ $order->orderItems->sum(fn($i) => $i->produk->berat * $i->quantity) }}">
            <input type="hidden" id="total_harga"
                value="{{ $order->orderItems->sum(fn($i) => $i->harga * $i->quantity) }}">
            <input type="hidden" name="city_name" id="city_name">
            <input type="hidden" name="province_name" id="province_name">
            <input type="hidden" name="kurir" id="kurir">
            <input type="hidden" name="layanan_ongkir" id="layanan_ongkir">
            <input type="hidden" name="biaya_ongkir" id="biaya_ongkir">
            <input type="hidden" name="estimasi_ongkir" id="estimasi_ongkir">
            <input type="hidden" name="pos" id="pos">

            <table class="table">
                <tr>
                    <td style="width:200px;"><strong>Provinsi Tujuan:</strong></td>
                    <td>
                        <select id="provinceSelect" class="form-control" style="width:300px;">
                            <option value="">Pilih Provinsi</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><strong>Kota Tujuan:</strong></td>
                    <td>
                        <select id="citySelect" name="destination_id" class="form-control" style="width:300px;">
                            <option value="">Pilih Kota</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><strong>Kurir:</strong></td>
                    <td>
                        <select id="courierSelect" class="form-control" style="width:200px;">
                            <option value="">Pilih Kurir</option>
                            <option value="jne">JNE</option>
                            <option value="tiki">TIKI</option>
                            <option value="pos">POS Indonesia</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td><strong>Alamat</strong></td>
                    <td><textarea name="alamat" class="form-control" rows="3"
                            placeholder="Masukkan alamat lengkap..."></textarea></td>
                </tr>
                <tr>
                    <td><strong>Kode Pos</strong></td>
                    <td><input type="text" id="kode_pos" name="kode_pos" class="form-control" style="width:150px;"></td>
                </tr>
            </table>

            <button type="button" class="primary-btn" onclick="cekOngkir()">CEK ONGKIR</button>
            <div id="result" class="mt-4"></div>

            <div class="pull-right mt-3" id="btnLanjut" style="display:none;">
                <button type="submit" class="primary-btn">Lanjut ke Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const totalBerat = document.getElementById('total_berat').value;
        const totalHarga = parseInt(document.getElementById('total_harga').value);

        // Load provinsi
        fetch('/provinces')
            .then(res => res.json())
            .then(data => {
                const select = document.getElementById('provinceSelect');
                const provinces = data?.data ?? [];
                provinces.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.name;
                    select.appendChild(opt);
                });
            });

        // Load kota saat provinsi berubah
        document.getElementById('provinceSelect').addEventListener('change', function() {
            document.getElementById('province_name').value = this.options[this.selectedIndex].text;
            const citySelect = document.getElementById('citySelect');
            citySelect.innerHTML = '<option value="">Memuat kota...</option>';

            fetch(`/cities?province_id=${this.value}`)
                .then(res => res.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Pilih Kota</option>';
                    const cities = data?.data ?? [];
                    cities.forEach(c => {
                        const opt = document.createElement('option');
                        opt.value = c.id;
                        opt.textContent = c.name;
                        citySelect.appendChild(opt);
                    });
                });
        });

        // Simpan nama kota
        document.getElementById('citySelect').addEventListener('change', function() {
            document.getElementById('city_name').value = this.options[this.selectedIndex].text;
        });

        window.cekOngkir = function() {
            const destId = document.getElementById('citySelect').value;
            const courier = document.getElementById('courierSelect').value;
            const resultDiv = document.getElementById('result');

            if (!destId || !courier) {
                alert('Pilih kota tujuan dan kurir terlebih dahulu!');
                return;
            }

            resultDiv.innerHTML = '<p>Menghitung ongkir...</p>';

            fetch('/get-ongkir', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        origin: 501,
                        destination: destId,
                        weight: totalBerat,
                        courier: courier
                    })
                })
                .then(res => res.json())
                .then(data => {
                    resultDiv.innerHTML = '';
                    const costs = data?.data ?? [];
                    console.log('Costs:', costs);
                    console.log('Item pertama:', costs[0]);

                    if (!costs.length) {
                        resultDiv.innerHTML = '<p>Tidak ada layanan tersedia.</p>';
                        return;
                    }

                    const kurirText = document.getElementById('courierSelect').options[document.getElementById('courierSelect').selectedIndex].text;

                    let html = `<table class="table table-bordered">
                <thead><tr>
                    <th>LAYANAN</th><th>BIAYA</th><th>ESTIMASI PENGIRIMAN</th>
                    <th>TOTAL BERAT</th><th>TOTAL HARGA</th><th>BAYAR</th>
                </tr></thead><tbody>`;

                    costs.forEach(item => {
                        html += `<tr>
                    <td>${item.service}</td>
                    <td>${parseInt(item.cost).toLocaleString('id-ID')} Rupiah</td>
                    <td>${item.etd} hari</td>
                    <td>${totalBerat} Gram</td>
                    <td>Rp. ${totalHarga.toLocaleString('id-ID')}</td>
                    <td>
                        <button type="button" class="primary-btn"
                            onclick="pilihOngkir('${kurirText}','${item.service}',${item.cost},'${item.etd}','')">
                            PILIH PENGIRIMAN
                        </button>
                    </td>
                </tr>`;
                    });

                    html += '</tbody></table>';
                    resultDiv.innerHTML = html;
                })
                .catch(() => resultDiv.innerHTML = '<p>Terjadi error, coba lagi.</p>');
        };

        window.pilihOngkir = function(kurir, layanan, biaya, estimasi, pos) {
            document.getElementById('kurir').value = kurir;
            document.getElementById('layanan_ongkir').value = layanan;
            document.getElementById('biaya_ongkir').value = biaya;
            document.getElementById('estimasi_ongkir').value = estimasi;
            document.getElementById('pos').value = pos;
            document.getElementById('btnLanjut').style.display = 'block';
            document.getElementById('btnLanjut').scrollIntoView({
                behavior: 'smooth'
            });
        };

    }); // end DOMContentLoaded
</script>

@endsection