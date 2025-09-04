## Overview
Aerones Downloader is a high-performance file downloader built with Laravel 12, PHP 8.2, ReactPHP, and PostgreSQL

The project is containerized with Lando, making it easy to set up and review.


## Quick Setup Instructions
### Install Lando

1. If you haven't installed Lando yet, follow these steps:
   
##### macOS
```
brew install --cask lando
```

##### Linux
```
curl -fsSL https://github.com/lando/lando/releases/latest/download/lando-x64.deb -o lando.deb
sudo dpkg -i lando.deb
```

##### Windows
Download and install Lando from Lando's official [site](https://docs.lando.dev/install/)

2. Start Lando Environment
```
lando start
```

### Lando additional information
```
lando list
lando info
```

### Lando stop and destroy container
```
lando stop
lando destroy
```

## How to test
Run the command from the console

```
lando artisan migrate:fresh    
lando artisan db:seed
lando artisan downloads:run
```
