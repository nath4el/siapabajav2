<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Pengadaan;
use App\Models\User;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $superAdminName = auth()->user()->name ?? 'Super Admin';

        $tahunOptions = Pengadaan::whereNotNull('tahun')
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->map(fn($t) => (int)$t)
            ->values()
            ->all();

        if (count($tahunOptions) === 0) {
            $y = (int) date('Y');
            $tahunOptions = [$y, $y - 1, $y - 2, $y - 3, $y - 4];
        }

        $defaultYear = $tahunOptions[0] ?? (int) date('Y');

        $unitNameCol = Schema::hasColumn('units', 'nama') ? 'nama'
            : (Schema::hasColumn('units', 'nama_unit') ? 'nama_unit'
                : (Schema::hasColumn('units', 'name') ? 'name' : 'id'));

        $units = Unit::orderBy($unitNameCol, 'asc')->get(['id', $unitNameCol]);

        $registeredUnits = $units->pluck($unitNameCol)->values()->all();

        $unitOptions = $units->map(function ($u) use ($unitNameCol) {
            return [
                'id' => (int) $u->id,
                'name' => (string) $u->{$unitNameCol},
            ];
        })->values()->all();

        $totalUnitKerja = count($unitOptions);

        $totalPpk = DB::table('users')
            ->whereRaw('LOWER(role) = ?', ['ppk'])
            ->count();

        $totalArsip = Pengadaan::count();
        $publik     = Pengadaan::where('status_arsip', 'Publik')->count();
        $privat     = Pengadaan::where('status_arsip', 'Privat')->count();

        $paketAll = Pengadaan::count();
        $nilaiAll = (int) Pengadaan::sum('nilai_kontrak');

        $summary = [
            ["label" => "Total Arsip", "value" => $totalArsip, "accent" => "navy", "icon" => "bi-file-earmark-text"],
            ["label" => "Arsip Publik", "value" => $publik, "accent" => "green", "icon" => "bi-eye"],
            ["label" => "Arsip Private", "value" => $privat, "accent" => "gray", "icon" => "bi-eye-slash"],
            ["label" => "Total PPK", "value" => $totalPpk, "accent" => "green", "icon" => "bi-building"],
            ["label" => "Total Unit Kerja", "value" => $totalUnitKerja, "accent" => "yellow", "icon" => "bi-buildings"],
            ["label" => "Total Arsip Pengadaan", "value" => $paketAll, "accent" => "navy", "icon" => "bi-file-earmark-text", "sub" => "Paket Pengadaan Barang dan Jasa"],
            ["label" => "Total Nilai Pengadaan", "value" => $this->formatRupiahNumber($nilaiAll), "accent" => "yellow", "icon" => "bi-buildings", "sub" => "Nilai Kontrak Pengadaan"],
        ];

        $statusLabels = ["Perencanaan", "Pemilihan", "Pelaksanaan", "Selesai"];
        $statusValues = $this->countByStatusPekerjaan(null, null, $statusLabels);

        $barLabels = [
            "Pengadaan\nLangsung",
            "Penunjukan\nLangsung",
            "E-Purchasing /\nE-Catalog",
            "Tender\nTerbatas",
            "Tender\nTerbuka",
            "Swakelola"
        ];
        $barValues = $this->countByMetodePengadaan(null, null, $barLabels);

        return view('SuperAdmin.Dashboard', compact(
            'superAdminName',
            'summary',
            'tahunOptions',
            'unitOptions',
            'defaultYear',
            'totalUnitKerja',
            'registeredUnits',
            'statusLabels',
            'statusValues',
            'barLabels',
            'barValues'
        ));
    }

    public function dashboardStats(Request $request)
    {
        $tahun = $request->query('tahun');
        $tahun = ($tahun === null || $tahun === '') ? null : (int) $tahun;

        $unitId = $request->query('unit_id');
        $unitId = ($unitId === null || $unitId === '') ? null : (int) $unitId;

        $rawUnit = $request->query('unit');
        if ($unitId === null && $rawUnit !== null && $rawUnit !== '' && $rawUnit !== 'Semua Unit') {
            $unitId = $this->resolveUnitId($rawUnit);
        }

        $paket = Pengadaan::query()
            ->when($unitId !== null, fn($q) => $q->where('unit_id', $unitId))
            ->when($tahun !== null, fn($q) => $q->where('tahun', $tahun))
            ->count();

        $nilai = (int) Pengadaan::query()
            ->when($unitId !== null, fn($q) => $q->where('unit_id', $unitId))
            ->when($tahun !== null, fn($q) => $q->where('tahun', $tahun))
            ->sum('nilai_kontrak');

        $statusLabels = ["Perencanaan", "Pemilihan", "Pelaksanaan", "Selesai"];
        $statusValues = $this->countByStatusPekerjaan($unitId, $tahun, $statusLabels);

        $barLabels = [
            "Pengadaan\nLangsung",
            "Penunjukan\nLangsung",
            "E-Purchasing /\nE-Catalog",
            "Tender\nTerbatas",
            "Tender\nTerbuka",
            "Swakelola"
        ];
        $barValues = $this->countByMetodePengadaan($unitId, $tahun, $barLabels);

        return response()->json([
            'tahun'   => $tahun,
            'unit_id' => $unitId,
            'paket'   => ['count' => $paket],
            'nilai'   => ['sum' => $nilai, 'formatted' => $this->formatRupiahNumber($nilai)],
            'status'  => ['labels' => $statusLabels, 'values' => $statusValues],
            'metode'  => ['labels' => $barLabels, 'values' => $barValues],
        ]);
    }

    public function dashboardData(Request $request)
    {
        return $this->dashboardStats($request);
    }

    public function arsipIndex(Request $request)
    {
        $superAdminName = auth()->user()->name ?? "Super Admin";

        $q      = trim((string) $request->query('q', ''));
        $unitQ  = trim((string) $request->query('unit', 'Semua'));
        $status = trim((string) $request->query('status', 'Semua'));
        $tahunQ = trim((string) $request->query('tahun', 'Semua'));

        $sortNilaiRaw = $request->query('sort_nilai');
        $sortNilai = null;
        if ($sortNilaiRaw !== null && $sortNilaiRaw !== '') {
            $tmp = strtolower(trim((string) $sortNilaiRaw));
            if (in_array($tmp, ['asc', 'desc'], true)) {
                $sortNilai = $tmp;
            }
        }

        $driver = DB::connection()->getDriverName();
        $likeOp = $driver === 'pgsql' ? 'ilike' : 'like';

        $unitId = null;
        $unitQNorm = mb_strtolower($unitQ, 'UTF-8');
        if ($unitQ !== '' && $unitQNorm !== 'semua' && $unitQNorm !== 'semua unit') {
            $unitId = $this->resolveUnitId($unitQ);
        }

        $statusNorm = mb_strtolower($status, 'UTF-8');
        $statusFixed = in_array($statusNorm, ['publik', 'privat'], true) ? ucfirst($statusNorm) : null;

        $tahunInt = null;
        $tahunStr = null;
        if ($tahunQ !== '' && mb_strtolower($tahunQ, 'UTF-8') !== 'semua') {
            $tahunStr = $tahunQ;
            if (ctype_digit($tahunQ)) {
                $tahunInt = (int) $tahunQ;
            }
        }

        $arsipsQuery = Pengadaan::with('unit')
            ->when($unitId !== null, fn($qq) => $qq->where('unit_id', $unitId))
            ->when($statusFixed !== null, fn($qq) => $qq->where('status_arsip', $statusFixed))
            ->when($tahunStr !== null, function ($qq) use ($tahunInt, $tahunStr, $driver) {
                $qq->where(function ($w) use ($tahunInt, $tahunStr, $driver) {
                    if ($tahunInt !== null) {
                        $w->orWhere('tahun', $tahunInt);
                    }

                    if ($driver === 'pgsql') {
                        $w->orWhereRaw("TRIM(CAST(tahun AS TEXT)) = ?", [$tahunStr]);
                    } else {
                        $w->orWhereRaw("TRIM(CAST(tahun AS CHAR)) = ?", [$tahunStr]);
                    }
                });
            })
            ->when($q !== '', function ($query) use ($q, $likeOp, $driver) {
                $like = '%' . $q . '%';

                $query->where(function ($w) use ($q, $like, $likeOp, $driver) {
                    if (ctype_digit($q)) {
                        $w->orWhere('tahun', (int) $q);
                        $w->orWhere('id', (int) $q);
                    }

                    $w->orWhere('nama_pekerjaan', $likeOp, $like)
                        ->orWhere('id_rup', $likeOp, $like)
                        ->orWhere('jenis_pengadaan', $likeOp, $like)
                        ->orWhere('status_arsip', $likeOp, $like)
                        ->orWhere('status_pekerjaan', $likeOp, $like)
                        ->orWhere('nama_rekanan', $likeOp, $like);

                    if ($driver === 'pgsql') {
                        $w->orWhereRaw("CAST(COALESCE(nilai_kontrak,0) AS TEXT) ILIKE ?", [$like]);
                    } else {
                        $w->orWhereRaw("CAST(COALESCE(nilai_kontrak,0) AS CHAR) LIKE ?", [$like]);
                    }

                    $w->orWhereHas('unit', function ($u) use ($like, $likeOp) {
                        $u->where(function ($uu) use ($like, $likeOp) {
                            if (Schema::hasColumn('units', 'nama')) {
                                $uu->orWhere('nama', $likeOp, $like);
                            }
                            if (Schema::hasColumn('units', 'nama_unit')) {
                                $uu->orWhere('nama_unit', $likeOp, $like);
                            }
                            if (Schema::hasColumn('units', 'name')) {
                                $uu->orWhere('name', $likeOp, $like);
                            }
                        });
                    });
                });
            });

        $arsipsQuery->orderByDesc('updated_at')->orderByDesc('id');

        if ($sortNilai !== null) {
            $arsipsQuery->reorder();

            if ($sortNilai === 'asc') {
                $arsipsQuery->orderByRaw('COALESCE(nilai_kontrak, 0) ASC');
            } else {
                $arsipsQuery->orderByRaw('COALESCE(nilai_kontrak, 0) DESC');
            }

            $arsipsQuery->orderByDesc('updated_at')->orderByDesc('id');
        }

        $arsips = $arsipsQuery->paginate(10)->withQueryString();

       $mapped = $arsips->getCollection()->map(function (Pengadaan $p) {
    return [
        'id'                => $p->id,
        'pekerjaan'         => $p->nama_pekerjaan ?? '-',
        'id_rup'            => $p->id_rup ?? '-',
        'idrup'             => $p->id_rup ?? '-',
        'tahun'             => $p->tahun ?? null,
        'metode_pbj'        => $p->metode_pengadaan ?? '-',  // ← key harus metode_pbj
        'jenis_pengadaan'   => $p->jenis_pengadaan ?? '-',
        'jenis'             => $p->jenis_pengadaan ?? '-',   // ← tambahkan ini
        'status_pekerjaan'  => $p->status_pekerjaan ?? '-',
        'status_arsip'      => $p->status_arsip ?? '-',
        'nilai_kontrak'     => $this->formatRupiah($p->nilai_kontrak),
        'pagu'              => $this->formatRupiah($p->pagu_anggaran), // ← key harus pagu
        'pagu_anggaran'     => $this->formatRupiah($p->pagu_anggaran),
        'hps'               => $this->formatRupiah($p->hps),
        'nama_rekanan'      => $p->nama_rekanan ?? '-',
        'rekanan'           => $p->nama_rekanan ?? '-',      // ← tambahkan ini
        'unit'              => $p->unit?->nama ?? ($p->unit?->nama_unit ?? ($p->unit?->name ?? '-')),
        'dokumen'           => $this->buildDokumenList($p),
        'dokumen_tidak_dipersyaratkan' => $this->normalizeArray($p->dokumen_tidak_dipersyaratkan),
    ];
});
        $arsips->setCollection($mapped);

        $years = Pengadaan::whereNotNull('tahun')
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->map(fn($t) => (string) $t)
            ->values()
            ->all();

        if (count($years) === 0) {
            $y = (int) date('Y');
            $years = [(string) $y, (string) ($y - 1), (string) ($y - 2), (string) ($y - 3), (string) ($y - 4)];
        }

        $unitNameCol = Schema::hasColumn('units', 'nama') ? 'nama'
            : (Schema::hasColumn('units', 'nama_unit') ? 'nama_unit'
                : (Schema::hasColumn('units', 'name') ? 'name' : 'id'));

        $unitOptions = Unit::orderBy($unitNameCol, 'asc')
            ->pluck($unitNameCol)
            ->values()
            ->all();

        return view('SuperAdmin.ArsipPBJ', compact('superAdminName', 'arsips', 'unitOptions', 'years'));
    }

    public function pengadaanCreate()
    {
        $superAdminName = auth()->user()->name ?? "Super Admin";

        $tahunOptions = Pengadaan::whereNotNull('tahun')
            ->select('tahun')
            ->distinct()
            ->orderBy('tahun', 'desc')
            ->pluck('tahun')
            ->map(fn($t) => (int) $t)
            ->values()
            ->all();

        if (count($tahunOptions) === 0) {
            $y = (int) date('Y');
            $tahunOptions = [$y, $y - 1, $y - 2, $y - 3, $y - 4];
        }

        $unitNameCol = Schema::hasColumn('units', 'nama') ? 'nama'
            : (Schema::hasColumn('units', 'nama_unit') ? 'nama_unit'
                : (Schema::hasColumn('units', 'name') ? 'name' : 'id'));

        $units = Unit::query()
            ->select(['id', $unitNameCol])
            ->orderBy($unitNameCol, 'asc')
            ->get();

        $unitOptions = $units->pluck($unitNameCol)->values()->all();

        $jenisPengadaanOptions = [
    "Pengadaan Barang",
    "Pengadaan Pekerjaan Konstruksi",
    "Pengadaan Jasa Konsultasi",
    "Pengadaan Jasa Lainnya",
];

$metodePengadaanOptions = [
  'Pengadaan Langsung',
  'Penunjukan Langsung',
  'E-Purchasing / E-Catalogue',
  'Tender Terbatas',
  'Tender Terbuka',
  'Swakelola',
];
        $statusPekerjaanOptions = ["Perencanaan", "Pemilihan", "Pelaksanaan", "Selesai"];

        return view('SuperAdmin.TambahPengadaan', compact(
    'superAdminName',
    'tahunOptions',
    'units',
    'unitNameCol',
    'unitOptions',
    'jenisPengadaanOptions',
    'metodePengadaanOptions', 
    'statusPekerjaanOptions'
));
    }

    public function pengadaanStore(Request $request)
    {
        $rules = [
            'tahun' => 'required|integer|min:2000|max:' . (date('Y') + 5),
            'unit_id'   => 'nullable|integer|exists:units,id',
            'unit_kerja' => 'nullable|string|max:255',
            'nama_pekerjaan' => 'nullable|string|max:255',
            'id_rup' => 'nullable|string|max:255',
            'jenis_pengadaan' => 'required|string|max:100',
            'metode_pengadaan' => 'required|string|max:100',
            'status_pekerjaan' => 'required|string|max:100',
            'status_arsip' => 'required|in:Publik,Privat',
            'pagu_anggaran' => 'nullable|string|max:50',
            'hps' => 'nullable|string|max:50',
            'nilai_kontrak' => 'nullable|string|max:50',
            'nama_rekanan' => 'nullable|string|max:255',
            'dokumen_tidak_dipersyaratkan_json' => 'nullable|string',
            'dokumen_tidak_dipersyaratkan' => 'nullable|array',
        ];

        $data = $request->validate($rules);

        $resolvedUnitId = null;
        if (!empty($data['unit_id'])) {
            $resolvedUnitId = (int) $data['unit_id'];
        } elseif (!empty($data['unit_kerja'])) {
            $resolvedUnitId = $this->resolveUnitId($data['unit_kerja']);
        }

        if (!$resolvedUnitId) {
            $key = !empty($data['unit_id']) ? 'unit_id' : 'unit_kerja';
            return redirect()->back()->withInput()->withErrors([$key => 'Unit kerja tidak valid / tidak ditemukan di database.']);
        }

        $toInt = function ($v) {
            if ($v === null) return null;
            $num = preg_replace('/[^0-9]/', '', (string) $v);
            return $num === '' ? null : (int) $num;
        };

        $payload = [
    'tahun'            => (int) $data['tahun'],
    'unit_id'          => (int) $resolvedUnitId,
    'created_by'       => Auth::id(),
    'nama_pekerjaan'   => $data['nama_pekerjaan'] ?? null,
    'id_rup'           => $data['id_rup'] ?? null,
    'jenis_pengadaan'  => $data['jenis_pengadaan'],
    'metode_pengadaan' => $data['metode_pengadaan'] ?? null,  // ← tambahkan ini
    'status_pekerjaan' => $data['status_pekerjaan'],
    'status_arsip'     => $data['status_arsip'],
    'pagu_anggaran'    => $toInt($data['pagu_anggaran'] ?? null),
    'hps'              => $toInt($data['hps'] ?? null),
    'nilai_kontrak'    => $toInt($data['nilai_kontrak'] ?? null),
    'nama_rekanan'     => $data['nama_rekanan'] ?? null,
];

        $docTidak = [];
        if (is_array($request->input('dokumen_tidak_dipersyaratkan'))) {
            $docTidak = $request->input('dokumen_tidak_dipersyaratkan');
        } else {
            $json = $request->input('dokumen_tidak_dipersyaratkan_json');
            if (is_string($json) && trim($json) !== '') {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $docTidak = $decoded;
                }
            }
        }
        $payload['dokumen_tidak_dipersyaratkan'] = array_values(array_filter($docTidak, fn($x) => $x !== null && $x !== ''));

        DB::beginTransaction();
        try {
            $pengadaan = Pengadaan::create($payload);

            $this->handleUploadDokumenToModel($request, $pengadaan, false);
            $pengadaan->save();

            DB::commit();

            return redirect()->route('superadmin.arsip')->with('success', 'Pengadaan baru berhasil ditambahkan!');
        } catch (\Throwable $e) {
            DB::rollBack();

            if (isset($pengadaan) && $pengadaan instanceof Pengadaan) {
                try {
                    Storage::disk('public')->deleteDirectory("pengadaan/{$pengadaan->id}");
                } catch (\Throwable $ex) {}
                try {
                    $pengadaan->delete();
                } catch (\Throwable $ex) {}
            }

            return redirect()->back()->withInput()->withErrors(['upload' => 'Gagal menyimpan pengadaan/dokumen.']);
        }
    }

    public function arsipEdit($id)
{
    $superAdminName = auth()->user()->name ?? "Super Admin";

    $pengadaan = Pengadaan::with('unit')->findOrFail($id);
    $arsip = $pengadaan;

    $tahunOptions = Pengadaan::whereNotNull('tahun')
        ->select('tahun')
        ->distinct()
        ->orderBy('tahun', 'desc')
        ->pluck('tahun')
        ->map(fn($t) => (int) $t)
        ->values()
        ->all();

    if (count($tahunOptions) === 0) {
        $y = (int) date('Y');
        $tahunOptions = [$y, $y - 1, $y - 2, $y - 3, $y - 4];
    }

    $unitNameCol = Schema::hasColumn('units', 'nama') ? 'nama'
        : (Schema::hasColumn('units', 'nama_unit') ? 'nama_unit'
            : (Schema::hasColumn('units', 'name') ? 'name' : 'id'));

    $units = Unit::query()
        ->select(['id', $unitNameCol])
        ->orderBy($unitNameCol, 'asc')
        ->get();

    $unitOptions = $units->pluck($unitNameCol)->values()->all();

    // 🔹 JENIS PENGADAAN
    $jenisPengadaanOptions = [
    "Pengadaan Barang",
    "Pengadaan Pekerjaan Konstruksi",
    "Pengadaan Jasa Konsultasi",
    "Pengadaan Jasa Lainnya",
];

    // 🔥 METODE PENGADAAN (FIX ERROR DI SINI)
    $metodePengadaanOptions = [
        "Pengadaan Langsung",
        "Penunjukan Langsung",
        "E-Purchasing / E-Catalogue",
        "Tender Terbatas",
        "Tender Terbuka",
        "Swakelola",
    ];

    // 🔹 STATUS PEKERJAAN
    $statusPekerjaanOptions = ["Perencanaan", "Pemilihan", "Pelaksanaan", "Selesai"];

    // 🔹 DOKUMEN EXISTING
    $dokumenExisting = [];
    foreach ($this->dokumenFieldLabels() as $field => $label) {
        $paths = $this->normalizeArray($pengadaan->{$field} ?? null);
        if (count($paths) > 0) {
            $dokumenExisting[$field] = collect($paths)->map(function ($p) use ($label) {
                $path = $this->normalizePublicDiskPath($p);
                return [
                    'label' => $label,
                    'path' => $path,
                    'url' => '/storage/' . ltrim((string) $path, '/'),
                ];
            })->all();
        }
    }

    $pengadaan->dokumen_tidak_dipersyaratkan = $this->normalizeArray($pengadaan->dokumen_tidak_dipersyaratkan);

    return view('SuperAdmin.EditArsip', compact(
        'superAdminName',
        'pengadaan',
        'arsip',
        'tahunOptions',
        'units',
        'unitNameCol',
        'unitOptions',
        'jenisPengadaanOptions',
        'metodePengadaanOptions', // 🔥 INI YANG TAMBAH
        'statusPekerjaanOptions',
        'dokumenExisting'
    ));
}

    public function arsipUpdate(Request $request, $id)
    {
        $pengadaan = Pengadaan::findOrFail($id);

        $rules = [
            'tahun' => 'nullable|integer|min:2000|max:' . (date('Y') + 5),
            'unit_id' => 'nullable|integer|exists:units,id',
            'unit_kerja' => 'nullable|string|max:255',
            'nama_pekerjaan' => 'nullable|string|max:255',
            'id_rup' => 'nullable|string|max:255',
            'jenis_pengadaan' => 'nullable|string|max:100',
            'metode_pengadaan' => 'nullable|string|max:100',
            'status_pekerjaan' => 'nullable|string|max:100',
            'status_arsip' => 'nullable|in:Publik,Privat',
            'pagu_anggaran' => 'nullable|string|max:50',
            'hps' => 'nullable|string|max:50',
            'nilai_kontrak' => 'nullable|string|max:50',
            'nama_rekanan' => 'nullable|string|max:255',
            'dokumen_tidak_dipersyaratkan_json' => 'nullable|string',
            'dokumen_tidak_dipersyaratkan' => 'nullable|array',
        ];

        $data = $request->validate($rules);

        $toInt = function ($v) {
            if ($v === null) return null;
            $num = preg_replace('/[^0-9]/', '', (string) $v);
            return $num === '' ? null : (int) $num;
        };

        DB::beginTransaction();
        try {
            if ($request->filled('tahun')) $pengadaan->tahun = (int) $data['tahun'];

            if ($request->filled('unit_id') || $request->filled('unit_kerja')) {
                $resolvedUnitId = null;

                if ($request->filled('unit_id')) {
                    $resolvedUnitId = (int) $data['unit_id'];
                } elseif ($request->filled('unit_kerja')) {
                    $resolvedUnitId = $this->resolveUnitId($data['unit_kerja']);
                }

                if (!$resolvedUnitId) {
                    DB::rollBack();
                    $key = $request->filled('unit_id') ? 'unit_id' : 'unit_kerja';
                    return redirect()->back()->withInput()->withErrors([$key => 'Unit kerja tidak valid / tidak ditemukan di database.']);
                }

                $pengadaan->unit_id = (int) $resolvedUnitId;
            }

            if ($request->filled('nama_pekerjaan')) $pengadaan->nama_pekerjaan = $data['nama_pekerjaan'];
            if ($request->filled('id_rup')) $pengadaan->id_rup = $data['id_rup'];
            if ($request->filled('jenis_pengadaan')) $pengadaan->jenis_pengadaan = $data['jenis_pengadaan'];
            if ($request->filled('metode_pengadaan')) {$pengadaan->metode_pengadaan = $data['metode_pengadaan'];}
            if ($request->filled('status_pekerjaan')) $pengadaan->status_pekerjaan = $data['status_pekerjaan'];
            if ($request->filled('status_arsip')) $pengadaan->status_arsip = $data['status_arsip'];

            if (array_key_exists('pagu_anggaran', $data)) $pengadaan->pagu_anggaran = $toInt($data['pagu_anggaran']);
            if (array_key_exists('hps', $data)) $pengadaan->hps = $toInt($data['hps']);
            if (array_key_exists('nilai_kontrak', $data)) $pengadaan->nilai_kontrak = $toInt($data['nilai_kontrak']);
            if (array_key_exists('nama_rekanan', $data)) $pengadaan->nama_rekanan = $data['nama_rekanan'];

            $docTidak = [];
            if (is_array($request->input('dokumen_tidak_dipersyaratkan'))) {
                $docTidak = $request->input('dokumen_tidak_dipersyaratkan');
            } else {
                $json = $request->input('dokumen_tidak_dipersyaratkan_json');
                if (is_string($json) && trim($json) !== '') {
                    $decoded = json_decode($json, true);
                    if (is_array($decoded)) {
                        $docTidak = $decoded;
                    }
                }
            }
            $pengadaan->dokumen_tidak_dipersyaratkan = array_values(array_filter($docTidak, fn($x) => $x !== null && $x !== ''));

            $this->handleUploadDokumenToModel($request, $pengadaan, true);
            $this->handleRemoveExistingByHiddenInputs($request, $pengadaan);

            $pengadaan->save();
            DB::commit();

            return redirect()->route('superadmin.arsip')->with('success', 'Arsip berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['update' => 'Gagal memperbarui arsip.']);
        }
    }

   public function arsipDelete($id)
{
    $pengadaan = Pengadaan::findOrFail($id);

    DB::beginTransaction();
    try {
        try {
            Storage::disk('public')->deleteDirectory("pengadaan/{$pengadaan->id}");
        } catch (\Throwable $e) {}

        $pengadaan->delete();

        DB::commit();

        // ✅ return JSON karena JS pakai fetch DELETE
        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('superadmin.arsip')->with('success', 'Arsip berhasil dihapus.');

    } catch (\Throwable $e) {
        DB::rollBack();
        \Log::error('Delete arsip error: ' . $e->getMessage());

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
        return redirect()->back()->withErrors(['delete' => 'Gagal menghapus arsip.']);
    }
}
    public function kelolaMenu()
    {
        $superAdminName = auth()->user()->name ?? "Super Admin";
        return view('SuperAdmin.KelolaMenu', compact('superAdminName'));
    }

    public function updateAkun(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->back()->withErrors(['auth' => 'Kamu belum login.'])->withInput();
        }

        $wantsPasswordChange = $request->filled('password') || $request->filled('password_confirmation');

        $rules = [
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        ];

        if ($wantsPasswordChange) {
            $rules['current_password'] = ['required', 'string'];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        } else {
            $rules['current_password'] = ['nullable', 'string'];
            $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        }

        $data = $request->validate($rules);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if ($wantsPasswordChange) {
            if (!Hash::check($data['current_password'], $user->password)) {
                return redirect()->back()->withErrors(['current_password' => 'Password saat ini salah.'])->withInput();
            }
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()->route('superadmin.kelola.akun')->with('success', 'Akun berhasil diperbarui.');
    }

    public function kelolaAkun()
{
    $user = auth()->user();

    $superAdminName  = $user->name ?? 'Super Admin';
    $superAdminEmail = $user->email ?? 'superadmin@gmail.com';
    $roleText        = 'SUPER ADMIN';

    return view('SuperAdmin.KelolaAkun', compact(
        'superAdminName',
        'superAdminEmail',
        'roleText'
    ));
}

public function kelolaAkunPpk()
{
    $superAdminName = auth()->user()->name ?? 'Super Admin';

    $ppkAccounts = User::query()
        ->whereRaw('LOWER(role) = ?', ['ppk'])
        ->orderBy('id', 'desc')
        ->get()
        ->map(function ($user) {
            return [
                'id'         => $user->id,
                'username'   => $user->name,
                'unit_kerja' => $user->unit_kerja ?? '-',
                'email'      => $user->email,
                'password'   => '********',
                'status'     => $user->status ?? 'Aktif',
            ];
        })
        ->toArray();

    return view('SuperAdmin.KelolaAkunPpk', compact('superAdminName', 'ppkAccounts'));
}
public function storePpk(Request $request)
{
    $request->validate([
        'username'   => ['required', 'string', 'max:255'],
        'unit_kerja' => ['required', 'string', 'max:255'],
        'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
        'password'   => ['required', 'string', 'max:255'],
        'status'     => ['required', 'in:Aktif,Tidak Aktif'],
    ]);

    User::create([
        'name'       => $request->username,
        'unit_kerja' => $request->unit_kerja,
        'email'      => $request->email,
        'password'   => Hash::make($request->password),
        'role'       => 'ppk',
        'status'     => $request->status,
    ]);

    return redirect()
        ->route('superadmin.kelola.akun.ppk')
        ->with('success', 'Data admin PPK berhasil ditambahkan.');
}

public function updatePpk(Request $request, $id)
{
    $user = User::whereRaw('LOWER(role) = ?', ['ppk'])->findOrFail($id);

    $request->validate([
        'username'   => ['required', 'string', 'max:255'],
        'unit_kerja' => ['required', 'string', 'max:255'],
        'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        'password'   => ['nullable', 'string', 'max:255'],
        'status'     => ['required', 'in:Aktif,Tidak Aktif'],
    ]);

    $user->name       = $request->username;
    $user->unit_kerja = $request->unit_kerja;
    $user->email      = $request->email;
    $user->status     = $request->status;
    $user->role       = 'ppk';

    if (!empty($request->password)) {
        $user->password = Hash::make($request->password);
    }

    $user->save();

    return redirect()
        ->route('superadmin.kelola.akun.ppk')
        ->with('success', 'Data admin PPK berhasil diperbarui.');
}

public function destroyPpk($id)
{
    $user = User::whereRaw('LOWER(role) = ?', ['ppk'])->findOrFail($id);
    $user->delete();

    return redirect()
        ->route('superadmin.kelola.akun.ppk')
        ->with('success', 'Data admin PPK berhasil dihapus.');
}

    public function showDokumen($id, $field, $file)
    {
        $pengadaan = Pengadaan::findOrFail($id);

        if (!isset($pengadaan->{$field})) {
            abort(404);
        }

        $arr = $this->normalizeArray($pengadaan->{$field});

        foreach ($arr as $raw) {
            $path = $this->normalizePublicDiskPath($raw);
            if (!$path) continue;

            if (basename($path) === $file) {
                $publicUrl = '/storage/' . ltrim($path, '/');
                return redirect()->route('file.viewer', ['file' => $publicUrl]);
            }
        }

        abort(404);
    }

    public function kelolaAkunUnit()
{
    $superAdminName = auth()->user()->name ?? 'Super Admin';

    $unitAccounts = User::query()
        ->whereRaw('LOWER(role) = ?', ['unit'])
        ->orderBy('id', 'desc')
        ->get()
        ->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->name,
                'unit_kerja' => $user->unit_kerja ?? '-',
                'email' => $user->email,
                'password' => '********',
                'status' => $user->status ?? 'Aktif',
            ];
        })
        ->toArray();

    return view('SuperAdmin.KelolaAkunUnit', compact('superAdminName', 'unitAccounts'));
}

