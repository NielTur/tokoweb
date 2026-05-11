<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RajaOngkirController extends Controller
{
    public function getProvinces()
    {
        $response = Http::withoutVerifying()
            ->withHeaders([
                'key' => env('RAJAONGKIR_API_KEY'),
                'Accept' => 'application/json'
            ])
            ->get('https://rajaongkir.komerce.id/api/v1/destination/province');

        return response()->json(json_decode($response->body(), true));
    }

    public function getCities(Request $request)
    {
        $provinceId = $request->input('province_id');
        $response = Http::withoutVerifying()
            ->withHeaders([
                'key' => env('RAJAONGKIR_API_KEY'),
                'Accept' => 'application/json'
            ])
            ->get('https://rajaongkir.komerce.id/api/v1/destination/city/' . $provinceId);

        return response()->json(json_decode($response->body(), true));
    }

    public function getCost(Request $request)
    {
        $response = Http::withoutVerifying()
            ->asForm() // <--- INI KUNCI UTAMANYA: Mengubah JSON menjadi Form Data
            ->withHeaders([
                'key' => env('RAJAONGKIR_API_KEY'),
                'Accept' => 'application/json'
            ])
            ->post('https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost', [
                // Kita bungkus dengan (int) agar datanya pasti berupa angka bulat, sesuai standar Komerce
                'origin'      => (int) $request->input('origin'),
                'destination' => (int) $request->input('destination'),
                'weight'      => (int) $request->input('weight'),
                'courier'     => $request->input('courier'),
            ]);

        return response()->json(json_decode($response->body(), true));
    }

    public function getOngkir(Request $request)
    {
        $response = Http::withoutVerifying()
            ->asForm()
            ->withHeaders([
                'key' => env('RAJAONGKIR_API_KEY'),
                'Accept' => 'application/json'
            ])
            ->post('https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost', [
                'origin'      => (int) $request->input('origin'),
                'destination' => (int) $request->input('destination'),
                'weight'      => (int) $request->input('weight'),
                'courier'     => $request->input('courier'),
            ]);

        return response()->json(json_decode($response->body(), true));
    }
}
