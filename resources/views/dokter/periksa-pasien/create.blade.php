<x-layouts.app title="Periksa Pasien">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('periksa-pasien.index') }}" class="inline-flex items-center justify-center w-9 h-9 
                  rounded-lg bg-slate-100 text-slate-500 
                  hover:bg-slate-200 transition">
            <i class="fas fa-arrow-left text-sm"></i>
        </a>
        <h2 class="text-2xl font-bold text-slate-800">
            Periksa Pasien
        </h2>
    </div>

    {{-- Card --}}
    <div class="card bg-base-100 shadow-sm rounded-2xl border border-slate-200">
        <div class="card-body p-8">

            <form action="{{ route('periksa-pasien.store') }}" method="POST">
                @csrf
                <input type="hidden" name="id_daftar_poli" value="{{ $id }}">

                {{-- Pilih Obat --}}
                <div class="form-control mb-5">
                    <label class="label pb-1">
                        <span class="text-sm font-semibold text-gray-700">Pilih Obat <span class="text-red-500">*</span></span>
                    </label>
                    <select id="select-obat" class="select select-bordered w-full rounded-lg border-2 px-4">
                        <option value="">-- Pilih Obat --</option>
                        @foreach ($obats as $obat)
                            {{-- TAMBAHAN: Atribut data-stok ditambahkan di sini --}}
                            <option value="{{ $obat->id }}"
                                data-nama="{{ $obat->nama_obat }}"
                                data-harga="{{ $obat->harga }}"
                                data-stok="{{ $obat->stok }}"> 
                                {{ $obat->nama_obat }} - Rp{{ number_format($obat->harga) }} 
                                (Stok: {{ $obat->stok }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Obat Terpilih --}}
                <div class="form-control mb-5">
                    <label class="label pb-1 ">
                        <span class="text-sm font-semibold text-gray-700">Obat Terpilih</span>
                    </label>

                    <ul id="obat-terpilih" class="flex flex-col gap-2 mb-2 min-h-[48px]"></ul>

                    <input type="hidden" name="biaya_periksa" id="biaya_periksa" value="0">
                    <input type="hidden" name="obat_json" id="obat_json">
                </div>

                {{-- Total Harga --}}
                <div class="form-control mb-5">
                    <label class="label pb-1">
                        <span class="text-sm font-semibold text-gray-700">Total Harga</span>
                    </label>
                    <div class="input input-bordered w-full rounded-lg flex items-center bg-slate-50 text-slate-700 font-bold" id="total-harga">
                        Rp 0
                    </div>
                </div>

                {{-- Catatan --}}
                <div class="form-control mb-8">
                    <label class="label pb-1">
                        <span class="text-sm font-semibold text-gray-700">Catatan <span class="text-slate-400 font-normal">(Opsional)</span></span>
                    </label>
                    <textarea name="catatan" id="catatan" rows="4"
                        placeholder="Masukkan catatan..."
                        class="textarea textarea-bordered w-full border-2 px-4 py-2 rounded-lg resize-none">{{ old('catatan') }}</textarea>
                </div>

                {{-- Buttons --}}
                <div class="flex gap-3">
                    <button type="submit"
                        class="btn bg-[#2d4499] hover:bg-[#1e2d6b] text-white border-none rounded-lg px-6">
                        <i class="fas fa-save"></i>
                        Simpan
                    </button>
                    <a href="{{ route('periksa-pasien.index') }}"
                        class="btn btn-ghost bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-lg px-6">
                        Batal
                    </a>
                </div>

            </form>
        </div>
    </div>

    {{-- TAMBAHAN: Pop-up Notifikasi (Toast) --}}
    <div id="stok-warning-toast" class="hidden fixed top-5 right-5 z-[99] max-w-sm w-full bg-amber-50 border border-amber-200 rounded-xl p-4 shadow-lg transition-all duration-300 transform translate-y-2 opacity-0">
        <div class="flex items-start gap-3">
            <div class="text-amber-500 mt-0.5">
                <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-amber-800">Peringatan Stok!</h3>
                <p id="toast-message" class="text-xs text-amber-700 mt-1">
                    Stok obat menipis.
                </p>
            </div>
            <button onclick="closeToast()" class="text-amber-400 hover:text-amber-600 transition">
                <i class="fas fa-times text-sm mt-1"></i>
            </button>
        </div>
    </div>

    <script>
        const selectObat = document.getElementById('select-obat');
        const listObat = document.getElementById('obat-terpilih');
        const inputBiaya = document.getElementById('biaya_periksa');
        const inputObatJson = document.getElementById('obat_json');
        const totalHargaEl = document.getElementById('total-harga');

        let daftarObat = [];

        selectObat.addEventListener('change', () => {
            const selectedOption = selectObat.options[selectObat.selectedIndex];
            const id = selectedOption.value;
            const nama = selectedOption.dataset.nama;
            const harga = parseInt(selectedOption.dataset.harga || 0);
            
            // Ambil data stok (TAMBAHAN)
            const stok = parseInt(selectedOption.dataset.stok || 0);

            if (!id || daftarObat.some(o => o.id == id)) return;

            // Trigger Pop-up Toast jika stok menipis (< 5) tapi tidak habis (> 0) (TAMBAHAN)
            if (stok > 0 && stok < 5) {
                showToast(`Stok obat "${nama}" hanya tersisa ${stok}!`);
            }

            daftarObat.push({ id, nama, harga });
            renderObat();
            selectObat.selectedIndex = 0;
        });

        function renderObat() {
            listObat.innerHTML = '';
            let total = 0;

            daftarObat.forEach((obat, index) => {
                total += obat.harga;

                const item = document.createElement('li');
                item.className = 'flex items-center justify-between px-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-700';
                item.innerHTML = `
                    <span>${obat.nama} — <span class="font-semibold">Rp ${obat.harga.toLocaleString()}</span></span>
                    <button type="button"
                        onclick="hapusObat(${index})"
                        class="btn btn-sm bg-red-500 hover:bg-red-600 text-white border-none rounded-lg px-3">
                        <i class="fas fa-trash"></i>
                    </button>
                `;
                listObat.appendChild(item);
            });

            inputBiaya.value = total;
            totalHargaEl.textContent = `Rp ${total.toLocaleString()}`;
            inputObatJson.value = JSON.stringify(daftarObat.map(o => o.id));
        }

        function hapusObat(index) {
            daftarObat.splice(index, 1);
            renderObat();
        }

        // TAMBAHAN: Fungsi untuk menampilkan dan menyembunyikan Toast
        let toastTimeout;
        function showToast(message) {
            const toast = document.getElementById('stok-warning-toast');
            const toastMsg = document.getElementById('toast-message');
            
            toastMsg.innerText = message;
            
            // Tampilkan
            toast.classList.remove('hidden');
            setTimeout(() => {
                toast.classList.remove('translate-y-2', 'opacity-0');
                toast.classList.add('translate-y-0', 'opacity-100');
            }, 10);

            // Bersihkan timer lama jika ada, lalu set penutupan otomatis (5 detik)
            clearTimeout(toastTimeout);
            toastTimeout = setTimeout(closeToast, 5000);
        }

        function closeToast() {
            const toast = document.getElementById('stok-warning-toast');
            toast.classList.remove('translate-y-0', 'opacity-100');
            toast.classList.add('translate-y-2', 'opacity-0');
            
            setTimeout(() => {
                toast.classList.add('hidden');
            }, 300);
        }
    </script>

</x-layouts.app>