public function storeUnit(Request $request)
{
    $data = $request->validate([
        'username'   => ['required', 'string', 'max:255'],
        'unit_kerja' => ['required', 'string', 'max:255'],
        'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
        'password'   => ['required', 'string', 'min:6'],
        'status'     => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
    ]);

    User::create([
        'name'       => $data['username'],
        'unit_kerja' => $data['unit_kerja'],
        'email'      => $data['email'],
        'password'   => Hash::make($data['password']),
        'role'       => 'unit',
        'status'     => $data['status'],
    ]);

    return redirect()
        ->route('superadmin.kelola.akun.unit')
        ->with('success', 'Data PIC Unit berhasil ditambahkan.');
}

public function updateUnit(Request $request, $id)
{
    $user = User::whereRaw('LOWER(role) = ?', ['unit'])->findOrFail($id);

    $data = $request->validate([
        'username'   => ['required', 'string', 'max:255'],
        'unit_kerja' => ['required', 'string', 'max:255'],
        'email'      => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
        'password'   => ['nullable', 'string', 'min:6'],
        'status'     => ['required', Rule::in(['Aktif', 'Tidak Aktif'])],
    ]);

    $user->name = $data['username'];
    $user->unit_kerja = $data['unit_kerja'];
    $user->email = $data['email'];
    $user->status = $data['status'];
    $user->role = 'unit';

    if (!empty($data['password'])) {
        $user->password = Hash::make($data['password']);
    }

    $user->save();

    return redirect()
        ->route('superadmin.kelola.akun.unit')
        ->with('success', 'Data PIC Unit berhasil diperbarui.');
}

