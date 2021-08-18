<?php

namespace App\Controllers;

use App\Models\AbsensiModel;
use App\Models\KaryawanModel;
use App\Models\GajiModel;
use App\Models\StatusAbsensiModel;
use DB;
use CodeIgniter\I18n\Time;

class Home extends BaseController
{

	protected $karyawanModel;
	protected $absensiModel;
	protected $statusAbsenModel;
	protected $gajiModel;

	public function __construct()
	{
		$this->karyawanModel = new KaryawanModel();
		$this->absensiModel = new AbsensiModel();
		$this->statusAbsenModel = new StatusAbsensiModel();
		$this->gajiModel = new GajiModel();
	}

	public function index()
	{
		$db      = \Config\Database::connect();
		$username = session()->get('ses_username');

		$absensi = $this->absensiModel
			->select('
				SUM(IF(absensi.id_status = 1 OR absensi.id_status = 2, 1, 0)) AS hadir,
				SUM(IF(absensi.id_status = 2, 1, 0)) AS telat,
				SUM(IF(absensi.id_status = 3, 1, 0)) AS absen
				')
			->join('karyawan', 'karyawan.id = absensi.id_karyawan', 'inner')
			->join('users', 'users.id_karyawan = karyawan.id', 'inner')
			->where('users.username', $username)
			->first();
		
		$cek_absen = $this->absensiModel
			->select('absensi.*')
			->join('karyawan', 'karyawan.id = absensi.id_karyawan', 'inner')
			->join('users', 'users.id_karyawan = karyawan.id', 'inner')
			->where([
				'users.username' => $username,
				'absensi.tanggal' => date("Y-m-d")
			])
			->first();

		$jumlah_karyawan = $db->table('karyawan')
					->countAllResults();
					// ->get();
					
		// $timestamp = strtotime('today midnight');
		// $timestamp  = new Time('now');
		$timestamp  = Time::today('Asia/Jakarta', 'en');

		// dd($timestamp);

		$jumlah_absensi = $this->absensiModel
					->where('created_at >', $timestamp)
					->countAllResults();

		// dd($jumlah_absensi);

		// ====================================================================

		// dd(session()->get('ses_nama'));
		$id_karyawan = $this->karyawanModel
		->select('karyawan.id')
		->where('karyawan.nama', session()->get('ses_nama') )
		->first();

		$gaji = $this->gajiModel
		->where('id_karyawan', $id_karyawan)
		->where('bulan', date("m"))
		->findAll();

		if (session()->get('ses_userRole') == 1) {
			$gaji_karyawan = [
				'salary' => 0,
				'potongan' => 0,
				'gaji_bersih' => 0
			];
		} elseif (session()->get('ses_userRole') == 0) {
			if ($gaji) {
					$gaji_karyawan = [
					'salary' => $gaji[0]['salary'],
					'potongan' => $gaji[0]['potongan'],
					'gaji_bersih' => $gaji[0]['gaji_bersih']
				];
			} else {
				$gaji_karyawan = [
					'salary' => 0,
					'potongan' => 0,
					'gaji_bersih' => 0
				];
			}
		}
		// ====================================================================

		$data = [
			'title' => 'SIPAKAR',
			'absensi' => $absensi,
			'cek_absen' => $cek_absen,
			'jumlah_karyawan' => $jumlah_karyawan,
			'absensi_hari_ini'	=> $jumlah_absensi,
			'gaji_karyawan' => $gaji_karyawan
		];

		// dd($data);

		if (session()->get('ses_userRole') != 1) {
			if (empty($cek_absen)) {
				$karyawan = $this->karyawanModel
					->select('karyawan.id, karyawan.nama')
					->join('users', 'users.id_karyawan = karyawan.id', 'inner')
					->where('users.username', $username)
					->first();
				$data_absen_karyawan = [
					'title' => 'Absen Karyawan',
					'karyawan' => $karyawan
				];
				// dd($data_absen_karyawan);
				return view('absensi/absen_karyawan', $data_absen_karyawan);
			}
		}

		return view('pages/dashboard', $data);
	}

	public function setting()
	{
		$data = [
			'title' => "User Settings - SIPAKAR",
			'validation' => \Config\Services::validation(),
		];

		return view('pages/setting', $data);
	}
}
