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
