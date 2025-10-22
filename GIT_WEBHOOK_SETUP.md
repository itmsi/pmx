# Git Webhook Integration

Sistem ini mendukung integrasi dengan Git providers (GitHub, GitLab, Bitbucket) untuk melacak commit yang terkait dengan ticket.

## Setup Webhook

### 1. Webhook URL
```
POST {{APP_URL}}/api/git/webhook
```

### 2. Konfigurasi GitHub
1. Pergi ke repository settings
2. Pilih "Webhooks" di sidebar
3. Klik "Add webhook"
4. Masukkan URL webhook: `https://your-domain.com/api/git/webhook`
5. Pilih "Just the push event"
6. Klik "Add webhook"

### 3. Konfigurasi GitLab
1. Pergi ke project settings
2. Pilih "Webhooks" di sidebar
3. Masukkan URL webhook: `https://your-domain.com/api/git/webhook`
4. Pilih "Push events"
5. Klik "Add webhook"

### 4. Konfigurasi Bitbucket
1. Pergi ke repository settings
2. Pilih "Webhooks" di sidebar
3. Klik "Add webhook"
4. Masukkan URL webhook: `https://your-domain.com/api/git/webhook`
5. Pilih "Repository push"
6. Klik "Save"

## Format Commit Message

Untuk melacak commit ke ticket tertentu, gunakan format berikut dalam commit message:

### Format yang Didukung:
- `#123` - Mengacu ke ticket ID 123
- `TICKET-123` - Mengacu ke ticket ID 123
- `T-123` - Mengacu ke ticket ID 123
- `TICKET123` - Mengacu ke ticket ID 123

### Contoh Commit Message:
```bash
git commit -m "Fix bug #123 - resolve authentication issue"
git commit -m "Update feature TICKET-456 - add new dashboard"
git commit -m "Refactor T-789 - improve code structure"
```

## Data yang Disimpan

Setiap commit yang terkait dengan ticket akan menyimpan:
- Ticket ID
- User yang melakukan commit (jika ditemukan berdasarkan email)
- Branch name
- Commit message
- Commit hash
- Timestamp push
- Repository name dan URL

## API Endpoints

### Webhook Endpoint
```
POST /api/git/webhook
```
Menerima payload dari Git providers dan memproses commit.

### Get Ticket Git History
```
GET /api/git/ticket/{ticketId}/history
```
Mengembalikan git history untuk ticket tertentu.

## Troubleshooting

### Commit Tidak Muncul
1. Pastikan commit message mengandung ticket ID dengan format yang benar
2. Cek log aplikasi untuk error webhook
3. Pastikan webhook URL dapat diakses dari Git provider

### User Tidak Terdeteksi
1. Pastikan email di Git provider sama dengan email di sistem
2. Sistem akan mencoba mencocokkan berdasarkan email terlebih dahulu
3. Jika tidak ditemukan, akan menggunakan name sebagai fallback

### Webhook Error
1. Cek log Laravel di `storage/logs/laravel.log`
2. Pastikan webhook URL benar dan dapat diakses
3. Cek konfigurasi Git provider webhook
