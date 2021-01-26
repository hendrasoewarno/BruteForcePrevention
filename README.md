# Brute Force & NMAP Scan Prevention 
Bruteforce adalah serangan yang sulit dihadapi, karena layanan tetap harus disediakan bagi pemakai yang sah melalui authentication. Salah satunya upaya menghadapi Bruteforce adalah menginstalasi Fail2Ban yang bekerja sebagai Instrussion Prevention System (IPS) yang bekerja terus-menerus memantau log file dari masing-masing aplikasi dan menghitung jumlah pesan kegagalan authentication, jika batasan julah yang diperbolehkan dilanggar, maka apliaksi akan mengaktifkan iptables untuk memblokir koneksi untuk suatu masa jail yang ditetapkan.
```
apt-get install python2.5
apt-get install fail2ban
tail /var/log/fail2ban.log
```
Anda akan menemui  ERROR Unexpected communication error, karena Fail2Ban adalah membutuhkan python2.5, sehingga anda harus memperbaharui header fail2ban-server dari python ke python2.5
```
pico /usr/bin/fail2ban-server
  #!/usr/bin/python2.5
```
dan lakukan restart terhadap service fail2ban untuk memastikan tidak ada ERROR lagi
```
sudo service fail2ban restart
sudo service --status-all
tail /var/log/fail2ban.log
```
Kemudian lakukan konfigurasi pada file /etc/fail2ban/jail.local, misalkan anda ingin  memantau brute force pada aplikasi ssh, vsftpd, sasl dan memblokir ipaddress penyerang selama 10 menit(bantime=600), dan mengirim email (action=%(action_mw)s) pemberitahuan ke you@email.com (destemail) jika terjadi 6 kali (maxretry) kesalahan dalam waktu 1 jam (findtime).
```
destemail = you@email.com
bantime = 600
findtime = 3600
action = %(action_m)s

ignoreip = 127.0.0.1/8 your_home_IP

[ssh]

enabled = true
port    = ssh
filter  = sshd
logpath  = /var/log/auth.log
maxretry = 6

[vsftpd]

enabled  = true
port     = ftp,ftp-data,ftps,ftps-data
filter   = vsftpd
logpath  = /var/log/vsftpd.log
# or overwrite it in jails.local to be
# logpath = /var/log/auth.log
# if you want to rely on PAM failed login attempts
# vsftpd's failregex should match both of those formats
maxretry = 6

[sasl]

enabled  = true
port     = smtp,ssmtp,imap2,imap3,imaps,pop3,pop3s
filter   = sasl
logpath  = /var/log/mail.log
```
Langkah selanjutnya adalah memeriksa kembali kesesuaian regex untuk masing-masing aplikasi yang dipantau. Script untuk masing-masing aplikasi dapat diperiksa pada /etc/fail2ban/filter.d apakah sudah sesuai dengan pesan kesalahan jika terjadi kegagalan authentication untuk aplikasi yang dimaksud, dan jangan lupa restart service fail2ban setiap anda melakukan perubahan setting.
```
service fail2ban stop
service fail2ban start
tail /var/log/fail2ban.log
```
Pastikan Intrussion Pervention System pemantauan Jail 'vsftpd' started, Jail 'ssh' started dan Jail 'sasl' started
## Menampilkan IP yang di ban
Berikut ini adalah perintah firewall untuk menampilkan alamat IP yang di-banned karena kesalahan login pada SSH.
```
sudo iptables -L fail2ban-ssh -v -n
```
# Hal yang harus diperhatikan
Berdasarkan pengujikan yang dilakukan, terutama pada SSH, ditemukan bahwa Fail2Ban tidak bekerja secara konsisten terkait dengan setting MaxRetry, hal ini disebabkan oleh Log file sering menggunakan format sebagai berikut:
```
Feb 17 13:23:38 [host] sshd[15498]: Failed password for root from xxx.xxx.xxx.xxx port 9498 ssh2
Feb 17 13:23:49 [host] sshd[15498]: message repeated 5 times: [ Failed password for root from xxx.xxx.xxx.xxx port 9498 ssh2]
```
Bagi Fail2ban, kegagalan login berdasarkan log tersebut diatas hanya dihitung sebagai kegagalan 1 kali, padahal secara prakteknya adalah 6 kali.
# Keamanan pada Apache
Fail2ban juga dapat digunakan untuk meningkatkan keamanan pada Apache2 terkait dengan kesalahan basic authentication
```
[apache]

enabled  = true
port     = http,https
filter   = apache-auth
logpath  = /var/log/apache*/*error.log
maxretry = 10
findtime = 600
```
Anda juga dapat mengaktifkan pemantauan terhadap badbots
```
[apache-badbots]

enabled  = true
port     = http,https
filter   = apache-badbots
logpath  = /var/log/apache*/*error.log
maxretry = 2
```
# Custom Script untuk 404 Not Found
Kadang-kadang serangan scan bruteforce terhadap Web akan menimbulkan banyak error 404, yang dapat ditangani dengan menambahkan section pada /etc/fail2ban/jail.conf
```
[apache-404]
enabled = true
port = http,https
filter = apache-404
logpath = /var/log/apache*/*access.log
bantime = 3600
findtime = 600
maxretry = 5
```
dan menambahkan filter berikut pada .etc/fail2ban/filter.d
```
pico /etc/fail2ban/filter.d/apache-404.conf
    [INCLUDES]

    before = apache-404.conf

    [Definition]

    failregex = ^<HOST> - .* "(GET|POST|HEAD).*HTTP.*" 404 .*$
    ignoreregex =.*(robots.txt|favicon.ico|jpg|png)
```
# Custom Script untuk NMAP Scan
Tambahkan script berikut ini pada /etc/rc.local
```
pico /etc/rc.local
  iptables -N suspect_nmap_scan
  iptables -A suspect_nmap_scan -j LOG --log-prefix "Suspected nmap scanner: "

  iptables -A OUTPUT -p tcp --tcp-flags RST,ACK RST,ACK -j suspect_nmap_scan
```
Tambahkan section berikut ini pada /etc/fail2ban/jail.conf
```
[nmap-scan]

enabled = true
banaction = iptables-allports
port = anyport
filter = nmap-scan
logpath = /var/log/kern.log
bantime = 3600
findtime = 5
maxretry = 10
```
dan menambahkan filter 
```
pico /etc/fail2ban/filter.d/nmap-scan.conf
  [INCLUDES]

  before = nmap-scan.conf

  [Definition]

  failregex = .* Suspected nmap scanner: .* DST=<HOST> .*
  ignoreregex =
```
Perintah tersebut akan mengaktifkan iptables untuk merekam setiap upaya klien yang koneksi ke port yang Close pada server yang dibalas dengan output packet dengan flags RST,ACK. Kegagalan koneksi tersebut akan direkam pada /var/log/kern.log. Selanjutnya adalah membuat script untuk memantau jumlah RST,ACK untuk satu satuan waktu agar dianggap sebagai upaya scan port.
# Debug fail2ban
Berinit ini adalah perintah untuk menjalankan fail2ban secara debug jika ada script atau setting yang salah:
```
fail2ban-client -v -v -v start
```
# Kesimpulan
Fail2Ban adalah Intrussion Prevention System yang bekerja dengan cara memantau dan menghitung jumlah kegagalan authentication per-satuan waktu, jika jumlah kegagalan melampaui batasan yang telah ditetapkan, maka Fail2Ban akan mengaktifkan pemblokiran(jail) terhadap alamat IP untuk suatu jangka waktu tertentu. Salah satu kelemahan dari Fail2Ban adalah tidak mampu menghitung jumlah kegagalan authentication (Failed password for) yang diikuti dengan pesan message repeated 5 times. Sehingga menjadi suatu fenomena yang perlu dicermati.
