# BruteForcePrevention
Bruteforce adalah serangan yang sulit dihadapi, karena layanan tetap harus dilakukan. Salah satunya adalah menginstalasi Fail2Ban yang merupakan salah satu Instrussion Prevention System yang bekerja terus-menerus memantau log file dari masing-masing aplikasi.

```
apt-get install python2.5
apt-get install fail2ban
tail /var/log/fail2ban.log
```
Anda akan menemui  ERROR Unexpected communication error, sehingga anda harus memperbaharui header fail2ban-server dari python ke python2.5
```
pico /usr/bin/fail2ban-server
  #!/usr/bin/python2.5
```
dan lakukan restart terhadap service fail2ban untuk memastikan tidak ada ERROR lagi
```
sudo service fail2bin restart
sudo service --status-all
tail /var/log/fail2ban.log
```
Kemudian lakukan konfigurasi pada file /etc/fail2ban/jail.conf, misalkan anda ingin  memantau brute force pada aplikasi ssh, vsftpd, sasl dan memblokir ipaddress penyerang selama 10 menit(bantime=600), dan mengirim email (action=%(action_mw)s) pemberitahuan ke you@email.com (destemail) jika terjadi 6 kali (maxretry) kesalahan dalam waktu 1 jam (findtime).
```
destemail = you@email.com
bantime = 600
findtime = 3600
action = %(action_m)s

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
# Hal yang harus diperhatikan
Berdasarkan pengujikan yang dilakukan, terutama pada SSH, ditemukan bahwa Fail2Ban tidak bekerja secara konsisten terkait dengan setting MaxRetry, hal ini disebabkan oleh Log file sering menggunakan format sebagai berikut:
```
Feb 17 13:23:38 [host] sshd[15498]: Failed password for root from xxx.xxx.xxx.xxx port 9498 ssh2
Feb 17 13:23:49 [host] sshd[15498]: message repeated 5 times: [ Failed password for root from xxx.xxx.xxx.xxx port 9498 ssh2]
```
Bagi Fail2ban, kegagalan login berdasarkan log tersebut diatas hanya dihitung sebagai kegagalan 1 kali, padahal secara prakteknya adalah 6 kali.
