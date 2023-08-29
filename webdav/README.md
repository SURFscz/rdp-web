## Project

This project is based on [bytemark/webdav](https://github.com/BytemarkHosting/docker-webdav)

## Usage

### Basic WebDAV server

This example starts a WebDAV server on port 80.

```
docker build -t webdav .
docker run -rm -d --restart always \
    -v '/mnt/webdav':'/var/lib/dav/data':'rw' -v '/mnt/appdata':'/var/lib/dav':'rw' \
    -p '80:80' webdav
```

#### Via Docker Compose:

```
version: '3'
services:
  webdav:
    build: webdav
    restart: always
    ports:
      - "80:80"
    volumes:
      - /mnt/appdata:/var/lib/dav
      - /mnt/webdav:/var/lib/dav/data
```

### Environment variables

All environment variables are optional. You probably want to at least specify `USERNAME` and `PASSWORD` (or bind mount your own authentication file to `/user.passwd`) otherwise nobody will be able to access your WebDAV server!

- **`SERVER_NAME`**: Is set as the [ServerName](https://httpd.apache.org/docs/current/mod/core.html#servername). The default is `localhost`.
- **`LOCATION`**: The URL path for WebDAV (eg, if set to `/webdav` then clients should connect to `example.com/webdav`). The default is `/`.
- **`PUID`**: file owner's UID of `/var/lib/dav/data`
- **`PGID`**: file owner's GID of `/var/lib/dav/data`
- **`PUMASK`**: umask of `/var/lib/dav/data`
