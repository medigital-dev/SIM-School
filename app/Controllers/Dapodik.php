<?php

namespace App\Controllers;

use App\Models\AnggotaRombelModel;
use App\Models\DapodikSyncModel;
use App\Models\KontakModel;
use App\Models\OrangtuaWaliModel;
use App\Models\OrtuWaliPdModel;
use App\Models\PeriodikModel;
use App\Models\PesertaDidikModel;
use App\Models\RefAgamaModel;
use App\Models\RefPekerjaanModel;
use App\Models\RegistrasiPesertaDidikModel;
use App\Models\RiwayatTestKoneksiModel;
use App\Models\RombelModel;
use App\Models\SemesterModel;
use CodeIgniter\API\ResponseTrait;

class Dapodik extends BaseController
{
    use ResponseTrait;

    public function __construct()
    {
        helper(['string', 'indonesia']);
    }

    public function index(): string
    {
        $page = [
            'title' => 'SISPADU - Pengaturan Dapodik',
            'sidebar' => 'dapodik',
            'name' => 'Koneksi Aplikasi Dapodik',
            'data' => [],
        ];
        return view('dapodik/index', $page);
    }

    public function getTable()
    {
        $mDapodik = new DapodikSyncModel();
        $mRiwayatTest = new RiwayatTestKoneksiModel();
        $sendDapodik = [];
        $dataDapodik = $mDapodik
            ->select(['dapodik_id as id', 'nama', 'url', 'port', 'npsn', 'token', 'status'])
            ->orderBy('status', 'DESC')
            ->findAll();
        foreach ($dataDapodik as $dapodik) {
            $color = 'secondary';
            $icon = 'fa-minus-circle';
            $tglWaktu = '--/--/-- --:--';

            $riwayat = $mRiwayatTest->where('dapodik_id', $dapodik['id'])
                ->orderBy('tanggal_waktu', 'DESC')
                ->first();
            if ($riwayat) {
                $color = $riwayat['status'] ? 'success' : 'danger';
                $icon = $riwayat['status'] ? 'fa-check-circle' : $icon;
                $tglWaktu = $riwayat ? tanggal($riwayat['tanggal_waktu'], 'd-m-Y H:i') : $tglWaktu;
            }

            $status = $dapodik['status'] ? '<span class="text-success"><i class="fas fa-check-circle fa-fw"></i></span>' : '<span class="text-secondary"><i class="fas fa-minus-circle fa-fw"></i></span>';
            $temp = [
                'checkbox' => '
                    <div class="custom-control custom-checkbox">
                        <input class="custom-control-input dtCheckbox" type="checkbox" id="check_' . $dapodik['id'] . '" value="' . $dapodik['id'] . '">
                        <label for="check_' . $dapodik['id'] . '" class="custom-control-label"></label>
                    </div>',
                'nama' => $dapodik['nama'],
                'url' => '<a href="//' . $dapodik['url'] . ':' . $dapodik['port'] . '" target="_blank">' . $dapodik['url'] . ':' . $dapodik['port'] . '</a>',
                'npsn' => $dapodik['npsn'],
                'token' => $dapodik['token'],
                'status' => $status,
                'koneksi' => '<a type="button" class="text-decoration-none btnRiwayatTestKoneksiDapodik" title="Riwayat Koneksi" data-id="' . $dapodik['id'] . '"><span class="badge p-2 bg-' . $color . '"><i class="fas ' . $icon . ' fa-fw"></i> ' . $tglWaktu . '</span></a>',
            ];
            array_push($sendDapodik, $temp);
        }
        return $this->respond($sendDapodik);
    }

    public function set()
    {
        $set = $this->request->getVar('set');
        $id = $this->request->getVar('id');
        $mDapodik = new DapodikSyncModel();
        $data = $mDapodik->where('dapodik_id', $id)->first();

        if (!$data) $set['dapodik_id'] = unik($mDapodik, 'dapodik_id');
        else $set['id'] = $data['id'];

        if (!$mDapodik->save($set)) return $this->fail('Error: Konfigurasi koneksi dapodik tidak dapat disimpan.');

        $response = [
            'message' => 'Konfigurasi koneksi dapodik berhasil disimpan.',
            'data' => $set
        ];
        return $this->respond($response);
    }

    public function get()
    {
        $mDapodik = new DapodikSyncModel();
        $id = $this->request->getVar('id');

        $data = $mDapodik->select(['dapodik_id as id', 'nama', 'url', 'port', 'npsn', 'token'])
            ->where('dapodik_id', $id)
            ->first();
        if (!$data) return $this->fail('Error: Data koneksi dapodik tidak ditemukan.');
        return $this->respond($data);
    }

