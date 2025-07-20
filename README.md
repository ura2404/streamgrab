## 1. Edit main parameters
```bash
nano global.conf
```

## 2.Camera config

Create and edit camera config file

```bash
cp camera.conf.src camera1.conf`
nano camera1.conf
```

## 3. Unit file
```bash
cp streamgrab@.service.src streamgrab@.service
nano streamgrab@.service.src
sudo mv streamgrab@.service /etc/systemd/system/
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
```

## 4.Usage
```bash
sudo systemctl enable streamgrab@camera1
sudo systemctl start streamgrab@camera1
sudo systemctl status streamgrab@camera1
sudo journalctl -u streamgrab@camera1 -f
```
