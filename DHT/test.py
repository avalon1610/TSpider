from twisted.internet import reactor
from twisted.internet.protocol import DatagramProtocol,Factory

class QOTD(DatagramProtocol):
	def __init__(self,data):
		self.data = data

	def sendDatagram(self):
		print '%d sending %s' % (self.host.port,self.data)
		self.transport.write(self.data,('106.36.36.36',9999))

	def datagramReceived(self, data, (host, port)):
		# srchost = self.transport.getHost()
		print 'port %d receive from %s:%d' % (self.host.port,host,port)
		print data

	def startProtocol(self):
		print 'startProtocol'
		self.host = self.transport.getHost()
		self.sendDatagram()

# 8007 is the port you want to run under. Choose something >1024
reactor.listenUDP(8007, QOTD('aaaa'))
reactor.listenUDP(8008, QOTD('bbbb'))
reactor.run()