    public function delete()
    {
        $mDapodik = new DapodikSyncModel();
        $mRiwayatTest = new RiwayatTestKoneksiModel();
        $idDeleted = [];
        $ids = $this->request->getVar('ids');
        foreach ($ids as $id) {
            $data = $mDapodik->where('dapodik_id', $id)
                ->first();
            if (!$data) return $this->fail('Error tidak ditemukan: #' . $id);;
            array_push($idDeleted, $data['id']);
        }
        if (!$mDapodik->delete($idDeleted, true)) return $this->fail('Error: ' . count($idDeleted) . ' data koneksi dapodik gagal dihapus.');
        if (!$mRiwayatTest->where('dapodik_id', $id)->delete()) return $this->fail('Error: Riwayat test koneksi dapodik gagal dihapus.');
        $mRiwayatTest->purgeDeleted();
        $response = [
            'message' => count($idDeleted) . ' profil koneksi dapodik berhasil dihapus.',
            'data' => $ids,
        ];
        return $this->respond($response);
    }

    public function setAktif()
    {
        $mDapodik = new DapodikSyncModel();
        $id = $this->request->getVar('id');
        $data = $mDapodik->where('dapodik_id', $id)->first();
        if (!$data) return $this->fail('Error: Data koneksi dapodik tidak ditemukan.');
        $aktif = $mDapodik->where('status', true)->first();
        if ($aktif) {
            if (!$mDapodik->update($aktif['id'], ['status' => false])) return $this->fail('Error: Penonaktifan data koneksi dapodik gagal.');
        }
        if (!$mDapodik->update($data['id'], ['status' => true])) return $this->fail('Error: Pengaktifan data koneksi dapodik gagal.');
        $response = [
            'message' => 'Profil <strong>' . $data['nama'] . '</strong> berhasil diaktikan.',
            'data' => $data,
        ];
        return $this->respond($response);
    }

