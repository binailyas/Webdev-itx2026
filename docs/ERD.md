# ERD — Sistem Layanan Terpadu BK Sekolah

> Dihasilkan otomatis dari skema database (`php artisan docs:erd`) — 30 tabel aplikasi. Tabel bawaan framework (`cache`, `jobs`, `sessions`, dst.) tidak digambar.

Legenda: **PK** primary key · **FK** foreign key · **UK** unique. Relasi `||--o{` = satu-ke-banyak, `}o--o|` = banyak-ke-satu opsional.

## Gambaran menyeluruh (relasi, tanpa kolom)

```mermaid
erDiagram
    ai_model_versions
    ai_overrides
    announcements
    anonymous_accounts
    app_settings
    audit_logs
    chat_messages
    chat_participants
    chat_rooms
    classrooms
    credit_categories
    credit_records
    incident_categories
    incident_reports
    keyword_aliases
    keyword_daily_stats
    keyword_stopwords
    notifications
    report_attachments
    report_entities
    report_keywords
    report_notes
    report_status_histories
    role_locks
    role_permissions
    roles
    student_profiles
    users
    wali_kelas_assignments
    watchlist_terms
    incident_reports ||--o{ ai_overrides : "report_id"
    users ||--o{ ai_overrides : "user_id"
    users ||--o{ announcements : "user_id"
    users |o--o{ audit_logs : "user_id"
    chat_rooms ||--o{ chat_messages : "chat_room_id"
    anonymous_accounts |o--o{ chat_messages : "sender_anon_id"
    users |o--o{ chat_messages : "sender_user_id"
    anonymous_accounts |o--o{ chat_participants : "anon_id"
    chat_rooms ||--o{ chat_participants : "chat_room_id"
    users |o--o{ chat_participants : "user_id"
    users |o--o{ chat_rooms : "career_user_id"
    users |o--o{ chat_rooms : "created_by"
    incident_reports |o--o{ chat_rooms : "report_id"
    credit_categories ||--o{ credit_records : "category_id"
    incident_reports |o--o{ credit_records : "report_id"
    users ||--o{ credit_records : "student_id"
    users ||--o{ credit_records : "user_id_pencatat"
    users |o--o{ credit_records : "voided_by"
    ai_model_versions |o--o{ incident_reports : "ai_model_version_id"
    users |o--o{ incident_reports : "assigned_to"
    incident_categories ||--o{ incident_reports : "category_id"
    anonymous_accounts |o--o{ incident_reports : "reporter_anon_id"
    users |o--o{ incident_reports : "reporter_user_id"
    users |o--o{ keyword_aliases : "dibuat_oleh"
    users |o--o{ keyword_aliases : "student_user_id"
    incident_categories |o--o{ keyword_daily_stats : "category_id"
    anonymous_accounts |o--o{ notifications : "anon_id"
    users |o--o{ notifications : "user_id"
    incident_reports ||--o{ report_attachments : "report_id"
    users |o--o{ report_entities : "confirmed_by"
    users |o--o{ report_entities : "kandidat_user_id"
    incident_reports ||--o{ report_entities : "report_id"
    users |o--o{ report_entities : "user_id_terkait"
    incident_reports ||--o{ report_keywords : "report_id"
    incident_reports ||--o{ report_notes : "report_id"
    users ||--o{ report_notes : "user_id"
    incident_reports ||--o{ report_status_histories : "report_id"
    users |o--o{ report_status_histories : "user_id"
    users |o--o{ role_locks : "locked_by"
    classrooms |o--o{ student_profiles : "classroom_id"
    users ||--o{ student_profiles : "user_id"
    roles |o--o{ users : "role_id"
    classrooms ||--o{ wali_kelas_assignments : "classroom_id"
    users ||--o{ wali_kelas_assignments : "user_id"
    users ||--o{ watchlist_terms : "user_id"
```

## Domain 1 — Pengguna & Autentikasi

