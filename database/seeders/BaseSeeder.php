<?php

namespace Database\Seeders;

use App\Models\AiModelVersion;
use App\Models\Classroom;
use App\Models\CreditCategory;
use App\Models\IncidentCategory;
use App\Models\KeywordStopword;
use App\Models\Role;
use Illuminate\Database\Seeder;

/** Data wajib (produksi & lokal): peran, kategori, stopword, pengaturan. */
class BaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['siswa', 'Siswa'], ['admin', 'Admin sekolah'], ['bk', 'Guru BK'], ['wali_kelas', 'Wali kelas'],
        ] as [$name, $label]) {
            Role::updateOrCreate(['name' => $name], ['label' => $label]);
        }

        foreach ([
            ['Perundungan', 'Ejekan, pengucilan, atau intimidasi berulang.'],
            ['Kekerasan fisik', 'Pemukulan, dorongan, atau tindakan fisik lain.'],
            ['Kekerasan verbal', 'Kata-kata kasar, hinaan, atau bentakan.'],
            ['Pelecehan', 'Perilaku tidak pantas yang membuat tidak nyaman.'],
            ['Ancaman', 'Ancaman terhadap diri, barang, atau keluarga.'],
            ['Perundungan online', 'Perundungan lewat media sosial atau grup obrolan.'],
            ['Lainnya', 'Kejadian lain yang perlu diketahui BK.'],
        ] as $i => [$n, $d]) {
            IncidentCategory::updateOrCreate(['name' => $n], ['description' => $d, 'urutan' => $i + 1]);
        }

        foreach ([['Terlambat', 5], ['Atribut tidak lengkap', 5], ['Membolos', 15], ['Perkelahian', 30], ['Perundungan terbukti', 30], ['Merusak fasilitas', 15]] as [$n, $p]) {
            CreditCategory::updateOrCreate(['name' => $n], ['poin_pengurangan_default' => $p]);
        }

        $stop = 'yang dan di ke dari untuk dengan pada itu ini saya aku dia mereka kami kita ada tidak sudah telah akan juga atau karena sebagai dalam oleh adalah ia bisa hanya lebih sangat lagi saat ketika kalau jika tapi tetapi namun sama lalu kemudian setelah sebelum selalu sering sekali banyak para nya mau jadi agar supaya seperti antara masih belum bukan pun terus dua tiga hari kemarin tadi kok sih dong deh nih tuh aja saja udah gak nggak ga ya yg dgn sudah lagi';
        foreach (array_unique(explode(' ', $stop)) as $w) {
            KeywordStopword::firstOrCreate(['word' => $w], ['scope' => 'bawaan']);
        }

        // Placeholder versi model; diperbarui otomatis saat endpoint mengirim model_version.
        AiModelVersion::firstOrCreate(['versi' => 'belum-dipasang'], ['catatan' => 'Menunggu model .pkl dipasang di ai-service/models/.']);

        foreach ([
            'nama_sekolah' => 'SMA Negeri 1 Contoh',
            'tahun_ajaran' => Classroom::currentYear(),
            'anon_days' => '30',
            'retensi' => 'tahun_ajaran',
            'wajib_2fa' => '1',
            'ekstraksi_chat' => '1',
            'skor_baik' => '90',
            'skor_perhatian' => '70',
            'skor_peringatan' => '50',
            'skor_do' => '0',
        ] as $k => $v) {
            \App\Models\AppSetting::firstOrCreate(['key' => $k], ['value' => $v]);
        }
    }
}
