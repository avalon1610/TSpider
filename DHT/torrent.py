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

DB_name = 'test'
DB_user = 'root'
DB_passwd = '1qaz2wsx'
DB_host = '127.0.0.1'
# sys.stdout = os.fdopen(sys.stdout.fileno(), 'w', 0)

class torrent(basic.LineReceiver):
	from os import linesep as delimiter

	def connectionMade(self):
		self.transport.write('connection made!\n')
		reload(sys)
		sys.setdefaultencoding('utf-8')
		self.cp = adbapi.ConnectionPool('MySQLdb',db=DB_name,user=DB_user,passwd=DB_passwd,host=DB_host,charset="utf8")
		self.cp.runOperation("SET NAMES utf8");

	def dataReceived(self,data):
		self.transport.write('receive:%r\n'%data)
		self.getTorrent(data)

	def getTorrent(self,info_hash):
		filename = ''
		for i in info_hash:
			filename += ('%02X' % ord(i))
		if (len(filename) > 40):
			filename = filename[0:40]
		header = filename[0:2]
		tailer = filename[-2:]
		Hash = filename
		filename += '.torrent'

		def ParseTorrent(torrent):
			# to do
			self.transport.write('download torrent success\n')
			try:
				torrent_dict = bdecode(torrent)
			except:
				defer = getFromTorrage(filename)
				defer.addCallback(Decompress)
				defer.addErrback(getFromXunlei,(header,tailer,filename))
				return

			info = torrent_dict['info']

			if 'name.utf-8' in info.keys():
				name = info['name.utf-8']
			else:
				name = info['name']

			def calc_length(length):
				len_str = ''
				if length < 1024:
					len_str = '%d B' % length
				elif length >= 1024 and length < 1024**2:
					len_str = '%.2f KB' % (float(length)/float(1024))
				elif length >= 1024**2 and length < 1024**3:
					len_str = '%.2f MB' % (float(length)/float(1024**2))
				elif length >= 1024**3 and length < 1024**4:
					len_str = '%.2f GB' % (float(length)/float(1024**3))
				elif length >= 1024**4:
					len_str = '%.2f TB' % (float(length)/float(1024**4))
				return len_str

			description = ''
			if 'files' in info.keys():
				files = info['files']
				directory = ''
				path = []
				length = 0
				for f in files:
					if 'path.utf-8' in f.keys():
						path = f['path.utf-8']
					else:
						path = f['path']

					if 'length' in f.keys():
						length = f['length']	

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
						if l.find('.') != -1:
							description += '|%s' % calc_length(length)
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
				self.transport.write('insert [%s] to db success]\n' % filename)

			def printError(error):
				self.transport.write('ERROR:%r\n' % error)

			query_string = '''insert into test.dht (Hash,Name,Description,Rank) values ("%s","%s","%s",1) ON DUPLICATE KEY UPDATE Rank=Rank+1;''' % (
				Hash,name,description)
			self.transport.write('description length:%d\n' % len(description))
			self.cp.runOperation(query_string).addCallbacks(printResult,printError)

		def Decompress(result):
			torrent = zlib.decompress(result,16+zlib.MAX_WBITS)
			ParseTorrent(torrent)

		def getFromTorrage(filename):
			url = 'http://torrage.com/torrent/%s' % filename
			self.transport.write('start downloading from %s\n' % url)
			return getPage(url)

		def getFromXunlei(result,parameter):
			header,tailer,filename = parameter
			url = 'http://bt.box.n0808.com/%s/%s/%s' % (header,tailer,filename)
			self.transport.write('start downloading from %s\n' % url)
			getPage(url,filename).addCallbacks(
				ParseTorrent,
				lambda result:self.transport.write('download torrent failed\n'))

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