```mermaid
erDiagram
    roles {
        bigint id PK
        string name UK
        string label
        string guard_name
        timestamp created_at
        timestamp updated_at
    }
    users {
        bigint id PK
        bigint role_id FK
        string name
        string email UK
        timestamp email_verified_at
        string password
        tinyint is_active
        tinyint two_factor_enabled
        timestamp last_login_at
        string remember_token
        timestamp created_at
        timestamp updated_at
    }
    student_profiles {
        bigint id PK
        bigint user_id FK
        string nis UK
        bigint classroom_id FK
        string angkatan
        string tahun_ajaran
        int skor_awal
        timestamp created_at
        timestamp updated_at
    }
    classrooms {
        bigint id PK
        string nama_kelas
        string tingkat
        string tahun_ajaran
        timestamp created_at
        timestamp updated_at
    }
    wali_kelas_assignments {
        bigint id PK
        bigint user_id FK
        bigint classroom_id FK
        string tahun_ajaran
        timestamp created_at
    }
    anonymous_accounts {
        bigint id PK
        string alias UK
        string password_hash
        string remember_token
        timestamp expires_at
        timestamp last_activity_at
        timestamp created_at
    }
    classrooms |o--o{ student_profiles : "classroom_id"
    users ||--o{ student_profiles : "user_id"
    roles |o--o{ users : "role_id"
    classrooms ||--o{ wali_kelas_assignments : "classroom_id"
    users ||--o{ wali_kelas_assignments : "user_id"
```

## Domain 2 — Laporan Insiden

```mermaid
erDiagram
    incident_categories {
        bigint id PK
        string name
        text description
        tinyint is_active
        int urutan
        timestamp created_at
        timestamp updated_at
    }
    incident_reports {
        bigint id PK
        string ticket_code UK
        string pin_hash
        bigint category_id FK
        bigint reporter_user_id FK
        bigint reporter_anon_id FK
        string judul
        text kronologi
        date tanggal_kejadian
        string lokasi
        text pihak_terlibat
        enum prioritas
        string prioritas_siswa
        tinyint risk_flagged
        enum status
        bigint assigned_to FK
        enum ai_priority_suggestion
        double ai_priority_confidence
        tinyint ai_flagged
        bigint ai_model_version_id FK
        timestamp opened_at
        timestamp selesai_at
        timestamp arsip_notified_at
        timestamp archived_at
        timestamp created_at
        timestamp updated_at
    }
    report_attachments {
        bigint id PK
        bigint report_id FK
        string file_path
        string file_name
        string mime_type
        int file_size
        timestamp created_at
        timestamp updated_at
    }
    report_status_histories {
        bigint id PK
        bigint report_id FK
        bigint user_id FK
        string status_from
        string status_to
        text alasan
        timestamp created_at
        timestamp updated_at
    }
    report_notes {
        bigint id PK
        bigint report_id FK
        bigint user_id FK
        text isi
        tinyint penting
        timestamp created_at
        timestamp updated_at
    }
    report_keywords {
        bigint id PK
        bigint report_id FK
        string keyword
        tinyint n
        int frekuensi
        enum source
        timestamp created_at
        timestamp updated_at
    }
    report_entities {
        bigint id PK
        bigint report_id FK
        string nama_entitas
        text konteks
        enum jenis_entitas
        bigint user_id_terkait FK
        bigint kandidat_user_id FK
        enum status
        tinyint dikonfirmasi
        bigint confirmed_by FK
        timestamp confirmed_at
        timestamp created_at
        timestamp updated_at
    }
    users {
        bigint id PK
    }
    anonymous_accounts {
        bigint id PK
    }
    users |o--o{ incident_reports : "assigned_to"
    incident_categories ||--o{ incident_reports : "category_id"
    anonymous_accounts |o--o{ incident_reports : "reporter_anon_id"
    users |o--o{ incident_reports : "reporter_user_id"
    incident_reports ||--o{ report_attachments : "report_id"
    users |o--o{ report_entities : "confirmed_by"
    users |o--o{ report_entities : "kandidat_user_id"
    incident_reports ||--o{ report_entities : "report_id"
    users |o--o{ report_entities : "user_id_terkait"
    incident_reports ||--o{ report_keywords : "report_id"
    incident_reports ||--o{ report_notes : "report_id"
    users ||--o{ report_notes : "user_id"
    incident_reports ||--o{ report_status_histories : "report_id"
    users |o--o{ report_status_histories : "user_id"
```

## Domain 3 — AI Klasifikasi Prioritas

