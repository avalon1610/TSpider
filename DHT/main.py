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

GOOD = 'good'
DUBIOUS = 'dubious'
BAD = 'bad'
 
class DHTQuery():
	''' t : transaction ID
		y : type of message (ping,find_node,get_peer,announce_peer)
		q : query
		r : response, a dictionary containing named return values
		e : error
			Code	Description
			201		Generic Error
			202		Server Error
			203		Protocol Error, such as a malformed packet, invalid arguments, or bad token
			204		Method Unknown
		a : dictionary value containing named arguments to the query.
	'''
 
	def encode(self,t,y,q=None,r=None,e=None,a=None):
		if not t or not y:
			return None
		msg = {'t': t,'y': y}
		if q:
			msg['q'] = q
			if a:
				msg['a'] = a
		elif r:
			msg['r'] = r
		else:
			return None

		if r:
			logging.debug(msg)
		return bencode(msg)
 
	def decode(self,msg):
		result = ''
		try:
			result = bdecode(msg)
		except:
			logging.warning('not a valid bencoded string')

		return result
 
class DHTNodeProtocol(DatagramProtocol):
	def __init__(self,service):
		# self.d = deferred;
		self.query = DHTQuery()
		self.service = service
		
	def datagramReceived(self, data, (host, port)):
		logging.debug("received %r from %s:%d" % (data, host, port))
		# self.d.callback(self.query.decode(data))
		self.service.ProcessData(self.query.decode(data),host,port,self.my_port)
 
	def sendDatagram(self,data,host,port):
		logging.debug('Data:%r' % data)
		self.transport.write(data,(host,port))

	def startProtocol(self):
		self.my_port = self.transport.getHost().port
 
class Nodes():
	def __init__(self,NodeID,IP,Port):
		self.NodeID = NodeID
		self.IP = IP
		self.Port = Port
		self.search_time = 1
		self.healthy = GOOD
		self.update_time = datetime.datetime.now()
 
	def __str__(self):
		return 'NodeID:%r\nIP:%r\nPort:%r\n' % (self.NodeID,self.IP,self.Port)

class TorrentClient(protocol.Protocol):
	def connectionMade(self):
		logging.info('success connect to the peer:%s:%s' % (self.factory.host,self.factory.port))
		data = self.factory.handshake()
		self.transport.write(data)
		data = self.factory.ext_handshake()
		self.transport.write(data)

	def dataReceived(self,data):
		logging.info('Peer receive:%r' % data)
		# '\x13BitTorrent protocolex\x00\x00\x00\x10\x00\x01e\x7fK\xb3M\x9e\xcaA\xc
		# 1\xdf8\xb9w\x0e\xda\xe4\x13\x18EQ-SD0100-\xb0\xd4\x88#m76\xc8@\xa1M\x9e'
		if data.find('BitTorrent protocol') != -1:
			pass
		else:
			logging.info('decode:%r' % bdecode(data))
			data = self.factory.start_req_metainfo()
			self.transport.write(data)
			self.transport.loseConnection()	

	def connectionLost(self,reason):
		logging.warning('connection lost:%s' % reason)
		if reactor.running:
			reactor.stop()

class TorrentClientFactory(protocol.ClientFactory):
	protocol = TorrentClient
	UT_METADATA_MSGID = 1

	def __init__(self,hash_info,peer_id,address):
		self.host,self.port = address
		self.hash_info = hash_info
		self.peer_id = peer_id

	def handshake(self):
		reserved = struct.pack('!BBBBBBBB',0,0,0,0,0,0x10,0,0)
		protocol_name = 'BitTorrent protocol'
		msg = struct.pack('!B19s8s20s20s',19,protocol_name,reserved,self.hash_info,self.peer_id)
		logging.info('send handshake:%r' % msg)
		# self.protocol.transport.write(msg)
		return msg

	def encode_ext_msg(self,MsgID,Dict):
		Body = bencode(Dict)
		Len = len(Body) + 2
		msg =  struct.pack('!LBB',Len,0x20,MsgID)
		msg += Body
		return msg

	def ext_handshake(self):
		MetaMsgID = self.UT_METADATA_MSGID
		Dict = {'m':('ut_metadata',MetaMsgID)}
		msg = self.encode_ext_msg(0,Dict)
		logging.info('send ext_handshake:%r' % msg)
		return msg

	def start_req_metainfo(self,MsgID):
		Dict = bencode({'msg_type': 0, 'piece': 0})
		msg = self.encode_ext_msg(MsgID,Dict)
		logging.info('start_req_metainfo:%r' % msg)
		return msg

	def clientConnectionFailed(self, connector, reason):
		logging.info('connection failed:%s' % reason.getErrorMessage())

	def clientConnectionLost(self, connector, reason):
		logging.info('connection lost:%s' % reason.getErrorMessage())
		if reactor.running:
			reactor.stop()
 
