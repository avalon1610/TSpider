Exclude_LIST = []
def XOR(s1,s2):
	s = ''.join(chr(ord(a) ^ ord(b)) for a,b in zip(s1,s2))
	return ''.join(s)

def Convert(d_str):
	r = ''
	for s in d_str:
		r += ('%x' % ord(s))

	return int(r,16)

def find_root_node(Want_find,Exclude_find=None):
	mini = 0xff
	mini_node = ''
	if Exclude_find:
		Exclude_LIST.extend(Exclude_find)
	for a in router.keys():
		if a in Exclude_LIST:
			continue
		temp = Convert(XOR(a,Want_find))
		if temp < mini:
			mini = temp
			mini_node = a

	return mini_node

router = {'2':{'23446565sdfasdg3453':3,'2fg3452435345 rag':3,'2sf34545454aga':3},
	  '3':{'3bbbbbbbbbbbbbbbb':5,'34444444444444444444':6,'3zzzzzzzzzzzzzzz':8},
	  '4':{'4kkkkkkkkkkkkkkkkk':4,'4ssssssssssssssss':8,'44444444444444444':9},
	  '5':{'5fasdfr452345345345':0,'5343524523452345fg':1,'5fasdfer435454545':4}}

zero_node = '7'
if zero_node not in router.keys():
	root_node = find_root_node(zero_node)
else:
	root_node = zero_node


temp_nodes = [v for v in router[root_node].keys()]
while len(temp_nodes) < 8:
	now_node = find_root_node(zero_node,root_node)
	Exclude_LIST.extend(now_node)
	temp_nodes.extend([v for v in router[now_node].keys()])

print temp_nodes