```mermaid
erDiagram
    ai_model_versions {
        bigint id PK
        string versi
        timestamp deployed_at
        double f1_score
        double precision_score
        double recall_score
        text catatan
        timestamp created_at
        timestamp updated_at
    }
    ai_overrides {
        bigint id PK
        bigint report_id FK
        bigint user_id FK
        enum ai_suggestion
        enum priority_set
        text alasan
        timestamp created_at
        timestamp updated_at
    }
    incident_reports {
        bigint id PK
    }
    users {
        bigint id PK
    }
    incident_reports ||--o{ ai_overrides : "report_id"
    users ||--o{ ai_overrides : "user_id"
    ai_model_versions |o--o{ incident_reports : "ai_model_version_id"
    users |o--o{ incident_reports : "assigned_to"
    users |o--o{ incident_reports : "reporter_user_id"
```

## Domain 4 — Chat

```mermaid
erDiagram
    chat_rooms {
        bigint id PK
        enum type
        bigint report_id FK
        bigint career_user_id FK
        string topik
        enum status
        text ringkasan
        bigint created_by FK
        tinyint is_readonly
        tinyint wk_diizinkan
        timestamp closed_at
        timestamp created_at
        timestamp updated_at
    }
    chat_participants {
        bigint id PK
        bigint chat_room_id FK
        bigint user_id FK
        bigint anon_id FK
        string role
        tinyint can_write
        timestamp created_at
        timestamp updated_at
    }
    chat_messages {
        bigint id PK
        bigint chat_room_id FK
        bigint sender_user_id FK
        bigint sender_anon_id FK
        text isi
        tinyint is_read
        timestamp created_at
        timestamp updated_at
    }
    incident_reports {
        bigint id PK
    }
    users {
        bigint id PK
    }
    anonymous_accounts {
        bigint id PK
    }
    chat_rooms ||--o{ chat_messages : "chat_room_id"
    anonymous_accounts |o--o{ chat_messages : "sender_anon_id"
    users |o--o{ chat_messages : "sender_user_id"
    anonymous_accounts |o--o{ chat_participants : "anon_id"
    chat_rooms ||--o{ chat_participants : "chat_room_id"
    users |o--o{ chat_participants : "user_id"
    users |o--o{ chat_rooms : "career_user_id"
    users |o--o{ chat_rooms : "created_by"
    incident_reports |o--o{ chat_rooms : "report_id"
    users |o--o{ incident_reports : "assigned_to"
    anonymous_accounts |o--o{ incident_reports : "reporter_anon_id"
    users |o--o{ incident_reports : "reporter_user_id"
```

## Domain 5 — Analitik Kata Kunci

```mermaid
erDiagram
    keyword_aliases {
        bigint id PK
        string alias
        string canonical
        bigint student_user_id FK
        bigint dibuat_oleh FK
        timestamp created_at
        timestamp updated_at
    }
    keyword_stopwords {
        bigint id PK
        string word UK
        enum scope
        timestamp created_at
        timestamp updated_at
    }
    keyword_daily_stats {
        bigint id PK
        string keyword
        date tanggal
        bigint category_id FK
        int frekuensi
        timestamp created_at
        timestamp updated_at
    }
    watchlist_terms {
        bigint id PK
        string term
        text catatan
        bigint user_id FK
        int ambang
        tinyint notifikasi
        timestamp last_alerted_at
        timestamp created_at
        timestamp updated_at
    }
    users {
        bigint id PK
    }
    incident_categories {
        bigint id PK
    }
    users |o--o{ keyword_aliases : "dibuat_oleh"
    users |o--o{ keyword_aliases : "student_user_id"
    incident_categories |o--o{ keyword_daily_stats : "category_id"
    users ||--o{ watchlist_terms : "user_id"
```

## Domain 6 — Skor Kredit, Konten & Sistem

