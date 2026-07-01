<?php

namespace App\Http\Controllers\Dokter;

use App\Http\Controllers\Controller;
use App\Models\DaftarPoli;
use App\Models\DetailPeriksa;
use App\Models\Obat;
use App\Models\Periksa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PeriksaPasienController extends Controller
{
    public function index()
    {
        $dokterId = Auth::id();

        $daftarPasien = DaftarPoli::with(['pasien', 'jadwalPeriksa', 'periksas'])
            ->whereHas('jadwalPeriksa', function ($query) use ($dokterId) {
                $query->where('id_dokter', $dokterId);
            })
            ->orderBy('no_antrian')
            ->get();

        return view('dokter.periksa-pasien.index', compact('daftarPasien'));
    }

    public function create($id)
    {
        $obats = Obat::all();
        return view('dokter.periksa-pasien.create', compact('obats', 'id'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'obat_json' => 'required',
            'catatan' => 'nullable|string',
            'biaya_periksa' => 'required|integer',
        ]);

        $obatIds = json_decode($request->obat_json, true);

        // ==========================================================
        // 1. VALIDASI STOK OBAT HABIS
        // ==========================================================
        if (is_array($obatIds) && count($obatIds) > 0) {
            foreach ($obatIds as $idObat) {
                $obat = Obat::find($idObat);
                if ($obat && $obat->stok <= 0) {
                    // Batalkan proses jika ada obat yang stoknya habis
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Gagal menyimpan resep! Stok obat "' . $obat->nama_obat . '" sudah habis.');
                }
            }
        }

        // Jika semua stok aman, lanjut simpan data periksa
        $periksa = Periksa::create([
            'id_daftar_poli' => $request->id_daftar_poli,
            'tgl_periksa' => now(),
            'catatan' => $request->catatan,
            'biaya_periksa' => $request->biaya_periksa + 150000,
        ]);

        foreach ($obatIds as $idObat) {
            DetailPeriksa::create([
                'id_periksa' => $periksa->id,
                'id_obat' => $idObat,
            ]);

            // ==========================================================
            // 2. PENGURANGAN STOK OTOMATIS
            // ==========================================================
            $obat = Obat::find($idObat);
            if ($obat) {
                $obat->decrement('stok', 1); // Mengurangi kolom stok sebanyak 1
            }
        }

        return redirect()->route('periksa-pasien.index')->with('success', 'Data periksa berhasil disimpan.');
    }
}