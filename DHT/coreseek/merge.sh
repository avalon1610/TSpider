#!/bin/sh
/usr/local/coreseek/bin/indexer -c /home/wwwroot/TSpider/DHT/coreseek/csft_mysql.conf --merge mysql delta --rotate --merge-dst-range deleted 0 0