```mermaid
erDiagram
    credit_categories {
        bigint id PK
        string name
        int poin_pengurangan_default
        tinyint is_active
        timestamp created_at
        timestamp updated_at
    }
    credit_records {
        bigint id PK
        bigint student_id FK
        bigint user_id_pencatat FK
        bigint report_id FK
        bigint category_id FK
        int poin_dikurangi
        text alasan
        date tanggal
        string tahun_ajaran
        timestamp voided_at
        bigint voided_by FK
        text void_reason
        timestamp created_at
        timestamp updated_at
    }
    announcements {
        bigint id PK
        bigint user_id FK
        string judul
        string slug UK
        string kategori
        text isi
        string image_path
        enum status
        timestamp published_at
        timestamp created_at
        timestamp updated_at
    }
    notifications {
        bigint id PK
        bigint user_id FK
        bigint anon_id FK
        string type
        json data
        timestamp read_at
        timestamp created_at
        timestamp updated_at
    }
    audit_logs {
        bigint id PK
        bigint user_id FK
        string action
        string entity_type
        bigint entity_id
        json data
        timestamp created_at
    }
    app_settings {
        string key
        text value
        timestamp created_at
        timestamp updated_at
    }
    users {
        bigint id PK
    }
    incident_reports {
        bigint id PK
    }
    anonymous_accounts {
        bigint id PK
    }
    users ||--o{ announcements : "user_id"
    users |o--o{ audit_logs : "user_id"
    credit_categories ||--o{ credit_records : "category_id"
    incident_reports |o--o{ credit_records : "report_id"
    users ||--o{ credit_records : "student_id"
    users ||--o{ credit_records : "user_id_pencatat"
    users |o--o{ credit_records : "voided_by"
    users |o--o{ incident_reports : "assigned_to"
    anonymous_accounts |o--o{ incident_reports : "reporter_anon_id"
    users |o--o{ incident_reports : "reporter_user_id"
    anonymous_accounts |o--o{ notifications : "anon_id"
    users |o--o{ notifications : "user_id"
```

## Kamus tabel

### `ai_model_versions`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| versi | string |  |  |
| deployed_at | timestamp |  | ya |
| f1_score | double |  | ya |
| precision_score | double |  | ya |
| recall_score | double |  | ya |
| catatan | text |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `ai_overrides`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| report_id | bigint | FK |  |
| user_id | bigint | FK |  |
| ai_suggestion | enum |  | ya |
| priority_set | enum |  |  |
| alasan | text |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `announcements`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| user_id | bigint | FK |  |
| judul | string |  |  |
| slug | string | UK |  |
| kategori | string |  |  |
| isi | text |  |  |
| image_path | string |  | ya |
| status | enum |  |  |
| published_at | timestamp |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `anonymous_accounts`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| alias | string | UK |  |
| password_hash | string |  |  |
| remember_token | string |  | ya |
| expires_at | timestamp |  |  |
| last_activity_at | timestamp |  | ya |
| created_at | timestamp |  | ya |

### `app_settings`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| key | string |  |  |
| value | text |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `audit_logs`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| user_id | bigint | FK | ya |
| action | string |  |  |
| entity_type | string |  | ya |
| entity_id | bigint |  | ya |
| data | json |  | ya |
| created_at | timestamp |  | ya |

