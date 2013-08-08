info_hash = '52345678901234567890'
def XOR(s1,s2):
	s = ''.join(chr(ord(a) ^ ord(b)) for a,b in zip(s1,s2))
	return ''.join(s)

def Convert(d_str):
	r = ''
	for s in d_str:
		r += ('%x' % ord(s))

	return int(r,16)

def find_mini(index,node_ids,number=8):
	_del = ''
	l = []
	c_dict = {}
	for node_id in node_ids:
		distance = Convert(XOR(node_id[index],info_hash[index]))
		c_dict[node_id] = distance

	l = sorted(c_dict.iteritems(),key=lambda x:x[1])
	result = []
	temp = []

	# print 'number %d:%r' % (number+1,l[number])
	for item in l:
		if item[1] < l[number][1]:
			print 'less',item
			result.append(item[0])
		if item[1] == l[number][1]:
			print 'equal',item
			temp.append(item[0])

	print 'result:',result
	print 'temp:',temp

	while len(result) < number:	
		print 'got %d less' % number
		index += 1
		add_list = find_mini(index,temp,number-len(result))
		result.extend(add_list)
	if len(result) == number:
		print 'got %d result' % number
		return result
	elif len(result) > number:
		print 'error'

temp_nodes = ['52345678901234567890',
			'52345678901234z67890',
			'52345678901234a67890',
			'62345678901234k67890',
			'62445678901234n67890',
			'62445678901234b67890',
			'72345678901234667890',
			'72345678901234h67890',
			'73345678901233567890']
			
index = 1
print len(temp_nodes)
while len(temp_nodes) > 8:
	temp_nodes = find_mini(index,temp_nodes)	# should run once

print temp_nodes