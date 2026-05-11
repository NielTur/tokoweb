<!DOCTYPE html>
<html>

<head>
    <title>Cek Ongkir</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    <form id="ongkirForm">
        <select name="province" id="province">
            <option value="">Pilih Provinsi</option>
        </select>
        <select name="city" id="city">
            <option value="">Pilih Kota</option>
        </select>
        <input type="number" name="weight" id="weight" placeholder="Berat (gram)">
        <select name="courier" id="courier">
            <option value="">Pilih Kurir</option>
            <option value="jne">JNE</option>
            <option value="tiki">TIKI</option>
            <option value="pos">POS Indonesia</option>
        </select>
        <button type="submit">Cek Ongkir</button>
    </form>

    <div id="result"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const provinceSelect = document.getElementById('province');
            const citySelect = document.getElementById('city');

            // 1. Ambil Provinsi
            fetch('/provinces')
                .then(response => response.json())
                .then(res => {
                    console.log('Respon Provinsi:', res);
                    if (res.meta && res.meta.code === 200) {
                        res.data.forEach(province => {
                            let option = document.createElement('option');
                            option.value = province.id; // Komerce pakai 'id'
                            option.textContent = province.name; // Komerce pakai 'name'
                            provinceSelect.appendChild(option);
                        });
                    }
                })
                .catch(err => console.error('Gagal ambil provinsi:', err));

            // 2. Ambil Kota saat Provinsi berubah
            provinceSelect.addEventListener('change', function() {
                let provinceId = this.value;
                if (!provinceId) return;

                citySelect.innerHTML = '<option value="">Memuat kota...</option>';

                fetch(`/cities?province_id=${provinceId}`)
                    .then(response => response.json())
                    .then(res => {
                        console.log('Respon Kota:', res);
                        citySelect.innerHTML = '<option value="">Pilih Kota</option>';

                        if (res.meta && res.meta.code === 200 && res.data) {
                            res.data.forEach(city => {
                                let option = document.createElement('option');
                                option.value = city.id;
                                option.textContent = city.name;
                                citySelect.appendChild(option);
                            });
                        }
                    })
                    .catch(err => console.error('Gagal ambil kota:', err));
            });
        });

        // --- 3. CEK ONGKIR ---
        document.getElementById('ongkirForm').addEventListener('submit', function(event) {
            event.preventDefault();

            let origin = 501; // Pastikan kode asal ini valid di Komerce
            let destination = document.getElementById('city').value;
            let weight = document.getElementById('weight').value;
            let courier = document.getElementById('courier').value;

            fetch('/cost', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        origin: origin,
                        destination: destination,
                        weight: weight,
                        courier: courier
                    })
                })
                .then(response => response.json())
                .then(res => {
                    console.log('Cost data:', res);

                    // Sesuaikan struktur ongkir jika menggunakan Komerce
                    if (res.meta && res.meta.code === 200) {
                        // Catatan: Struktur array cost Komerce mungkin berbeda dengan RajaOngkir.
                        // Jika error saat submit ongkir, perhatikan console.log 'Cost data' ini
                        let result = res.data;
                        let resultDiv = document.getElementById('result');
                        resultDiv.innerHTML = '';

                        // Pastikan format looping ini sesuai dengan respon Komerce
                        if (result && result.length > 0) {
                            result.forEach(cost => {
                                let div = document.createElement('div');
                                div.textContent = `${cost.service} : ${cost.cost} Rupiah (${cost.etd} hari)`;
                                resultDiv.appendChild(div);
                            });
                        } else {
                            resultDiv.innerHTML = '<div>Ongkir tidak ditemukan</div>';
                        }
                    } else {
                        console.error('Failed to fetch cost', res.meta ? res.meta.message : 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error fetching cost:', error);
                });
        });
    </script>
</body>

</html>