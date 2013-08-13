#!/bin/env python
from bencode import *
from twisted.internet import protocol
from twisted.internet.protocol import DatagramProtocol,Factory
from twisted.internet import reactor
from twisted.internet.defer import Deferred
import string,random,struct,logging
import datetime,zlib,sys
import chardet
from twisted.web.client import getPage
from twisted.enterprise import adbapi
from twisted.internet import stdio
from twisted.protocols import basic
import sys, os

# sys.stdout = os.fdopen(sys.stdout.fileno(), 'w', 0)

class torrent(basic.LineReceiver):
	from os import linesep as delimiter

	def connectionMade(self):
		self.transport.write('connection made!>>>')
		reload(sys)
		sys.setdefaultencoding('utf-8')
		cp = adbapi.ConnectionPool('MySQLdb',db=DB_name,user=DB_user,passwd=DB_passwd,host=DB_host,charset="utf8")
		cp.runOperation("SET NAMES utf8");

	def dataReceived(self,data):
		self.transport.write(data)

	def getTorrent(self):
		filename = ''
		for i in info_hash:
			filename += ('%02X' % ord(i))
		header = filename[0:2]
		tailer = filename[-2:]
		Hash = filename
		filename += '.torrent'

		def ParseTorrent(torrent):
			# to do
			logging.info('download torrent success')
			torrent_dict = bdecode(torrent)
			info = torrent_dict['info']

			if 'name.utf-8' in info.keys():
				name = info['name.utf-8']
			else:
				name = info['name']

			description = ''
			if 'files' in info.keys():
				files = info['files']
				directory = ''
				path = []
				for f in files:
					if 'path.utf-8' in f.keys():
						path = f['path.utf-8']
					else:
						path = f['path']
					for l in path:
						if l.find('padding_file') != -1:
							continue
						if l.find('.') == -1:
							# this is a directory
							if (not directory) or (directory != l):
								directory = l
							else:
								continue
						else:
							description += ' - '

						description += l
						description += '\n'

			try:
				encoding = chardet.detect(name)['encoding']
		 		name = name.decode(encoding).encode('utf-8')
		 	except UnicodeError:
		 		name = name.decode('utf-8','replace').encode('utf-8')

		 	if description:
		 		try:
			 		encoding = chardet.detect(description)['encoding']
			 		description = description.decode(encoding).encode('utf-8')
			 	except UnicodeError:
			 		description = description.decode('utf-8','replace').encode('utf-8')

			def printResult(result):
				logging.info('insert [%s] to db success]' % name.decode('utf-8'))
				# reactor.stop()

			def printError(error):
				print error
				reactor.stop()

			query_string = '''insert into test.dht (Hash,Name,Description,Rank) values ("%s","%s","%s",1) ON DUPLICATE KEY UPDATE Rank=Rank+1;''' % (
				Hash,name,description)
			logging.info('description length:%d' % len(description))
			self.cp.runOperation(query_string).addCallbacks(printResult,printError)

		def Decompress(result):
			torrent = zlib.decompress(result,16+zlib.MAX_WBITS)
			ParseTorrent(torrent)

		def getFromTorrage(filename):
			url = 'http://torrage.com/torrent/%s' % filename
			logging.info('start downloading from %s' % url)
			return getPage(url)

		def getFromXunlei(result,parameter):
			header,tailer,filename = parameter
			url = 'http://bt.box.n0808.com/%s/%s/%s' % (header,tailer,filename)
			logging.info('start downloading from %s' % url)
			getPage(url,filename).addCallbacks(
				ParseTorrent,
				lambda result:logging.info('download torrent failed'))

		defer = getFromTorrage(filename)
		defer.addCallback(Decompress)
		defer.addErrback(getFromXunlei,(header,tailer,filename))
		running = True

def main():
	stdio.StandardIO(torrent())
	reactor.run()

if __name__ == "__main__":
    main()
    sys.exit(2)