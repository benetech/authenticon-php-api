from cgi import parse_qs, escape

def application(environ, start_response):

    parameters = parse_qs(environ.get('QUERY_STRING', ''))
    if 'fingerprint' in parameters:
        fingerprint = escape(parameters['fingerprint'][0])
	chl1 = [map(int, c)[0] for c in fingerprint.strip()]
        chl11 = get_list_inter(chl1, 4)
        chl111 = get_higher(chl11,4, 3)
        chl1111 = get_higher(chl111,3, 1)
    else:
        fingerprint = '?'

    if 'part' in parameters:
        part = int(escape(parameters['part'][0]))
    else:
        part = -1

    start_response('200 OK', [('Access-Control-Allow-Origin','*'),('Content-Type', 'text/plain')])

    l = []
    if part == -1 or part == 0:
        l.append('First check:\n')
        l.append(', '.join(to_num_str(chl1111)) + '\n\n')
    if part == -1 or part == 1:
        l.append('Second check:\n')
        l.append(', '.join(to_num_str(chl111)) + '\n\n')
    if part == -1 or part == 2:
        l.append('Third check:\n')
        l.append('\n'.join(to_num_str(chl11)))
    return ''.join(l)

def get_list_inter(l, inter):
    result = []
    for i in range(0, len(l), inter):
        result.append(l[i:i+4])
    return result


def check_sum(l):
    check = [0,0,0,0]
    for ll in l:
        for i in range(4):
            check[i] = (check[i] + ll[i])%10

    return check

def get_higher(l, s, inter):
    result = []
    for i in xrange(0, len(l), inter):
        if(i + s <= len(l)):
            result.append( check_sum(l[i:i+s]))
    return result

def to_num(num):
    strr = ""
    for n in num:
        strr += str(n)
    return strr

def to_num_str(l):
    result = [to_num(s) for s in l]
    return result

if "__main__" == __name__:
    with open("test.txt") as file:
        l1 = file.readline()

    chl1 = [map(int, c)[0] for c in l1.strip()]

    chl11 = get_list_inter(chl1, 4)
    chl111 = get_higher(chl11,4, 3)
    chl1111 = get_higher(chl111,3, 1)
    print "top"
    print to_num_str(chl1111)
    print "second"
    print to_num_str(chl111)
    print "bottom"
    print to_num_str(chl11)

