/**
 * A JavaScript equivalent of PHP's empty. See https://github.com/locutusjs/locutus/blob/master/src/php/var/empty.js
 *
 * @param   {*}  mixedVar  Value to test.
 *
 * @return  {boolean}
 */
function empty(mixedVar) {
	let undef,
		key,
		i,
		len;
	const emptyValues = [undef, null, false, 0, '', '0'];

	for (i = 0, len = emptyValues.length; i < len; i++) {
		if (mixedVar === emptyValues[i]) {
			return true
		}
	}

	if (typeof mixedVar === 'object') {
		for (key in mixedVar) {
			if (mixedVar.hasOwnProperty(key)) {
				return false
			}
		}

		return true
	}

	return false
}

/**
 * A JavaScript equivalent of PHP's str_replace. See https://github.com/locutusjs/locutus/blob/master/src/php/strings/str_replace.js
 *
 * @param   {array|string}  search    The value being searched for.
 * @param   {array|string}  replace   The replacement value that replaces found search values.
 * @param   {*}             subject   The string being searched and replaced on.
 * @param   {object}        countObj  The countObj parameter (optional) if used must be passed in as a object.
 *                              The count will then be written by reference into it's `value` property
 *
 * @return  {string}
 */
function str_replace (search, replace, subject, countObj) {
	let i = 0
	let j = 0
	let temp = ''
	let repl = ''
	let sl = 0
	let fl = 0
	const f = [].concat(search)
	let r = [].concat(replace)
	let s = subject
	let ra = Object.prototype.toString.call(r) === '[object Array]'
	const sa = Object.prototype.toString.call(s) === '[object Array]'
	s = [].concat(s)

	if (typeof (search) === 'object' && typeof (replace) === 'string') {
		temp = replace
		replace = []

		for (i = 0; i < search.length; i += 1) {
			replace[i] = temp
		}

		temp = ''
		r = [].concat(replace)
		ra = Object.prototype.toString.call(r) === '[object Array]'
	}

	if (typeof countObj !== 'undefined') {
		countObj.value = 0
	}

	for (i = 0, sl = s.length; i < sl; i++) {
		if (s[i] === '') {
			continue
		}

		for (j = 0, fl = f.length; j < fl; j++) {
			if (f[j] === '') {
				continue
			}

			temp = s[i] + ''
			repl = ra ? (r[j] !== undefined ? r[j] : '') : r[0]
			s[i] = (temp).split(f[j]).join(repl)

			if (typeof countObj !== 'undefined') {
				countObj.value += ((temp.split(f[j])).length - 1)
			}
		}
	}

	return sa ? s : s[0]
}