class NodeService(object):
	router = {}
	protocol_list = {}
	def __init__(self,host,port):
		self.begin_host = host
		self.begin_port = port
		self.query = DHTQuery()

		self.tp = TorrentProcess()
		cmd = ['d:\\python27\\python.exe','E:\\Projects\\TSpider\\DHT\\torrent.py']
		reactor.spawnProcess(self.tp,cmd[0],cmd)
		
		#generate 100 random id , {port : id}
		self.id_list = {}
		now_port = 6881
		for a in range(0,0x64):
			id = struct.pack('B',a) + ''.join(random.choice(string.printable) for x in xrange(19))
			self.id_list[now_port] = id
			now_port += 1

		print len(self.id_list)
		for k,v in self.id_list.iteritems():
			logging.info('generate id:[%d][%r]' % (k,v))
		reactor.resolve(self.begin_host).addCallback(self.gotIP)
 
	def ping(self,host,port):
		random_port = random.choice([k for k,v in self.id_list.iteritems()])
		random_id = self.id_list[random_port]
		data = self.query.encode(t='aa',y='q',q='ping',a={'id':random_id})
		logging.debug('ping: %s:%d' % (host,port))
		self.protocol_list[random_port].sendDatagram(data,host,port)
 
	def find_node(self,host,port):
		# id = ''.join(random.choice(string.lowercase+string.digits) for x in xrange(20))
		# id = '8dm6lzo8c3fk4bcgzeyi'
 
		# mao si shi xunlei de id
		for k,v in self.id_list.iteritems():
			my_id = v
			data = self.query.encode(t='aa',y='q',q='find_node',a={'id':my_id,'target':my_id})
			logging.debug('find_node: %s:%d' % (host,port))
			self.protocol_list[k].sendDatagram(data,host,port)

	def reverse_node(self,n):
		a,b,c,d = n.IP.split('.')
		port = n.Port
		address = chr(int(a)) + chr(int(b)) + chr(int(c)) + chr(int(d)) + chr(port/256) + chr(port%256)
		return n.NodeID + address
 
	def reply_get_peers(self,parameter):
 		transaction_ID,node,info_hash,my_port = parameter
		token = ''.join(random.choice(string.lowercase) for x in xrange(8))
		closest_dict = self.select_closest(info_hash)
		random_nodes = ''

		if not closest_dict:
			return

		for node_id in closest_dict:
			root_node = node_id[0]
			n = self.router[root_node][node_id]
			random_nodes += self.reverse_node(n)
		
		my_id = self.id_list[my_port]
		# random_id = random.choice(self.id_list)
		reply_msg = {'id':my_id,'token':token,'nodes':random_nodes}
		data = self.query.encode(t=transaction_ID,y='r',r=reply_msg)
		logging.debug('reply data:%r' % data)
		logging.debug('reply_get_peers: %s:%d' % (node.IP,node.Port))
		self.protocol_list[my_port].sendDatagram(data,node.IP,node.Port)
		# reactor.stop()
 
 	def XOR(self,s1,s2):
 		s = ''.join(chr(ord(a) ^ ord(b)) for a,b in zip(s1,s2))
 		return ''.join(s)

 	def select_closest(self,info_hash):
 		closest_dict = {}

 		def Convert(d_str):
 			r = ''
 			for s in d_str:
 				r += ('%x' % ord(s))
 			return int(r,16)

 		zero_node = info_hash[0]

 		Exclude_LIST = []
 		def find_root_node(Want_find,Exclude_find=None):
 			mini = 0xff
 			mini_node = ''
 			if Exclude_find:
 				Exclude_LIST.extend(Exclude_find)
 			for a in self.router.keys():
 				if a in Exclude_LIST:
 					continue
 				temp = Convert(self.XOR(a,Want_find))
 				if temp < mini:
 					mini = temp
 					mini_node = a

 			return mini_node
 			
 		
 		if zero_node not in self.router.keys():
 			root_node = find_root_node(zero_node)
 		else:
 			root_node = zero_node

 		temp_nodes = [v.NodeID for v in self.router[root_node].values()]
 		while len(temp_nodes) < 8:
 			now_node = find_root_node(zero_node,root_node)
 			Exclude_LIST.extend(now_node)
 			if now_node not in self.router.keys():
 				return None
 			temp_nodes.extend([v.NodeID for v in self.router[now_node].values()])
 		# logging.info('after <8===============now has %d temp_nodes' % len(temp_nodes))

 		# index = which index to xor
 		def find_mini(index,node_ids,number=8):
 			_del = ''
			l = []
			c_dict = {}
			for node_id in node_ids:
				distance = Convert(self.XOR(node_id[index],info_hash[index]))
				c_dict[node_id] = distance

			l = sorted(c_dict.iteritems(),key=lambda x:x[1])
			result = []
			temp = []

			# print 'number %d:%r' % (number+1,l[number])
			# logging.warning('-------------->list numer:%d<----------------' % len(l))
			for item in l:
				if item[1] < l[number][1]:
					# print 'less',item
					result.append(item[0])
				if item[1] == l[number][1]:
					# print 'equal',item
					temp.append(item[0])

			# print 'result:',result
			# print 'temp:',temp

			while len(result) < number:	
				# print 'got %d less' % number
				index += 1
				add_list = find_mini(index,temp,number-len(result))
				result.extend(add_list)
			if len(result) == number:
				# print 'got %d result' % number
				return result
			elif len(result) > number:
				# print 'error'
				logging.error('get error')
				if reactor.running:
					reactor.stop()

 		index = 1
 		if len(temp_nodes) > 8:
 			# logging.warning('-------------->temp_nodes numer:%d<----------------' % len(temp_nodes))
 			temp_nodes = find_mini(index,temp_nodes)

 		# logging.info('after >8==================now has %d temp_nodes' % len(temp_nodes))
 		if len(temp_nodes) == 8:
 			return temp_nodes
 		else:
 			print temp_nodes
 			if reactor.running:
				reactor.stop()
 			return None
 
	def ProcessData(self,node_info,src_host,src_port,my_port):
		def parse_ip(ip_str):
			if len(ip_str) != 4:
				return None
			return '%s.%s.%s.%s' % (ord(ip_str[0]),ord(ip_str[1]),ord(ip_str[2]),ord(ip_str[3]))
 
		def parse_port(port_str):
			if len(port_str) != 2:
				return None
			return int(('%x%x' % (ord(port_str[0]),ord(port_str[1]))),16)

		transaction_ID = ''
		if 't' in node_info.keys():
			transaction_ID = node_info['t']
 
 		# when receive response of find_node, update the router
		if node_info['y'] == 'r':
			if 'nodes' in node_info['r'].keys():
				temp_str = node_info['r']['nodes']
				# parse 8 nodes info
				while temp_str:
					info = temp_str[0:26]
					nodeid = info[0:20]
					ip = parse_ip(info[20:24])
					port = parse_port(info[24:])
					self.UpdateRouter(Nodes(nodeid,ip,port))	# update 
					temp_str = temp_str[26:]

			if 'id' in node_info['r'].keys():
				node = Nodes(node_info['r']['id'],src_host,src_port)
				self.UpdateRouter(node)
 
		elif node_info['y'] == 'q':
			# update router when some query
			if 'id' in node_info['a'].keys():
				node = Nodes(node_info['a']['id'],src_host,src_port)
				self.UpdateRouter(node)
 
				if node_info['q'] == 'get_peers':
					logging.info('receive get_peers %s:%d' % (src_host,src_port))
					d1 = Deferred()
					d1.addCallbacks(self.reply_get_peers,self.OnError)
					d1.callback((transaction_ID,node,node_info['a']['info_hash'],my_port))

				elif node_info['q'] == 'announce_peer':
					logging.info('receive announce_peer %s:%d' % (src_host,src_port))
					logging.info('%r' % node_info['a'])
					info_hash = node_info['a']['info_hash']
					port = node_info['a']['port']
					id = node_info['a']['id']
					self.ParseTorrent((id,info_hash,src_host,port))

				elif node_info['q'] == 'find_node':
					logging.info('receive find_node %s:%d' % (src_host,src_port))
					d2 = Deferred()
					d2.addCallbacks(self.reply_find_node,self.OnError)
					d2.callback((transaction_ID,node,my_port))

				elif node_info['q'] == 'ping':
					logging.info('receive ping %s:%d' % (src_host,src_port))
					d3 = Deferred()
					d3.addCallbacks(self.reply_ping,self.OnError)
					d3.callback((transaction_ID,node,my_port))

		elif node_info['y'] == 'e':
			logging.error('receive error:')
			for e in node_info['e']:
				logging.error(e)
			# reactor.stop()
 
		logging.debug('router update %d nodes' % len(self.router))

	def ParseTorrent(self,parameter):
		peer_id,info_hash,host,port = parameter
		self.tp.send(info_hash)

	def reply_find_node(self,parameter):
		transaction_ID,node,my_port = parameter
		closest_dict = self.select_closest(node.NodeID)
		random_nodes = ''

		if not closest_dict:
			return

		for node_id in closest_dict:
			root_node = node_id[0]
			n = self.router[root_node][node_id]
			random_nodes += self.reverse_node(n)
		
		
		my_id = self.id_list[my_port]
		reply_msg = {'id':my_id,'nodes':random_nodes}
		data = self.query.encode(t=transaction_ID,y='r',r=reply_msg)
		logging.debug('reply data:%r' % data)
		logging.debug('reply_find_node: %s:%d' % (node.IP,node.Port))
		self.protocol_list[my_port].sendDatagram(data,node.IP,node.Port)

	def reply_ping(self,parameter):
		transaction_ID,node,my_port = parameter
		
		my_id = self.id_list[my_port]
		reply_msg = {'id':my_id}
		data = self.query.encode(t=transaction_ID,y='r',r=reply_msg)
		logging.debug('reply data:%r' % data)
		logging.debug('reply_ping: %s:%d' % (node.IP,node.Port))
		self.protocol_list[my_port].sendDatagram(data,node.IP,node.Port)
 
	def UpdateRouter(self,node):
		logging.debug(node)
		root_node = node.NodeID[0]
		if root_node not in self.router.keys():
			self.router[root_node] = {}

		container = self.router[root_node]
		container[node.NodeID] = node
		# logging.info(self.router)
 		
	def deeperSearch(self):	
		search = 0
		if len(self.router) != 0:
			for root_node in self.router.values():
				if search > 100:
					break
				for node in root_node.values():
					if search > 100:
						break
					if node.search_time:
						self.find_node(node.IP,node.Port)
						node.search_time -= 1
						search += 1
		else:
			self.find_node(self.begin_host,self.begin_port)
		
		reactor.callLater(60,self.deeperSearch)
 
	def checkHealthy(self):
		if len(self.router) != 0:
			if self.bad_node:
				for root_node in self.router.values():
					root_node = {k:v for k,v in root_node.iteritems() if v.healthy != BAD}
				# self.router = {k:v for k,v in self.router.iteritems() if v.healthy != BAD}
				logging.info('checking healthy...deleted %d bad nodes' % self.bad_node)
				self.bad_node = 0
 
			now = datetime.datetime.now()
			counter = 0
			# for node in self.router.values():
			for root_node in self.router.values():
				for node in root_node.values():
					counter += 1
					# 2 minutes no active
					if (now - node.update_time).seconds > 5*60:
						if node.healthy == GOOD:
							node.healthy = DUBIOUS
							self.ping(node.IP,node.Port)
						if node.healthy == DUBIOUS:
							node.healthy = BAD
							self.bad_node += 1
							# wait to delete
			logging.info('router has %d nodes now!' % counter)
 
		reactor.callLater(30,self.checkHealthy)
 
	def gotIP(self,ip):
		logging.debug("%s is %s" % (self.begin_host,ip))
		self.ip = ip
		self.startSearch(ip)
 
	def startSearch(self,ip):
		self.bad_node = 0
		logging.info( 'search begin with %s:%s' % (self.begin_host,self.begin_port))
		for k,v in self.id_list.iteritems():
			protocol = DHTNodeProtocol(self)
			self.protocol_list[k] = protocol
			reactor.listenUDP(k,protocol)

		self.find_node(self.begin_host,self.begin_port)
		reactor.callWhenRunning(self.deeperSearch)
		reactor.callWhenRunning(self.checkHealthy)
 
	def OnError(self,reason):
		logging.error('======GOT ERROR======')
		logging.error(reason)
		if reactor.running:
			reactor.stop()

