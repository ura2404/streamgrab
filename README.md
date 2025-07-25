# Stream grab service

## 1. Edit main parameters
```bash
cp service/global.conf.src service/global.conf
nano service/global.conf
```

## 2.Camera config
```bash
cp service/camera.conf.src service/camera1.conf
nano service/camera1.conf
```

## 3. Prepare web service
Необходимые пакеты
- php-twig
- php-intl

```bash
cp www/config.json.src www/config.json
nano www/config.json``
ln -s `cat service/global.conf | grep 'HEAP_DIR' | cut -d'"' -f2` www/data

sudo ln -s $(pwd)/www /var/www/streamgrab
sudo cp os/etc/apache2/streamgrab.conf.src /etc/apache2/conf-available/streamgrab.conf
sudo nano /etc/apache2/streamgrab.conf
sudo a2enconf streamgrab
sudo systemctl reload apache2
```

## 4. Prepare service
```bash
cp os/etc/default/streamgrab.src os/etc/default/streamgrab
nano os/etc/default/streamgrab
sudo cp os/etc/default/streamgrab /etc/default

cp os/etc/systemd/streamgrab@.service.src os/etc/systemd/streamgrab@.service
nano os/etc/systemd/streamgrab@.service
sudo cp os/etc/systemd/streamgrab@.service /etc/systemd/system
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
```

## 5.Usage
```bash
sudo systemctl enable streamgrab@camera1
sudo systemctl start streamgrab@camera1
sudo systemctl status streamgrab@camera1
sudo journalctl -u streamgrab@camera1 -f
```
