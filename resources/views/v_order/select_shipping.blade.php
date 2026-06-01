@extends('v_layouts.app')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .select2-container {
        width: 100% !important;
    }

    .select2-container .select2-selection--single {
        height: 40px !important;
        border: 1px solid #DADADA !important;
        border-radius: 0px !important;
        padding: 6px 12px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 26px !important;
        color: #555;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px !important;
    }
</style>

<div class="col-md-12">
    <div class="order-summary clearfix">
        <div class="section-title">
            <p>PENGIRIMAN</p>
            <h3 class="title">Pilih Pengiriman</h3>
        </div>

        <div class="form-group">
            <label for="originSelect">Kota Asal:</label><br>
            <select class="input" id="originSelect" style="width:auto"></select>
        </div>
        <input type="hidden" id="kota_asal" name="kota_asal">

        <div class="form-group">
            <label for="destinationSelect">Kota Tujuan:</label><br>
            <select class="input" id="destinationSelect" style="width:auto"></select>
        </div>
        <input type="hidden" id="kota_tujuan" name="kota_tujuan">

        <input type="hidden" name="weight" id="weight" value="{{ $totalBerat }}">

        <div class="form-group">
            <label for="kurir">Kurir:</label>
            <select name="kurir" id="kurir" class="input">
                <option value="">Pilih Kurir</option>
                <option value="jne">JNE</option>
                <option value="tiki">TIKI</option>
                <option value="pos">POS Indonesia</option>
            </select>
        </div>

        <div class="form-group">
            <label for="alamat">Alamat</label>
            <textarea class="input" name="alamat" id="alamat">{{ Auth::user()->alamat }}</textarea>
        </div>

        <div class="form-group">
            <label for="kode_pos">Kode Pos</label>
            <input type="text" class="input" name="kode_pos" id="kode_pos" value="{{ Auth::user()->pos }}">
        </div>

        <button type="button" class="primary-btn" id="checkShipping">Cek Ongkir</button>

        <div id="loading" style="display: none; text-align: center; margin-top: 20px;">
            <div class="spinner"></div>
            <p>Mohon tunggu, sedang memuat ongkir...</p>
        </div>

        <div id="result" style="margin-top: 20px;">
            <table class="shopping-cart-table table">
                <thead>
                    <tr>
                        <th>Layanan</th>
                        <th>Biaya</th>
                        <th>Estimasi Pengiriman</th>
                        <th>Total Berat</th>
                        <th>Total Harga</th>
                        <th class="text-center">Bayar</th>
                    </tr>
                </thead>
                <tbody id="shippingResults">
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    /**
     * Inisialisasi Select2 dengan AJAX (untuk origin dan destination)
     */
    function initSelect2(id, placeholder) {
        $('#' + id).select2({
            width: 'resolve',
            placeholder: placeholder,
            minimumInputLength: 2,
            ajax: {
                url: '/ongkir/get-destination',
                dataType: 'json',
                delay: 500,
                data: function(params) {
                    return {
                        search: params.term
                    };
                },
                processResults: function(data) {
                    if (data.data) {
                        const results = data.data.slice(0, 10).map(item => ({
                            id: item.id,
                            text: item.label + " (" + item.id + ")"
                        }));
                        return {
                            results
                        };
                    } else {
                        return {
                            results: []
                        };
                    }
                },
                error: function(xhr, status, error) {
                    console.error(`Error loading ${id}:`, error);
                }
            }
        });
    }

    // Inisialisasi Select2
    initSelect2('originSelect', 'Ketik kecamatan/kota asal...');
    initSelect2('destinationSelect', 'Ketik kecamatan/kota tujuan...');

    // Event ketika ada pilihan di originSelect
    $('#originSelect').on('select2:select', function(e) {
        let selectedText = e.params.data.text;
        $('#kota_asal').val(selectedText);
    });

    // Event ketika ada pilihan di destinationSelect
    $('#destinationSelect').on('select2:select', function(e) {
        let selectedText = e.params.data.text;
        $('#kota_tujuan').val(selectedText);
    });

    $(document).ready(function() {
        // Handler klik tombol "Cek Ongkir"
        $('#checkShipping').click(function() {
            // Ambil nilai input
            const origin = $('#originSelect').val();
            const destination = $('#destinationSelect').val();
            const weight = $('#weight').val();
            const courier = $('#kurir').val();
            const alamat = $('#alamat').val();
            const kode_pos = $('#kode_pos').val();
            const kota_asal = $('#kota_asal').val();
            const kota_tujuan = $('#kota_tujuan').val();

            // Validasi: semua field wajib diisi
            if (!origin || !destination || !weight || !courier || !alamat || !kode_pos) {
                alert("Mohon lengkapi semua field.");
                return;
            }

            // Siapkan data form untuk dikirim ke server (Disatukan kembali)
            const formData = `origin=${origin}&destination=${destination}&weight=${weight}&courier=${courier}&price=lowest`;
            console.log("Form Data:", formData);

            // Tampilkan loader
            $('#loading').show();

            // Kirim AJAX ke endpoint /ongkir/calculate
            $.ajax({
                url: '/ongkir/calculate',
                method: 'POST',
                data: formData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    $('#loading').hide();
                    console.log("Hasil Ongkir:", response);

                    let shippingResults = $('#shippingResults');
                    shippingResults.empty(); // Kosongkan tabel hasil sebelumnya

                    if (response.data) {
                        // Tambahkan baris hasil ongkir
                        response.data.forEach(service => {
                            let row = `
                        <tr>
                            <td>${service.service} - ${service.description}</td>
                            <td>Rp${service.cost.toLocaleString()}</td>
                            <td class="text-center">${service.etd}</td>
                            <td>${weight} Gram</td>
                            <td>Rp. {{ number_format($totalHarga, 0, ',', '.') }}</td>
                            <td>
                                <form action="{{ route('order.update-ongkir') }}" method="post">
                                    @csrf
                                    <input type="hidden" name="kurir" value="${courier}">
                                    <input type="hidden" name="alamat" value="${alamat}">
                                    <input type="hidden" name="pos" value="${kode_pos}">
                                    <input type="hidden" name="layanan_ongkir" value="${service.service}- ${service.description}">
                                    <input type="hidden" name="total_berat" value="${weight}">
                                    <input type="hidden" name="kota_asal" value="${kota_asal}">
                                    <input type="hidden" name="kota_tujuan" value="${kota_tujuan}">
                                    <input type="hidden" name="biaya_ongkir" value="${service.cost}">
                                    <input type="hidden" name="estimasi_ongkir" value="${service.etd}">
                                    <button type="submit" class="primary-btn">Pilih Pengiriman</button>
                                </form>
                            </td>
                        </tr>`;
                            shippingResults.append(row);
                        });
                    } else {
                        shippingResults.html("<tr><td colspan='6' class='text-center'><em>Tidak ada data ongkir ditemukan.</em></td></tr>");
                    }
                },
                error: function(xhr, status, error) {
                    $('#loading').hide();
                    console.error("Error ongkir:", error);
                    $('#shippingResults').html("<tr><td colspan='6' class='text-center'><em>Terjadi kesalahan saat mengambil ongkir.</em></td></tr>");
                }
            });
        });
    });
</script>
@endpush
@endsection