def main():
	FORMAT = '[%(levelname)s] %(message)s'
	logging.basicConfig(format=FORMAT,level=logging.INFO)
	reload(sys)
	sys.setdefaultencoding('utf-8')

	service = NodeService('router.bittorrent.com',6881)
	reactor.run()

def test():
	DB_name = 'test'
	DB_user = 'root'
	DB_passwd = '1qaz2wsx'
	DB_host = '127.0.0.1'
	#====================test torrent===========================
	reload(sys)
	sys.setdefaultencoding('utf-8')
	cp = adbapi.ConnectionPool('MySQLdb',db=DB_name,user=DB_user,passwd=DB_passwd,host=DB_host,charset="utf8")
	cp.runOperation("SET NAMES utf8");
	Hash = '3C65597CFF3C6A682606CEB68EF77469B8D85CFC'
	fileHandle = open('e:/projects/%s.torrent' % Hash,'rb')
	tor = fileHandle.read()
	a = bdecode(tor)

	info = a['info']
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
		print 'name encoding',encoding
 		name = name.decode(encoding).encode('utf-8')
 	except UnicodeError:
 		name = name.decode('utf-8').encode('utf-8')

 	print name

 	if description:
 		encoding = chardet.detect(description)['encoding']
 		print 'description encoding',encoding
 		description = description.decode(encoding).encode('utf-8')
 		
	def printResult(result):
		logging.info('insert [%s] to db success' % name)
		if reactor.running:
			reactor.stop()

	def printError(error):
		print error
		if reactor.running:
			reactor.stop()

	query_string = '''insert into test.dht (Hash,Name,Description,Rank) values ("%s","%s","%s",1) ON DUPLICATE KEY UPDATE Rank=Rank+1;''' % (
		Hash,name,description)
	print query_string
	# cp.runOperation(query_string).addCallbacks(printResult,printError)
	reactor.run()
	
	#==========================================================

class TorrentProcess(protocol.ProcessProtocol):
	def connectionMade(self):
		print 'Connection Made.'

	def send(self,data):
		self.transport.write(data)
		print 'Sent:%r' % data
		
	def outReceived(self,data):
		print '[torrent]:',data

	def errReceived(self,data):
		print '[torrent error]:',data
		if reactor.running:
			reactor.stop()

	def processEnded(self,reason):
		print 'Process end status',reason.value.exitCode
		if reactor.running:
			reactor.stop()


if __name__ == '__main__':
 
	# ('router.bittorrent.com'), 6881));
	# ('router.utorrent.com'), 6881));
	# ('router.bitcomet.com'), 6881));

	# test()
	main()