public function destroyUnit($id)
{
    $user = User::whereRaw('LOWER(role) = ?', ['unit'])->findOrFail($id);
    $user->delete();

    return redirect()
        ->route('superadmin.kelola.akun.unit')
        ->with('success', 'Data PIC Unit berhasil dihapus.');
}

    private function countByStatusPekerjaan(?int $unitId, ?int $tahun, array $labels): array
    {
        $rows = Pengadaan::query()
            ->when($unitId !== null, fn($q) => $q->where('unit_id', $unitId))
            ->when($tahun !== null, fn($q) => $q->where('tahun', $tahun))
            ->select('status_pekerjaan', DB::raw('COUNT(*) as cnt'))
            ->groupBy('status_pekerjaan')
            ->pluck('cnt', 'status_pekerjaan')
            ->toArray();

        return array_map(fn($lbl) => (int) ($rows[$lbl] ?? 0), $labels);
    }

    private function countByMetodePengadaan(?int $unitId, ?int $tahun, array $labels): array
    {
        $map = [
            "Pengadaan\nLangsung"        => ["pengadaan langsung"],
            "Penunjukan\nLangsung"       => ["penunjukan langsung"],
            "E-Purchasing /\nE-Catalog"  => [
                "e-purchasing / e-catalog",
                "e-purchasing/e-catalog",
                "e-purchasing",
                "e-catalog",
                "e-catalogue",
                "ecatalog",
                "e catalog",
            ],
            "Tender\nTerbatas"           => ["tender terbatas"],
            "Tender\nTerbuka"            => ["tender terbuka", "tender"],
            "Swakelola"                  => ["swakelola"],
        ];

        $raw = Pengadaan::query()
            ->when($unitId !== null, fn($q) => $q->where('unit_id', $unitId))
            ->when($tahun !== null, fn($q) => $q->where('tahun', $tahun))
            ->whereNotNull('metode_pengadaan')
            ->whereRaw("TRIM(jenis_pengadaan) <> ''")
            ->selectRaw("LOWER(TRIM(jenis_pengadaan)) as jp, COUNT(*) as cnt")
            ->groupBy('jp')
            ->pluck('cnt', 'jp')
            ->toArray();

        $out = [];
        foreach ($labels as $lbl) {
            $alts = $map[$lbl] ?? [mb_strtolower(trim($lbl), 'UTF-8')];
            $sum = 0;
            foreach ($alts as $k) {
                $k = mb_strtolower(trim($k), 'UTF-8');
                $sum += (int) ($raw[$k] ?? 0);
            }
            $out[] = $sum;
        }

        return $out;
    }

    private function handleRemoveExistingByHiddenInputs(Request $request, Pengadaan $pengadaan): void
    {
        $fileFields = array_keys($this->dokumenFieldLabels());

        foreach ($fileFields as $field) {
            $removeKey = $field . '_remove';
            $toRemove = $request->input($removeKey);

            if (!is_array($toRemove) || count($toRemove) === 0) continue;

            $current = $this->normalizeArray($pengadaan->{$field});
            $removeNorm = array_values(array_filter(array_map(fn($x) => $this->normalizePublicDiskPath($x), $toRemove)));

            if (count($removeNorm) === 0) continue;

            $new = array_values(array_filter($current, function ($x) use ($removeNorm) {
                $p = $this->normalizePublicDiskPath($x);
                return $p && !in_array($p, $removeNorm, true);
            }));

            foreach ($removeNorm as $p) {
                try {
                    Storage::disk('public')->delete($p);
                } catch (\Throwable $e) {}
            }

            $pengadaan->{$field} = $new;
        }
    }

    private function handleUploadDokumenToModel(Request $request, Pengadaan $pengadaan, bool $append = true): void
    {
        $fileFields = array_keys($this->dokumenFieldLabels());

        foreach ($fileFields as $field) {
            if (!$request->hasFile($field)) continue;

            $uploaded = $request->file($field);
            $files = is_array($uploaded) ? $uploaded : [$uploaded];

            $paths = $append ? $this->normalizeArray($pengadaan->{$field}) : [];

            foreach ($files as $file) {
                if (!$file || !$file->isValid()) continue;

                $ext = strtolower($file->getClientOriginalExtension());
                $base = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                $safeBase = Str::slug($base);
                if ($safeBase === '') {
                    $safeBase = 'dokumen';
                }

                $filename = $safeBase . '_' . date('Ymd_His') . '_' . Str::random(6) . '.' . $ext;
                $stored = $file->storeAs("pengadaan/{$pengadaan->id}/{$field}", $filename, 'public');

                if ($stored) {
                    $paths[] = $stored;
                }
            }

            $pengadaan->{$field} = array_values($paths);
        }
    }

    private function buildDokumenList(Pengadaan $p): array
    {
        $labels = $this->dokumenFieldLabels();
        $attrs = $p->getAttributes();
        $out = [];

        foreach ($attrs as $field => $rawValue) {
            $lk = strtolower((string) $field);

            if (!(str_contains($lk, 'dokumen') || str_contains($lk, 'file') || str_contains($lk, 'lampiran'))) {
                continue;
            }

            if (in_array($field, ['dokumen_tidak_dipersyaratkan', 'dokumen_tidak_dipersyaratkan_json'], true)) {
                continue;
            }

            $files = $this->normalizeArray($rawValue);
            if (count($files) === 0) continue;

            $label = $labels[$field] ?? Str::title(str_replace('_', ' ', $field));

            foreach ($files as $one) {
                $path = $this->normalizePublicDiskPath($one);
                if (!$path) continue;

                $file = basename($path);

                $out[$field][] = [
                    'label' => $label,
                    'name'  => $file,
                    'path'  => $path,
                    'url'   => '/storage/' . ltrim($path, '/'),
                ];
            }
        }

        return $out;
    }

    private function normalizePublicDiskPath($raw): ?string
    {
        if ($raw === null) return null;

        $s = trim((string) $raw);
        if ($s === '') return null;

        $s = str_replace('\\', '/', $s);
        $s = explode('?', $s)[0];

        if (Str::startsWith($s, ['http://', 'https://'])) {
            $u = parse_url($s);
            if (!empty($u['path'])) {
                $s = $u['path'];
            }
        }

        $s = ltrim($s, '/');

        if (Str::startsWith($s, 'public/'))  $s = Str::after($s, 'public/');
        if (Str::startsWith($s, 'storage/')) $s = Str::after($s, 'storage/');
        $s = preg_replace('#^storage/#', '', $s);

        return $s !== '' ? $s : null;
    }

    private function dokumenFieldLabels(): array
    {
        return [
            'dokumen_kak' => 'Kerangka Acuan Kerja (KAK)',
            'dokumen_hps' => 'Harga Perkiraan Sendiri (HPS)',
            'dokumen_spesifikasi_teknis' => 'Spesifikasi Teknis',
            'dokumen_rancangan_kontrak' => 'Rancangan Kontrak',
            'dokumen_lembar_data_kualifikasi' => 'Lembar Data Kualifikasi',
            'dokumen_lembar_data_pemilihan' => 'Lembar Data Pemilihan',
            'dokumen_daftar_kuantitas_harga' => 'Daftar Kuantitas dan Harga',
            'dokumen_jadwal_lokasi_pekerjaan' => 'Jadwal & Lokasi Pekerjaan',
            'dokumen_gambar_rancangan_pekerjaan' => 'Gambar Rancangan Pekerjaan',
            'dokumen_amdal' => 'Dokumen AMDAL',
            'dokumen_penawaran' => 'Dokumen Penawaran',
            'surat_penawaran' => 'Surat Penawaran',
            'dokumen_kemenkumham' => 'Kemenkumham',
            'ba_pemberian_penjelasan' => 'BA Pemberian Penjelasan',
            'ba_pengumuman_negosiasi' => 'BA Pengumuman Negosiasi',
            'ba_sanggah_banding' => 'BA Sanggah / Sanggah Banding',
            'ba_penetapan' => 'BA Penetapan',
            'laporan_hasil_pemilihan' => 'Laporan Hasil Pemilihan',
            'dokumen_sppbj' => 'SPPBJ',
            'surat_perjanjian_kemitraan' => 'Perjanjian Kemitraan',
            'surat_perjanjian_swakelola' => 'Perjanjian Swakelola',
            'surat_penugasan_tim_swakelola' => 'Penugasan Tim Swakelola',
            'dokumen_mou' => 'MoU',
            'dokumen_kontrak' => 'Dokumen Kontrak',
            'ringkasan_kontrak' => 'Ringkasan Kontrak',
            'jaminan_pelaksanaan' => 'Jaminan Pelaksanaan',
            'jaminan_uang_muka' => 'Jaminan Uang Muka',
            'jaminan_pemeliharaan' => 'Jaminan Pemeliharaan',
            'surat_tagihan' => 'Surat Tagihan',
            'surat_pesanan_epurchasing' => 'Surat Pesanan E-Purchasing',
            'dokumen_spmk' => 'SPMK',
            'dokumen_sppd' => 'SPPD',
            'laporan_pelaksanaan_pekerjaan' => 'Laporan Hasil Pelaksanaan',
            'laporan_penyelesaian_pekerjaan' => 'Laporan Penyelesaian',
            'bap' => 'BAP',
            'bast_sementara' => 'BAST Sementara',
            'bast_akhir' => 'BAST Akhir',
            'dokumen_pendukung_lainya' => 'Dokumen Pendukung Lainnya',
        ];
    }

    private function normalizeArray($value): array
    {
        if ($value === null) return [];

        if (is_array($value)) {
            return array_values(array_filter($value, fn($v) => $v !== null && $v !== ''));
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, fn($v) => $v !== null && $v !== ''));
            }
            return $value !== '' ? [$value] : [];
        }

        return [];
    }

    private function formatRupiah($value): string
    {
        if ($value === null || $value === '') return '-';
        $num = (int) $value;
        return 'Rp ' . number_format($num, 0, ',', '.');
    }

    private function formatRupiahNumber($value): string
    {
        $num = (int) ($value ?? 0);
        return 'Rp ' . number_format($num, 0, ',', '.');
    }

    private function resolveUnitId($rawUnit): ?int
    {
        if ($rawUnit === null) return null;

        $raw = trim((string) $rawUnit);
        if ($raw === '') return null;

        if (ctype_digit($raw)) return (int) $raw;

        if (!Schema::hasTable('units')) return null;

        $hasKode = Schema::hasColumn('units', 'kode');
        $hasSlug = Schema::hasColumn('units', 'slug');
        $hasUnitIdCol = Schema::hasColumn('units', 'unit_id');

        try {
            if ($hasKode) {
                $u = Unit::whereRaw('LOWER(kode) = ?', [mb_strtolower($raw)])->first();
                if ($u) return (int) $u->id;
            }

            if ($hasSlug) {
                $u = Unit::whereRaw('LOWER(slug) = ?', [mb_strtolower($raw)])->first();
                if ($u) return (int) $u->id;
            }

            if ($hasUnitIdCol) {
                $u = Unit::whereRaw('LOWER(unit_id) = ?', [mb_strtolower($raw)])->first();
                if ($u) return (int) $u->id;
            }

            if (Schema::hasColumn('units', 'nama')) {
                $u = Unit::whereRaw('LOWER(nama) = ?', [mb_strtolower($raw)])->first();
                if ($u) return (int) $u->id;
            }

            if (Schema::hasColumn('units', 'nama_unit')) {
                $u = Unit::whereRaw('LOWER(nama_unit) = ?', [mb_strtolower($raw)])->first();
                if ($u) return (int) $u->id;
            }

            if (Schema::hasColumn('units', 'name')) {
                $u = Unit::whereRaw('LOWER(name) = ?', [mb_strtolower($raw)])->first();
                if ($u) return (int) $u->id;
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }
}