### `chat_messages`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| chat_room_id | bigint | FK |  |
| sender_user_id | bigint | FK | ya |
| sender_anon_id | bigint | FK | ya |
| isi | text |  |  |
| is_read | tinyint |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `chat_participants`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| chat_room_id | bigint | FK |  |
| user_id | bigint | FK | ya |
| anon_id | bigint | FK | ya |
| role | string |  |  |
| can_write | tinyint |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `chat_rooms`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| type | enum |  |  |
| report_id | bigint | FK | ya |
| career_user_id | bigint | FK | ya |
| topik | string |  | ya |
| status | enum |  |  |
| ringkasan | text |  | ya |
| created_by | bigint | FK | ya |
| is_readonly | tinyint |  |  |
| wk_diizinkan | tinyint |  |  |
| closed_at | timestamp |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `classrooms`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| nama_kelas | string |  |  |
| tingkat | string |  |  |
| tahun_ajaran | string |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `credit_categories`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| name | string |  |  |
| poin_pengurangan_default | int |  |  |
| is_active | tinyint |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `credit_records`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| student_id | bigint | FK |  |
| user_id_pencatat | bigint | FK |  |
| report_id | bigint | FK | ya |
| category_id | bigint | FK |  |
| poin_dikurangi | int |  |  |
| alasan | text |  |  |
| tanggal | date |  | ya |
| tahun_ajaran | string |  |  |
| voided_at | timestamp |  | ya |
| voided_by | bigint | FK | ya |
| void_reason | text |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `incident_categories`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| name | string |  |  |
| description | text |  | ya |
| is_active | tinyint |  |  |
| urutan | int |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `incident_reports`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| ticket_code | string | UK |  |
| pin_hash | string |  | ya |
| category_id | bigint | FK |  |
| reporter_user_id | bigint | FK | ya |
| reporter_anon_id | bigint | FK | ya |
| judul | string |  |  |
| kronologi | text |  |  |
| tanggal_kejadian | date |  | ya |
| lokasi | string |  | ya |
| pihak_terlibat | text |  | ya |
| prioritas | enum |  |  |
| prioritas_siswa | string |  |  |
| risk_flagged | tinyint |  |  |
| status | enum |  |  |
| assigned_to | bigint | FK | ya |
| ai_priority_suggestion | enum |  | ya |
| ai_priority_confidence | double |  | ya |
| ai_flagged | tinyint |  |  |
| ai_model_version_id | bigint | FK | ya |
| opened_at | timestamp |  | ya |
| selesai_at | timestamp |  | ya |
| arsip_notified_at | timestamp |  | ya |
| archived_at | timestamp |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `keyword_aliases`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| alias | string |  |  |
| canonical | string |  |  |
| student_user_id | bigint | FK | ya |
| dibuat_oleh | bigint | FK | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `keyword_daily_stats`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| keyword | string |  |  |
| tanggal | date |  |  |
| category_id | bigint | FK | ya |
| frekuensi | int |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `keyword_stopwords`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| word | string | UK |  |
| scope | enum |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `notifications`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| user_id | bigint | FK | ya |
| anon_id | bigint | FK | ya |
| type | string |  |  |
| data | json |  | ya |
| read_at | timestamp |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `report_attachments`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| report_id | bigint | FK |  |
| file_path | string |  |  |
| file_name | string |  |  |
| mime_type | string |  | ya |
| file_size | int |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `report_entities`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| report_id | bigint | FK |  |
| nama_entitas | string |  |  |
| konteks | text |  | ya |
| jenis_entitas | enum |  | ya |
| user_id_terkait | bigint | FK | ya |
| kandidat_user_id | bigint | FK | ya |
| status | enum |  |  |
| dikonfirmasi | tinyint |  |  |
| confirmed_by | bigint | FK | ya |
| confirmed_at | timestamp |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `report_keywords`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| report_id | bigint | FK |  |
| keyword | string |  |  |
| n | tinyint |  |  |
| frekuensi | int |  |  |
| source | enum |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `report_notes`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| report_id | bigint | FK |  |
| user_id | bigint | FK |  |
| isi | text |  |  |
| penting | tinyint |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `report_status_histories`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| report_id | bigint | FK |  |
| user_id | bigint | FK | ya |
| status_from | string |  | ya |
| status_to | string |  |  |
| alasan | text |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `role_locks`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| role | string |  |  |
| locked_by | bigint | FK | ya |
| locked_at | timestamp |  | ya |

### `role_permissions`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| role | string |  |  |
| feature | string |  |  |
| value | string |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `roles`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| name | string | UK |  |
| label | string |  |  |
| guard_name | string |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `student_profiles`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| user_id | bigint | FK |  |
| nis | string | UK |  |
| classroom_id | bigint | FK | ya |
| angkatan | string |  | ya |
| tahun_ajaran | string |  | ya |
| skor_awal | int |  |  |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `users`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| role_id | bigint | FK | ya |
| name | string |  |  |
| email | string | UK |  |
| email_verified_at | timestamp |  | ya |
| password | string |  |  |
| is_active | tinyint |  |  |
| two_factor_enabled | tinyint |  |  |
| last_login_at | timestamp |  | ya |
| remember_token | string |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

### `wali_kelas_assignments`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| user_id | bigint | FK |  |
| classroom_id | bigint | FK |  |
| tahun_ajaran | string |  |  |
| created_at | timestamp |  | ya |

### `watchlist_terms`

| Kolom | Tipe | Kunci | Null |
|---|---|---|---|
| id | bigint | PK |  |
| term | string |  |  |
| catatan | text |  | ya |
| user_id | bigint | FK |  |
| ambang | int |  |  |
| notifikasi | tinyint |  |  |
| last_alerted_at | timestamp |  | ya |
| created_at | timestamp |  | ya |
| updated_at | timestamp |  | ya |