    private function getConfig()
    {
        $mDapodik = new DapodikSyncModel();
        $config = $mDapodik->where('status', true)->first();
        if ($config) {
            return [
                'url' => $config['url'],
                'port' => $config['port'],
                'npsn' => $config['npsn'],
                'token' => $config['token'],
            ];
        }
        return [];
    }
    /**
     * @param string $type Enum ['getPesertaDidik','getSekolah','getRombonganBelajar','get]
     * @return array Response
     */
    private function makeRequest($type, $config = [])
    {
        $client = \Config\Services::curlrequest();
        if (empty($config)) {
            $config = $this->getConfig();
            if (empty($config)) return ['message' => 'Error: Konfigurasi koneksi dapodik belum diset.', 'success' => false];
        }
        $url = 'http://' . $config['url'] . ':' . $config['port'] . '/WebService/' . $type . '?npsn=' . $config['npsn'];
        $token = 'Authorization: Bearer ' . $config['token'];

        $options = [
            'headers' => ['Authorization' => $token],
            'http_errors' => false,
            'debug' => true,
        ];

        try {
            $response = $client->get($url, $options);
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                return  [
                    'success' => false,
                    'http_code' => $statusCode,
                    'status_code' => 'error',
                    'message' => $response->getReason(),
                    'data' => [],
                ];
            }
            $hasil = $response->getBody();
            $awal = strpos($hasil, '{');
            $result = json_decode(substr($hasil, $awal), true);
            if (isset($result['results'])) {
                $response = [
                    'success' => true,
                    'http_code' => 200,
                    'status_code' => 'success',
                    'message' => 'Koneksi ke aplikasi Dapodik berhasil.',
                    'data' => $result['rows'],
                ];
            } else {
                $response = $result;
                $response['data'] = [];
            }

            return $response;
        } catch (\Exception $e) {
            return  [
                'success' => false,
                'http_code' => 400,
                'status_code' => 'error',
                'message' => $e->getMessage(),
                'data' => [],
            ];
        }
    }

    public function testKoneksi()
    {
        $mDapodik = new DapodikSyncModel();
        $mRiwayatTest = new RiwayatTestKoneksiModel();
        $id = $this->request->getVar('id');
        $config = $mDapodik->where('dapodik_id', $id)->first();
        if (empty($config))
            return $this->fail('Error: Konfigurasi dapodik belum diset aktif.');

        $result = $this->makeRequest('getSekolah', $config);

        $temp = [
            'riwayat_id' => unik($mRiwayatTest, 'riwayat_id'),
            'dapodik_id' => $id,
            'tanggal_waktu' => date('Y-m-d H:i:s'),
            'pesan' => $result['message']
        ];

        if (!$result['success']) {
            $temp['status'] =  false;
            if (!$mRiwayatTest->save($temp)) return $this->fail('Error: Riwayat testing koneksi dapodik gagal disimpan.');
            return $this->fail($result['message']);
        }

        $temp['status'] = true;
        if (!$mRiwayatTest->save($temp)) return $this->fail('Error: Riwayat testing koneksi dapodik gagal disimpan.');

        $response = [
            'nama' => $result['data']['nama'],
            'npsn' => $result['data']['npsn'],
        ];
        return $this->respond($response);
    }

    public function getRiwayatTest()
    {
        $mDapodik = new DapodikSyncModel();
        $mRiwayatTest = new RiwayatTestKoneksiModel();
        $id = $this->request->getVar('id');
        $data = $mDapodik->where('dapodik_id', $id)->first();
        if (!$data) return $this->fail('Error: Data koneksi dapodik tidak ditemukan.');
        $riwayat = $mRiwayatTest->where('dapodik_id', $id)->orderBy('tanggal_waktu', 'DESC')
            ->findAll();
        $data['riwayat'] = $riwayat;
        return view('dapodik/riwayat-test', $data);
    }

    public function syncPd()
    {
        $mPesertaDidik = new PesertaDidikModel();
        $mRegistrasi = new RegistrasiPesertaDidikModel();
        $mOrtuWali = new OrangtuaWaliModel();
        $mOrtuWaliPd = new OrtuWaliPdModel();
        $mRombel = new RombelModel();
        $mAnggotaRombel = new AnggotaRombelModel();
        $mSemester = new SemesterModel();
        $mKontak = new KontakModel();
        $mPeriodik = new PeriodikModel();
        $mRefAgama = new RefAgamaModel();
        $mRefPekerjaan = new RefPekerjaanModel();

        $request = $this->makeRequest('getPesertaDidik');
        if (!$request['success']) return $this->fail($request['message']);

        foreach ($request['data'] as $row) {
            $idPd = $row['peserta_didik_id'];
            $nik = $row['nik'];

            $setRegistrasi = [
                'registrasi_id' => $row['registrasi_id'],
                'peserta_didik_id' => $row['peserta_didik_id'],
                'jenis_registrasi' => $row['jenis_pendaftaran_id_str'],
                'tanggal_registrasi' => $row['tanggal_masuk_sekolah'],
                'nipd' => $row['nipd'],
                'asal_sekolah' => $row['sekolah_asal'],
            ];
            $cReg = $mRegistrasi->where('peserta_didik_id', $idPd)->first();
            if ($cReg) $setRegistrasi['id'] = $cReg['id'];
            if (!$mRegistrasi->save($setRegistrasi)) return $this->fail('Error: Registrasi Peserta Didik an. ' . $row['nama'] . ' gagal disimpan.');

            $setAgama = [
                'nama' => $row['agama_id_str'],
            ];
            $cRefAgama = $mRefAgama->where('nama', $setAgama['nama'])->first();
            if (!$cRefAgama) {
                $setAgama['ref_id'] = unik($mRefAgama, 'ref_id');
                if (!$mRefAgama->save($setAgama)) return $this->fail('Error: Referensi Agama gagal disimpan.');
            } else
                $setAgama['ref_id'] = $cRefAgama['ref_id'];

            $setPd = [
                'peserta_didik_id' => $row['peserta_didik_id'],
                'nama' => $row['nama'],
                'jenis_kelamin' => $row['jenis_kelamin'],
                'tempat_lahir' => $row['tempat_lahir'],
                'tanggal_lahir' => $row['tanggal_lahir'],
                'nisn' => $row['nisn'],
                'nik' => $row['nik'],
                'agama_id' => $setAgama['ref_id'],
            ];
            $cPd = $mPesertaDidik->where('peserta_didik_id', $idPd)->first();
            if ($cPd) $setPd['id'] = $cPd['id'];
            if (!$mPesertaDidik->save($setPd)) return $this->fail('Error: Peserta Didik an. ' . $row['nama'] . ' gagal disimpan.');

            $setKontakPd = [
                'nik' => $nik,
                'telepon' => $row['nomor_telepon_rumah'],
                'hp' => $row['nomor_telepon_seluler'],
                'email' => $row['email'],
            ];
            $cKontak = $mKontak->where('nik', $nik)->first();
            if ($cKontak) $setKontakPd['id'] = $cKontak['id'];
            else $setKontakPd['kontak_id'] = unik($mKontak, 'kontak_id');
            if (!$mKontak->save($setKontakPd)) return $this->fail('Error: Kontak peserta didik gagal disimpan.');

            // Mulai Ortu: Ayah
            if ($row['nama_ayah']) {
                $setPekerjaanAyah = [
                    'nama' => $row['pekerjaan_ayah_id_str'],
                ];
                $cPekerjaan = $mRefPekerjaan->where('nama', $setPekerjaanAyah['nama'])->first();
                if (!$cPekerjaan) {
                    $setPekerjaanAyah['ref_id'] = unik($mRefPekerjaan, 'ref_id');
                    if (!$mRefPekerjaan->save($setPekerjaanAyah)) return $this->fail('Error: Referensi pekerjaan gagal disimpan.');
                } else $setPekerjaanAyah['ref_id'] = $cPekerjaan['ref_id'];

                $setOrtuAyah = [
                    'nama' => $row['nama_ayah'],
                    'pekerjaan_id' => $setPekerjaanAyah['ref_id'],
                ];
                $cOrtuWali = $mOrtuWali->where('nama', $setOrtuAyah['nama'])->first();
                if (!$cOrtuWali) {
                    $setOrtuAyah['orangtua_id'] = unik($mOrtuWali, 'orangtua_id');
                    if (!$mOrtuWali->save($setOrtuAyah)) return $this->fail('Error: Orangtua/Wali an ' . $setOrtuAyah['nama'] . ' gagal disimpan.');
                } else $setOrtuAyah['orangtua_id'] = $cOrtuWali['orangtua_id'];
                $idAyah = $setOrtuAyah['orangtua_id'];
                if (!$mOrtuWaliPd->where('peserta_didik_id', $idPd)->where('orangtua_id', $idAyah)->first()) {
                    $setOrtuPd = [
                        'ortupd_id' => unik($mOrtuWaliPd, 'ortupd_id'),
                        'peserta_didik_id' => $idPd,
                        'orangtua_id' => $idAyah,
                    ];
                    if (!$mOrtuWaliPd->save($setOrtuPd)) return $this->fail('Error: Data orangtua/wali peserta didik gagal disimpan.');
                }
            }
            // Akhir Ortu: Ayah

            // Mulai Ortu: Ibu
            if ($row['nama_ibu']) {
                $setPekerjaanIbu = [
                    'nama' => $row['pekerjaan_ibu_id_str'],
                ];
                $cPekerjaan = $mRefPekerjaan->where('nama', $setPekerjaanIbu['nama'])->first();
                if (!$cPekerjaan) {
                    $setPekerjaanIbu['ref_id'] = unik($mRefPekerjaan, 'ref_id');
                    if (!$mRefPekerjaan->save($setPekerjaanIbu)) return $this->fail('Error: Referensi pekerjaan gagal disimpan.');
                } else $setPekerjaanIbu['ref_id'] = $cPekerjaan['ref_id'];

                $setOrtuIbu = [
                    'nama' => $row['nama_ibu'],
                    'pekerjaan_id' => $setPekerjaanIbu['ref_id'],
                ];
                $cOrtuWali = $mOrtuWali->where('nama', $setOrtuIbu['nama'])->first();
                if (!$cOrtuWali) {
                    $setOrtuIbu['orangtua_id'] = unik($mOrtuWali, 'orangtua_id');
                    if (!$mOrtuWali->save($setOrtuIbu)) return $this->fail('Error: Orangtua/Wali an ' . $setOrtuIbu['nama'] . ' gagal disimpan.');
                } else $setOrtuIbu['orangtua_id'] = $cOrtuWali['orangtua_id'];
                $idIbu = $setOrtuIbu['orangtua_id'];
                if (!$mOrtuWaliPd->where('peserta_didik_id', $idPd)->where('orangtua_id', $idIbu)->first()) {
                    $setOrtuPd = [
                        'ortupd_id' => unik($mOrtuWaliPd, 'ortupd_id'),
                        'peserta_didik_id' => $idPd,
                        'orangtua_id' => $idIbu,
                    ];
                    if (!$mOrtuWaliPd->save($setOrtuPd)) return $this->fail('Error: Data orangtua/wali peserta didik gagal disimpan.');
                }
            }
            // Akhir Ortu: Ibu

            // Mulai Ortu: Wali
            if ($row['nama_wali']) {
                $setPekerjaanWali = [
                    'nama' => $row['pekerjaan_wali_id_str'],
                ];
                $cPekerjaan = $mRefPekerjaan->where('nama', $setPekerjaanWali['nama'])->first();
                if (!$cPekerjaan) {
                    $setPekerjaanWali['ref_id'] = unik($mRefPekerjaan, 'ref_id');
                    if (!$mRefPekerjaan->save($setPekerjaanWali)) return $this->fail('Error: Referensi pekerjaan gagal disimpan.');
                } else $setPekerjaanWali['ref_id'] = $cPekerjaan['ref_id'];

                $setOrtuWali = [
                    'nama' => $row['nama_wali'],
                    'pekerjaan_id' => $setPekerjaanWali['ref_id'],
                ];
                $cOrtuWali = $mOrtuWali->where('nama', $setOrtuWali['nama'])->first();
                if (!$cOrtuWali) {
                    $setOrtuWali['orangtua_id'] = unik($mOrtuWali, 'orangtua_id');
                    if (!$mOrtuWali->save($setOrtuWali)) return $this->fail('Error: Orangtua/Wali an ' . $setOrtuWali['nama'] . ' gagal disimpan.');
                } else $setOrtuWali['orangtua_id'] = $cOrtuWali['orangtua_id'];
                $idWali = $setOrtuWali['orangtua_id'];
                if (!$mOrtuWaliPd->where('peserta_didik_id', $idPd)->where('orangtua_id', $idWali)->first()) {
                    $setOrtuPd = [
                        'ortupd_id' => unik($mOrtuWaliPd, 'ortupd_id'),
                        'peserta_didik_id' => $idPd,
                        'orangtua_id' => $idWali,
                    ];
                    if (!$mOrtuWaliPd->save($setOrtuPd)) return $this->fail('Error: Data orangtua/wali peserta didik gagal disimpan.');
                }
            }
            // Akhir Ortu: Wali

            $cPeriodik = $mPeriodik
                ->where('nik', $nik)
                ->where('tinggi_badan', $row['tinggi_badan'])
                ->where('berat_badan', $row['berat_badan'])
                ->where('anak_ke', $row['anak_keberapa'])
                ->first();
            $setPeriodik = [
                'tinggi_badan' => $row['tinggi_badan'],
                'berat_badan' => $row['berat_badan'],
                'anak_ke' => $row['anak_keberapa'],
                'nik' => $nik,
                'tanggal' => date('Y-m-d'),
            ];
            if (!$cPeriodik) {
                $setPeriodik['periodik_id'] = unik($mPeriodik, 'periodik_id');
                if (!$mPeriodik->save($setPeriodik)) return $this->fail('Error: Data periodik an. ' . $setPd['nama'] . ' gagal disimpan.');
            }

            $setSemester = [
                'kode' => $row['semester_id'],
                'status' => true,
            ];
            $mSemester->where('status', true)->set('status', false)->update();
            $cSemester = $mSemester->where('kode', $row['semester_id'])
                ->first();
            if (!$cSemester)
                $setSemester['semester_id'] = unik($mSemester, 'semester_id');
            else {
                $setSemester['id'] = $cSemester['id'];
                $setSemester['semester_id'] = $cSemester['semester_id'];
            };
            if (!$mSemester->save($setSemester)) return $this->fail('Error: Data semester gagal disimpan.');

            $setRombel = [
                'rombel_id' => $row['rombongan_belajar_id'],
                'tingkat_pendidikan' => $row['tingkat_pendidikan_id'],
                'nama' => $row['nama_rombel'],
                'semester_id' => $setSemester['semester_id']
            ];
            $cRombel = $mRombel->where('rombel_id', $setRombel['rombel_id'])->first();
            if ($cRombel)
                $setRombel['id'] = $cRombel['id'];
            if (!$mRombel->save($setRombel)) return $this->fail('Error: Data rombongan belajar gagal disimpan.');

            $cAnggotaRombel = $mAnggotaRombel->where('rombel_id', $row['rombongan_belajar_id'])
                ->where('peserta_didik_id', $idPd)
                ->first();
            if (!$cAnggotaRombel) {
                $setAnggotaRombel = [
                    'anggota_id' => $row['anggota_rombel_id'],
                    'rombel_id' => $row['rombongan_belajar_id'],
                    'jenis_registrasi_rombel' => $row['jenis_pendaftaran_id_str'],
                    'peserta_didik_id' => $idPd,
                ];
                if (!$mAnggotaRombel->save($setAnggotaRombel)) return $this->fail('Error: Data anggota rombel gagal disimpan.');
            }
        }

        $response = [
            'message' => count($request['data']) . ' Data peserta didik berhasil disinkronkan.',
        ];
        return $this->respond($response);
    }
}
