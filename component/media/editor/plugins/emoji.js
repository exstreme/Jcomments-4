// phpcs:disable
(function (sceditor) {
	'use strict';

	const utils = sceditor.utils,
		// Full Emoji List, v15.0 https://unicode.org/emoji/charts/full-emoji-list.html
		list = {
			'smileys': {
				'face-smiling': '1F600,1F603,1F604,1F601,1F606,1F605,1F923,1F602,1F642,1F643,1FAE0,1F609,1F60A,1F607',
				'face-affection': '1F970,1F60D,1F929,1F618,1F617,263A,1F61A,1F619,1F972',
				'face-tongue': '1F60B,1F61B,1F61C,1F92A,1F61D,1F911',
				'face-hand': '1F917,1F92D,1FAE2,1FAE3,1F92B,1F914,1FAE1',
				'face-neutral-skeptical': '1F910,1F928,1F610,1F611,1F636,1FAE5,1F636-200D-1F32B-FE0F,1F60F,1F612,1F644,1F62C,1F62E-200D-1F4A8,1F925,1FAE8',
				'face-sleepy': '1F60C,1F614,1F62A,1F924,1F634',
				'face-unwell': '1F637,1F912,1F915,1F922,1F92E,1F927,1F975,1F976,1F974,1F635,1F635-200D-1F4AB,1F92F',
				'face-hat': '1F920,1F973,1F978',
				'face-glasses': '1F60E,1F913,1F9D0',
				'face-concerned': '1F615,1FAE4,1F61F,1F641,2639,1F62E,1F62F,1F632,1F633,1F97A,1F979,1F626,1F627,1F628,1F630,1F625,1F622,1F62D,1F631,1F616,1F623,1F61E,1F613,1F629,1F62B,1F971',
				'face-negative': '1F624,1F621,1F620,1F92C,1F608,1F47F,1F480,2620',
				'face-costume': '1F4A9,1F921,1F479,1F47A,1F47B,1F47D,1F47E,1F916',
				'cat-face': '1F63A,1F638,1F639,1F63B,1F63C,1F63D,1F640,1F63F,1F63E',
				'monkey-face': '1F648,1F649,1F64A',
				'heart': '1F48C,1F498,1F49D,1F496,1F497,1F493,1F49E,1F495,1F49F,2763,1F494,2764-FE0F-200D-1F525,2764-FE0F-200D-1FA79,2764,1FA77,1F9E1,1F49B,1F49A,1F499,1FA75,1F49C,1F90E,1F5A4,1FA76,1F90D',
				'emotion': '1F48B,1F4AF,1F4A2,1F4A5,1F4AB,1F4A6,1F4A8,1F573,1F4AC,1F441-FE0F-200D-1F5E8-FE0F,1F5E8,1F5EF,1F4AD,1F4A4'
			},
			'people': {
				'hand-fingers-open': '1F44B,1F91A,1F590,270B,1F596,1FAF1,1FAF2,1FAF3,1FAF4,1FAF7,1FAF8',
				'hand-fingers-partial': '1F44C,1F90C,1F90F,270C,1F91E,1FAF0,1F91F,1F918,1F919',
				'hand-single-finger': '1F448,1F449,1F446,1F595,1F447,261D,1FAF5',
				'hand-fingers-closed': '1F44D,1F44E,270A,1F44A,1F91B,1F91C',
				'hands': '1F44F,1F64C,1FAF6,1F450,1F932,1F91D,1F64F',
				'hand-prop': '270D,1F485,1F933',
				'body-parts': '1F4AA,1F9BE,1F9BF,1F9B5,1F9B6,1F442,1F9BB,1F443,1F9E0,1FAC0,1FAC1,1F9B7,1F9B4,1F440,1F441,1F445,1F444,1FAE6',
				'person': '1F476,1F9D2,1F466,1F467,1F9D1,1F471,1F468,1F9D4,1F9D4-200D-2642-FE0F,1F9D4-200D-2640-FE0F,1F468-200D-1F9B0,1F468-200D-1F9B1,1F468-200D-1F9B3,1F468-200D-1F9B2,1F469,1F469-200D-1F9B0,1F9D1-200D-1F9B0,1F469-200D-1F9B1,1F9D1-200D-1F9B1,1F469-200D-1F9B3,1F9D1-200D-1F9B3,1F469-200D-1F9B2,1F9D1-200D-1F9B2,1F471-200D-2640-FE0F,1F471-200D-2642-FE0F,1F9D3,1F474,1F475',
				'person-gesture': '1F64D,1F64D-200D-2642-FE0F,1F64D-200D-2640-FE0F,1F64E,1F64E-200D-2642-FE0F,1F64E-200D-2640-FE0F,1F645,1F645-200D-2642-FE0F,1F645-200D-2640-FE0F,1F646,1F646-200D-2642-FE0F,1F646-200D-2640-FE0F,1F481,1F481-200D-2642-FE0F,1F481-200D-2640-FE0F,1F64B,1F64B-200D-2642-FE0F,1F64B-200D-2640-FE0F,1F9CF,1F9CF-200D-2642-FE0F,1F9CF-200D-2640-FE0F,1F647,1F647-200D-2642-FE0F,1F647-200D-2640-FE0F,1F926,1F926-200D-2642-FE0F,1F926-200D-2640-FE0F,1F937,1F937-200D-2642-FE0F,1F937-200D-2640-FE0F',
				'person-role': '1F9D1-200D-2695-FE0F,1F468-200D-2695-FE0F,1F469-200D-2695-FE0F,1F9D1-200D-1F393,1F468-200D-1F393,1F469-200D-1F393,1F9D1-200D-1F3EB,1F468-200D-1F3EB,1F469-200D-1F3EB,1F9D1-200D-2696-FE0F,1F468-200D-2696-FE0F,1F469-200D-2696-FE0F,1F9D1-200D-1F33E,1F468-200D-1F33E,1F469-200D-1F33E,1F9D1-200D-1F373,1F468-200D-1F373,1F469-200D-1F373,1F9D1-200D-1F527,1F468-200D-1F527,1F469-200D-1F527,1F9D1-200D-1F3ED,1F468-200D-1F3ED,1F469-200D-1F3ED,1F9D1-200D-1F4BC,1F468-200D-1F4BC,1F469-200D-1F4BC,1F9D1-200D-1F52C,1F468-200D-1F52C,1F469-200D-1F52C,1F9D1-200D-1F4BB,1F468-200D-1F4BB,1F469-200D-1F4BB,1F9D1-200D-1F3A4,1F468-200D-1F3A4,1F469-200D-1F3A4,1F9D1-200D-1F3A8,1F468-200D-1F3A8,1F469-200D-1F3A8,1F9D1-200D-2708-FE0F,1F468-200D-2708-FE0F,1F469-200D-2708-FE0F,1F9D1-200D-1F680,1F468-200D-1F680,1F469-200D-1F680,1F9D1-200D-1F692,1F468-200D-1F692,1F469-200D-1F692,1F46E,1F46E-200D-2642-FE0F,1F46E-200D-2640-FE0F,1F575,1F575-FE0F-200D-2642-FE0F,1F575-FE0F-200D-2640-FE0F,1F482,1F482-200D-2642-FE0F,1F482-200D-2640-FE0F,1F977,1F477,1F477-200D-2642-FE0F,1F477-200D-2640-FE0F,1FAC5,1F934,1F478,1F473,1F473-200D-2642-FE0F,1F473-200D-2640-FE0F,1F472,1F9D5,1F935,1F935-200D-2642-FE0F,1F935-200D-2640-FE0F,1F470,1F470-200D-2642-FE0F,1F470-200D-2640-FE0F,1F930,1FAC3,1FAC4,1F931,1F469-200D-1F37C,1F468-200D-1F37C,1F9D1-200D-1F37C',
				'person-fantasy': '1F47C,1F385,1F936,1F9D1-200D-1F384,1F9B8,1F9B8-200D-2642-FE0F,1F9B8-200D-2640-FE0F,1F9B9,1F9B9-200D-2642-FE0F,1F9B9-200D-2640-FE0F,1F9D9,1F9D9-200D-2642-FE0F,1F9D9-200D-2640-FE0F,1F9DA,1F9DA-200D-2642-FE0F,1F9DA-200D-2640-FE0F,1F9DB,1F9DB-200D-2642-FE0F,1F9DB-200D-2640-FE0F,1F9DC,1F9DC-200D-2642-FE0F,1F9DC-200D-2640-FE0F,1F9DD,1F9DD-200D-2642-FE0F,1F9DD-200D-2640-FE0F,1F9DE,1F9DE-200D-2642-FE0F,1F9DE-200D-2640-FE0F,1F9DF,1F9DF-200D-2642-FE0F,1F9DF-200D-2640-FE0F,1F9CC',
				'person-activity': '1F486,1F486-200D-2642-FE0F,1F486-200D-2640-FE0F,1F487,1F487-200D-2642-FE0F,1F487-200D-2640-FE0F,1F6B6,1F6B6-200D-2642-FE0F,1F6B6-200D-2640-FE0F,1F9CD,1F9CD-200D-2642-FE0F,1F9CD-200D-2640-FE0F,1F9CE,1F9CE-200D-2642-FE0F,1F9CE-200D-2640-FE0F,1F9D1-200D-1F9AF,1F468-200D-1F9AF,1F469-200D-1F9AF,1F9D1-200D-1F9BC,1F468-200D-1F9BC,1F469-200D-1F9BC,1F9D1-200D-1F9BD,1F468-200D-1F9BD,1F469-200D-1F9BD,1F3C3,1F3C3-200D-2642-FE0F,1F3C3-200D-2640-FE0F,1F483,1F57A,1F574,1F46F,1F46F-200D-2642-FE0F,1F46F-200D-2640-FE0F,1F9D6,1F9D6-200D-2642-FE0F,1F9D6-200D-2640-FE0F,1F9D7,1F9D7-200D-2642-FE0F,1F9D7-200D-2640-FE0F',
				'person-sport': '1F93A,1F3C7,26F7,1F3C2,1F3CC,1F3CC-FE0F-200D-2642-FE0F,1F3CC-FE0F-200D-2640-FE0F,1F3C4,1F3C4-200D-2642-FE0F,1F3C4-200D-2640-FE0F,1F6A3,1F6A3-200D-2642-FE0F,1F6A3-200D-2640-FE0F,1F3CA,1F3CA-200D-2642-FE0F,1F3CA-200D-2640-FE0F,26F9,26F9-FE0F-200D-2642-FE0F,26F9-FE0F-200D-2640-FE0F,1F3CB,1F3CB-FE0F-200D-2642-FE0F,1F3CB-FE0F-200D-2640-FE0F,1F6B4,1F6B4-200D-2642-FE0F,1F6B4-200D-2640-FE0F,1F6B5,1F6B5-200D-2642-FE0F,1F6B5-200D-2640-FE0F,1F938,1F938-200D-2642-FE0F,1F938-200D-2640-FE0F,1F93C,1F93C-200D-2642-FE0F,1F93C-200D-2640-FE0F,1F93D,1F93D-200D-2642-FE0F,1F93D-200D-2640-FE0F,1F93E,1F93E-200D-2642-FE0F,1F93E-200D-2640-FE0F,1F939,1F939-200D-2642-FE0F,1F939-200D-2640-FE0F',
				'person-resting': '1F9D8,1F9D8-200D-2642-FE0F,1F9D8-200D-2640-FE0F,1F6C0,1F6CC',
				'family': '1F9D1-200D-1F91D-200D-1F9D1,1F46D,1F46B,1F46C,1F48F,1F469-200D-2764-FE0F-200D-1F48B-200D-1F468,1F468-200D-2764-FE0F-200D-1F48B-200D-1F468,1F469-200D-2764-FE0F-200D-1F48B-200D-1F469,1F491,1F469-200D-2764-FE0F-200D-1F468,1F468-200D-2764-FE0F-200D-1F468,1F469-200D-2764-FE0F-200D-1F469,1F46A,1F468-200D-1F469-200D-1F466,1F468-200D-1F469-200D-1F467,1F468-200D-1F469-200D-1F467-200D-1F466,1F468-200D-1F469-200D-1F466-200D-1F466,1F468-200D-1F469-200D-1F467-200D-1F467,1F468-200D-1F468-200D-1F466,1F468-200D-1F468-200D-1F467,1F468-200D-1F468-200D-1F467-200D-1F466,1F468-200D-1F468-200D-1F466-200D-1F466,1F468-200D-1F468-200D-1F467-200D-1F467,1F469-200D-1F469-200D-1F466,1F469-200D-1F469-200D-1F467,1F469-200D-1F469-200D-1F467-200D-1F466,1F469-200D-1F469-200D-1F466-200D-1F466,1F469-200D-1F469-200D-1F467-200D-1F467,1F468-200D-1F466,1F468-200D-1F466-200D-1F466,1F468-200D-1F467,1F468-200D-1F467-200D-1F466,1F468-200D-1F467-200D-1F467,1F469-200D-1F466,1F469-200D-1F466-200D-1F466,1F469-200D-1F467,1F469-200D-1F467-200D-1F466,1F469-200D-1F467-200D-1F467',
				'person-symbol': '1F5E3,1F464,1F465,1FAC2,1F463'
			},
			'component': {
				'hair-style': '1F9B0,1F9B1,1F9B3,1F9B2'
			},
			'nature': {
				'animal-mammal': '1F435,1F412,1F98D,1F9A7,1F436,1F415,1F9AE,1F415-200D-1F9BA,1F429,1F43A,1F98A,1F99D,1F431,1F408,1F408-200D-2B1B,1F981,1F42F,1F405,1F406,1F434,1FACE,1FACF,1F40E,1F984,1F993,1F98C,1F9AC,1F42E,1F402,1F403,1F404,1F437,1F416,1F417,1F43D,1F40F,1F411,1F410,1F42A,1F42B,1F999,1F992,1F418,1F9A3,1F98F,1F99B,1F42D,1F401,1F400,1F439,1F430,1F407,1F43F,1F9AB,1F994,1F987,1F43B,1F43B-200D-2744-FE0F,1F428,1F43C,1F9A5,1F9A6,1F9A8,1F998,1F9A1,1F43E',
				'animal-bird': '1F983,1F414,1F413,1F423,1F424,1F425,1F426,1F427,1F54A,1F985,1F986,1F9A2,1F989,1F9A4,1FAB6,1F9A9,1F99A,1F99C,1FABD,1F426-200D-2B1B,1FABF',
				'animal-amphibian': '1F438',
				'animal-reptile': '1F40A,1F422,1F98E,1F40D,1F432,1F409,1F995,1F996',
				'animal-marine': '1F433,1F40B,1F42C,1F9AD,1F41F,1F420,1F421,1F988,1F419,1F41A,1FAB8,1FABC',
				'animal-bug': '1F40C,1F98B,1F41B,1F41C,1F41D,1FAB2,1F41E,1F997,1FAB3,1F577,1F578,1F982,1F99F,1FAB0,1FAB1,1F9A0',
				'plant-flower': '1F490,1F338,1F4AE,1FAB7,1F3F5,1F339,1F940,1F33A,1F33B,1F33C,1F337,1FABB',
				'plant-other': '1F331,1FAB4,1F332,1F333,1F334,1F335,1F33E,1F33F,2618,1F340,1F341,1F342,1F343,1FAB9,1FABA,1F344'
			},
			'foodstuff': {
				'food-fruit': '1F347,1F348,1F349,1F34A,1F34B,1F34C,1F34D,1F96D,1F34E,1F34F,1F350,1F351,1F352,1F353,1FAD0,1F95D,1F345,1FAD2,1F965',
				'food-vegetable': '1F951,1F346,1F954,1F955,1F33D,1F336,1FAD1,1F952,1F96C,1F966,1F9C4,1F9C5,1F95C,1FAD8,1F330,1FADA,1FADB',
				'food-prepared': '1F35E,1F950,1F956,1FAD3,1F968,1F96F,1F95E,1F9C7,1F9C0,1F356,1F357,1F969,1F953,1F354,1F35F,1F355,1F32D,1F96A,1F32E,1F32F,1FAD4,1F959,1F9C6,1F95A,1F373,1F958,1F372,1FAD5,1F963,1F957,1F37F,1F9C8,1F9C2,1F96B',
				'food-asian': '1F371,1F358,1F359,1F35A,1F35B,1F35C,1F35D,1F360,1F362,1F363,1F364,1F365,1F96E,1F361,1F95F,1F960,1F961',
				'food-marine': '1F980,1F99E,1F990,1F991,1F9AA',
				'food-sweet': '1F366,1F367,1F368,1F369,1F36A,1F382,1F370,1F9C1,1F967,1F36B,1F36C,1F36D,1F36E,1F36F',
				'drink': '1F37C,1F95B,2615,1FAD6,1F375,1F376,1F37E,1F377,1F378,1F379,1F37A,1F37B,1F942,1F943,1FAD7,1F964,1F9CB,1F9C3,1F9C9,1F9CA',
				'dishware': '1F962,1F37D,1F374,1F944,1F52A,1FAD9,1F3FA'
			},
			'travel': {
				'place-map': '1F30D,1F30E,1F30F,1F310,1F5FA,1F5FE,1F9ED',
				'place-geographic': '1F3D4,26F0,1F30B,1F5FB,1F3D5,1F3D6,1F3DC,1F3DD,1F3DE',
				'place-building': '1F3DF,1F3DB,1F3D7,1F9F1,1FAA8,1FAB5,1F6D6,1F3D8,1F3DA,1F3E0,1F3E1,1F3E2,1F3E3,1F3E4,1F3E5,1F3E6,1F3E8,1F3E9,1F3EA,1F3EB,1F3EC,1F3ED,1F3EF,1F3F0,1F492,1F5FC,1F5FD',
				'place-religious': '26EA,1F54C,1F6D5,1F54D,26E9,1F54B',
				'place-other': '26F2,26FA,1F301,1F303,1F3D9,1F304,1F305,1F306,1F307,1F309,2668,1F3A0,1F6DD,1F3A1,1F3A2,1F488,1F3AA',
				'transport-ground': '1F682,1F683,1F684,1F685,1F686,1F687,1F688,1F689,1F68A,1F69D,1F69E,1F68B,1F68C,1F68D,1F68E,1F690,1F691,1F692,1F693,1F694,1F695,1F696,1F697,1F698,1F699,1F6FB,1F69A,1F69B,1F69C,1F3CE,1F3CD,1F6F5,1F9BD,1F9BC,1F6FA,1F6B2,1F6F4,1F6F9,1F6FC,1F68F,1F6E3,1F6E4,1F6E2,26FD,1F6DE,1F6A8,1F6A5,1F6A6,1F6D1,1F6A7',
				'transport-water': '2693,1F6DF,26F5,1F6F6,1F6A4,1F6F3,26F4,1F6E5,1F6A2',
				'transport-air': '2708,1F6E9,1F6EB,1F6EC,1FA82,1F4BA,1F681,1F69F,1F6A0,1F6A1,1F6F0,1F680,1F6F8',
				'hotel': '1F6CE,1F9F3',
				'time': '231B,23F3,231A,23F0,23F1,23F2,1F570,1F55B,1F567,1F550,1F55C,1F551,1F55D,1F552,1F55E,1F553,1F55F,1F554,1F560,1F555,1F561,1F556,1F562,1F557,1F563,1F558,1F564,1F559,1F565,1F55A,1F566',
				'sky-weather': '1F311,1F312,1F313,1F314,1F315,1F316,1F317,1F318,1F319,1F31A,1F31B,1F31C,1F321,2600,1F31D,1F31E,1FA90,2B50,1F31F,1F320,1F30C,2601,26C5,26C8,1F324,1F325,1F326,1F327,1F328,1F329,1F32A,1F32B,1F32C,1F300,1F308,1F302,2602,2614,26F1,26A1,2744,2603,26C4,2604,1F525,1F4A7,1F30A'
			},
			'activities': {
				'event': '1F383,1F384,1F386,1F387,1F9E8,2728,1F388,1F389,1F38A,1F38B,1F38D,1F38E,1F38F,1F390,1F391,1F9E7,1F380,1F381,1F397,1F39F,1F3AB',
				'award-medal': '1F396,1F3C6,1F3C5,1F947,1F948,1F949',
				'sport': '26BD,26BE,1F94E,1F3C0,1F3D0,1F3C8,1F3C9,1F3BE,1F94F,1F3B3,1F3CF,1F3D1,1F3D2,1F94D,1F3D3,1F3F8,1F94A,1F94B,1F945,26F3,26F8,1F3A3,1F93F,1F3BD,1F3BF,1F6F7,1F94C',
				'game': '1F3AF,1FA80,1FA81,1F52B,1F3B1,1F52E,1FA84,1F3AE,1F579,1F3B0,1F3B2,1F9E9,1F9F8,1FA85,1FAA9,1FA86,2660,2665,2666,2663,265F,1F0CF,1F004,1F3B4',
				'arts-crafts': '1F3AD,1F5BC,1F3A8,1F9F5,1FAA1,1F9F6,1FAA2'
			},
			'objects': {
				'clothing': '1F453,1F576,1F97D,1F97C,1F9BA,1F454,1F455,1F456,1F9E3,1F9E4,1F9E5,1F9E6,1F457,1F458,1F97B,1FA71,1FA72,1FA73,1F459,1F45A,1FAAD,1F45B,1F45C,1F45D,1F6CD,1F392,1FA74,1F45E,1F45F,1F97E,1F97F,1F460,1F461,1FA70,1F462,1FAAE,1F451,1F452,1F3A9,1F393,1F9E2,1FA96,26D1,1F4FF,1F484,1F48D,1F48E',
				'sound': '1F507,1F508,1F509,1F50A,1F4E2,1F4E3,1F4EF,1F514,1F515',
				'music': '1F3BC,1F3B5,1F3B6,1F399,1F39A,1F39B,1F3A4,1F3A7,1F4FB',
				'musical-instrument': '1F3B7,1FA97,1F3B8,1F3B9,1F3BA,1F3BB,1FA95,1F941,1FA98,1FA87,1FA88',
				'phone': '1F4F1,1F4F2,260E,1F4DE,1F4DF,1F4E0',
				'computer': '1F50B,1FAAB,1F50C,1F4BB,1F5A5,1F5A8,2328,1F5B1,1F5B2,1F4BD,1F4BE,1F4BF,1F4C0,1F9EE',
				'light-video': '1F3A5,1F39E,1F4FD,1F3AC,1F4FA,1F4F7,1F4F8,1F4F9,1F4FC,1F50D,1F50E,1F56F,1F4A1,1F526,1F3EE,1FA94',
				'book-paper': '1F4D4,1F4D5,1F4D6,1F4D7,1F4D8,1F4D9,1F4DA,1F4D3,1F4D2,1F4C3,1F4DC,1F4C4,1F4F0,1F5DE,1F4D1,1F516,1F3F7',
				'money': '1F4B0,1FA99,1F4B4,1F4B5,1F4B6,1F4B7,1F4B8,1F4B3,1F9FE,1F4B9',
				'mail': '2709,1F4E7,1F4E8,1F4E9,1F4E4,1F4E5,1F4E6,1F4EB,1F4EA,1F4EC,1F4ED,1F4EE,1F5F3',
				'writing': '270F,2712,1F58B,1F58A,1F58C,1F58D,1F4DD',
				'office': '1F4BC,1F4C1,1F4C2,1F5C2,1F4C5,1F4C6,1F5D2,1F5D3,1F4C7,1F4C8,1F4C9,1F4CA,1F4CB,1F4CC,1F4CD,1F4CE,1F587,1F4CF,1F4D0,2702,1F5C3,1F5C4,1F5D1',
				'lock': '1F512,1F513,1F50F,1F510,1F511,1F5DD',
				'tool': '1F528,1FA93,26CF,2692,1F6E0,1F5E1,2694,1F4A3,1FA83,1F3F9,1F6E1,1FA9A,1F527,1FA9B,1F529,2699,1F5DC,2696,1F9AF,1F517,26D3,1FA9D,1F9F0,1F9F2,1FA9C',
				'science': '2697,1F9EA,1F9EB,1F9EC,1F52C,1F52D,1F4E1',
				'medical': '1F489,1FA78,1F48A,1FA79,1FA7C,1FA7A,1FA7B',
				'household': '1F6AA,1F6D7,1FA9E,1FA9F,1F6CF,1F6CB,1FA91,1F6BD,1FAA0,1F6BF,1F6C1,1FAA4,1FA92,1F9F4,1F9F7,1F9F9,1F9FA,1F9FB,1FAA3,1F9FC,1FAE7,1FAA5,1F9FD,1F9EF,1F6D2',
				'other-object': '1F6AC,26B0,1FAA6,26B1,1F9FF,1FAAC,1F5FF,1FAA7,1FAAA'
			},
			'symbols': {
				'transport-sign': '1F3E7,1F6AE,1F6B0,267F,1F6B9,1F6BA,1F6BB,1F6BC,1F6BE,1F6C2,1F6C3,1F6C4,1F6C5',
				'warning': '26A0,1F6B8,26D4,1F6AB,1F6B3,1F6AD,1F6AF,1F6B1,1F6B7,1F4F5,1F51E,2622,2623',
				'arrow': '2B06,2197,27A1,2198,2B07,2199,2B05,2196,2195,2194,21A9,21AA,2934,2935,1F503,1F504,1F519,1F51A,1F51B,1F51C,1F51D',
				'religion': '1F6D0,269B,1F549,2721,2638,262F,271D,2626,262A,262E,1F54E,1F52F,1FAAF',
				'zodiac': '2648,2649,264A,264B,264C,264D,264E,264F,2650,2651,2652,2653,26CE',
				'av-symbol': '1F500,1F501,1F502,25B6,23E9,23ED,23EF,25C0,23EA,23EE,1F53C,23EB,1F53D,23EC,23F8,23F9,23FA,23CF,1F3A6,1F505,1F506,1F4F6,1F6DC,1F4F3,1F4F4',
				'gender': '2640,2642,26A7',
				'math': '2716,2795,2796,2797,1F7F0,267E',
				'punctuation': '203C,2049,2753,2754,2755,2757,3030',
				'currency': '1F4B1,1F4B2',
				'other-symbol': '2695,267B,269C,1F531,1F4DB,1F530,2B55,2705,2611,2714,274C,274E,27B0,27BF,303D,2733,2734,2747,00A9,00AE,2122',
				'keycap': '0023-FE0F-20E3,002A-FE0F-20E3,0030-FE0F-20E3,0031-FE0F-20E3,0032-FE0F-20E3,0033-FE0F-20E3,0034-FE0F-20E3,0035-FE0F-20E3,0036-FE0F-20E3,0037-FE0F-20E3,0038-FE0F-20E3,0039-FE0F-20E3,1F51F',
				'alphanum': '1F520,1F521,1F522,1F523,1F524,1F170,1F18E,1F171,1F191,1F192,1F193,2139,1F194,24C2,1F195,1F196,1F17E,1F197,1F17F,1F198,1F199,1F19A,1F201,1F202,1F237,1F236,1F22F,1F250,1F239,1F21A,1F232,1F251,1F238,1F234,1F233,3297,3299,1F23A,1F235',
				'geometric': '1F534,1F7E0,1F7E1,1F7E2,1F535,1F7E3,1F7E4,26AB,26AA,1F7E5,1F7E7,1F7E8,1F7E9,1F7E6,1F7EA,1F7EB,2B1B,2B1C,25FC,25FB,25FE,25FD,25AA,25AB,1F536,1F537,1F538,1F539,1F53A,1F53B,1F4A0,1F518,1F533,1F532'
			},
			'flags': {
				'flag': '1F3C1,1F6A9,1F38C,1F3F4,1F3F3,1F3F3-FE0F-200D-1F308,1F3F3-FE0F-200D-26A7-FE0F,1F3F4-200D-2620-FE0F',
				'country-flag': '1F1E6-1F1E8,1F1E6-1F1E9,1F1E6-1F1EA,1F1E6-1F1EB,1F1E6-1F1EC,1F1E6-1F1EE,1F1E6-1F1F1,1F1E6-1F1F2,1F1E6-1F1F4,1F1E6-1F1F6,1F1E6-1F1F7,1F1E6-1F1F8,1F1E6-1F1F9,1F1E6-1F1FA,1F1E6-1F1FC,1F1E6-1F1FD,1F1E6-1F1FF,1F1E7-1F1E6,1F1E7-1F1E7,1F1E7-1F1E9,1F1E7-1F1EA,1F1E7-1F1EB,1F1E7-1F1EC,1F1E7-1F1ED,1F1E7-1F1EE,1F1E7-1F1EF,1F1E7-1F1F1,1F1E7-1F1F2,1F1E7-1F1F3,1F1E7-1F1F4,1F1E7-1F1F6,1F1E7-1F1F7,1F1E7-1F1F8,1F1E7-1F1F9,1F1E7-1F1FB,1F1E7-1F1FC,1F1E7-1F1FE,1F1E7-1F1FF,1F1E8-1F1E6,1F1E8-1F1E8,1F1E8-1F1E9,1F1E8-1F1EB,1F1E8-1F1EC,1F1E8-1F1ED,1F1E8-1F1EE,1F1E8-1F1F0,1F1E8-1F1F1,1F1E8-1F1F2,1F1E8-1F1F3,1F1E8-1F1F4,1F1E8-1F1F5,1F1E8-1F1F7,1F1E8-1F1FA,1F1E8-1F1FB,1F1E8-1F1FC,1F1E8-1F1FD,1F1E8-1F1FE,1F1E8-1F1FF,1F1E9-1F1EA,1F1E9-1F1EC,1F1E9-1F1EF,1F1E9-1F1F0,1F1E9-1F1F2,1F1E9-1F1F4,1F1E9-1F1FF,1F1EA-1F1E6,1F1EA-1F1E8,1F1EA-1F1EA,1F1EA-1F1EC,1F1EA-1F1ED,1F1EA-1F1F7,1F1EA-1F1F8,1F1EA-1F1F9,1F1EA-1F1FA,1F1EB-1F1EE,1F1EB-1F1EF,1F1EB-1F1F0,1F1EB-1F1F2,1F1EB-1F1F4,1F1EB-1F1F7,1F1EC-1F1E6,1F1EC-1F1E7,1F1EC-1F1E9,1F1EC-1F1EA,1F1EC-1F1EB,1F1EC-1F1EC,1F1EC-1F1ED,1F1EC-1F1EE,1F1EC-1F1F1,1F1EC-1F1F2,1F1EC-1F1F3,1F1EC-1F1F5,1F1EC-1F1F6,1F1EC-1F1F7,1F1EC-1F1F8,1F1EC-1F1F9,1F1EC-1F1FA,1F1EC-1F1FC,1F1EC-1F1FE,1F1ED-1F1F0,1F1ED-1F1F2,1F1ED-1F1F3,1F1ED-1F1F7,1F1ED-1F1F9,1F1ED-1F1FA,1F1EE-1F1E8,1F1EE-1F1E9,1F1EE-1F1EA,1F1EE-1F1F1,1F1EE-1F1F2,1F1EE-1F1F3,1F1EE-1F1F4,1F1EE-1F1F6,1F1EE-1F1F7,1F1EE-1F1F8,1F1EE-1F1F9,1F1EF-1F1EA,1F1EF-1F1F2,1F1EF-1F1F4,1F1EF-1F1F5,1F1F0-1F1EA,1F1F0-1F1EC,1F1F0-1F1ED,1F1F0-1F1EE,1F1F0-1F1F2,1F1F0-1F1F3,1F1F0-1F1F5,1F1F0-1F1F7,1F1F0-1F1FC,1F1F0-1F1FE,1F1F0-1F1FF,1F1F1-1F1E6,1F1F1-1F1E7,1F1F1-1F1E8,1F1F1-1F1EE,1F1F1-1F1F0,1F1F1-1F1F7,1F1F1-1F1F8,1F1F1-1F1F9,1F1F1-1F1FA,1F1F1-1F1FB,1F1F1-1F1FE,1F1F2-1F1E6,1F1F2-1F1E8,1F1F2-1F1E9,1F1F2-1F1EA,1F1F2-1F1EB,1F1F2-1F1EC,1F1F2-1F1ED,1F1F2-1F1F0,1F1F2-1F1F1,1F1F2-1F1F2,1F1F2-1F1F3,1F1F2-1F1F4,1F1F2-1F1F5,1F1F2-1F1F6,1F1F2-1F1F7,1F1F2-1F1F8,1F1F2-1F1F9,1F1F2-1F1FA,1F1F2-1F1FB,1F1F2-1F1FC,1F1F2-1F1FD,1F1F2-1F1FE,1F1F2-1F1FF,1F1F3-1F1E6,1F1F3-1F1E8,1F1F3-1F1EA,1F1F3-1F1EB,1F1F3-1F1EC,1F1F3-1F1EE,1F1F3-1F1F1,1F1F3-1F1F4,1F1F3-1F1F5,1F1F3-1F1F7,1F1F3-1F1FA,1F1F3-1F1FF,1F1F4-1F1F2,1F1F5-1F1E6,1F1F5-1F1EA,1F1F5-1F1EB,1F1F5-1F1EC,1F1F5-1F1ED,1F1F5-1F1F0,1F1F5-1F1F1,1F1F5-1F1F2,1F1F5-1F1F3,1F1F5-1F1F7,1F1F5-1F1F8,1F1F5-1F1F9,1F1F5-1F1FC,1F1F5-1F1FE,1F1F6-1F1E6,1F1F7-1F1EA,1F1F7-1F1F4,1F1F7-1F1F8,1F1F7-1F1FA,1F1F7-1F1FC,1F1F8-1F1E6,1F1F8-1F1E7,1F1F8-1F1E8,1F1F8-1F1E9,1F1F8-1F1EA,1F1F8-1F1EC,1F1F8-1F1ED,1F1F8-1F1EE,1F1F8-1F1EF,1F1F8-1F1F0,1F1F8-1F1F1,1F1F8-1F1F2,1F1F8-1F1F3,1F1F8-1F1F4,1F1F8-1F1F7,1F1F8-1F1F8,1F1F8-1F1F9,1F1F8-1F1FB,1F1F8-1F1FD,1F1F8-1F1FE,1F1F8-1F1FF,1F1F9-1F1E6,1F1F9-1F1E8,1F1F9-1F1E9,1F1F9-1F1EB,1F1F9-1F1EC,1F1F9-1F1ED,1F1F9-1F1EF,1F1F9-1F1F0,1F1F9-1F1F1,1F1F9-1F1F2,1F1F9-1F1F3,1F1F9-1F1F4,1F1F9-1F1F7,1F1F9-1F1F9,1F1F9-1F1FB,1F1F9-1F1FC,1F1F9-1F1FF,1F1FA-1F1E6,1F1FA-1F1EC,1F1FA-1F1F2,1F1FA-1F1F3,1F1FA-1F1F8,1F1FA-1F1FE,1F1FA-1F1FF,1F1FB-1F1E6,1F1FB-1F1E8,1F1FB-1F1EA,1F1FB-1F1EC,1F1FB-1F1EE,1F1FB-1F1F3,1F1FB-1F1FA,1F1FC-1F1EB,1F1FC-1F1F8,1F1FD-1F1F0,1F1FE-1F1EA,1F1FE-1F1F9,1F1FF-1F1E6,1F1FF-1F1F2,1F1FF-1F1FC',
				'subdivision-flag': '1F3F4-E0067-E0062-E0065-E006E-E0067-E007F,1F3F4-E0067-E0062-E0073-E0063-E0074-E007F,1F3F4-E0067-E0062-E0077-E006C-E0073-E007F'
			}
		};

	/**
	 * Plugin options:
	 *
	 * emoji.enable           - Enable plugin. Required. Type: boolean
	 * emoji.closeAfterSelect - Hide dropdown after click on selected emoji. Type: boolean. Default: false
	 * emoji.groupTitle       - Show/hide group title. E.g.: 'flags'. Type: boolean
	 * emoji.subgroupTitle    - Show/hide subgroup title. E.g.: 'country-flag'. Type: boolean
	 * emoji.excludeGroups    - Exclude emojis group. Comma separated list of groups. E.g.: 'smileys,people'.
	 *                          Type: string
	 * emoji.excludeSubgroups - Exclude emoji subgroup. Comma separated list of subgroups.
	 * 							E.g.: 'face-smiling,monkey-face'. Type: string
	 * emoji.excludeEmojis    - Exclude emojis. Comma separated list of emojis. E.g.: '1F604,1F601'. Type: string
	 *
	 * Example: "emoji":{"enable":true,"excludeEmojis":"1F600,1F603"}
	 */
	sceditor.plugins.emoji = function () {
		const editorContainer = document.querySelector('.sceditor-container');

		/*
		 * Tabs.js v.1.0.0
		 * Copyright John SardaÃ±as
		 * Released under the MIT license
		 * Date: 04-11-2020
		 */
		function tabs(selector){
			const tabTriggers = selector.children[0], tabContents = selector.children[1];

			tabTriggers.children[0].firstElementChild.classList.add('active');
			tabContents.children[0].classList.add('active');

			for (let i = 0; i < tabTriggers.children.length; i++) {
				tabTriggers.children[i].firstElementChild.dataset.tab = i.toString();
				tabContents.children[i].dataset.tab = i.toString();

				tabTriggers.children[i].addEventListener('click', e => {
					e.preventDefault();

					for (let j = 0; j < tabContents.children.length; j++) {
						tabContents.children[j].dataset.tab === tabTriggers.children[i].firstElementChild.dataset.tab
							? tabContents.children[j].classList.add('active')
							: tabContents.children[j].classList.remove('active');
						tabTriggers.children[j].firstElementChild.dataset.tab === tabTriggers.children[i].firstElementChild.dataset.tab
							? tabTriggers.children[j].firstElementChild.classList.add('active')
							: tabTriggers.children[j].firstElementChild.classList.remove('active');
					}
				});
			}
		}

		function addEventListener(el, eventName, selector, eventHandler) {
			if (selector && selector !== '') {
				const wrappedHandler = (e) => {
					if (!e.target) return;
					const el = e.target.closest(selector);

					if (el) {
						const newEvent = Object.create(e, {
							target: {
								value: el
							}
						});
						eventHandler.call(el, newEvent);
					}
				};

				el.addEventListener(eventName, wrappedHandler);

				return wrappedHandler;
			} else {
				const wrappedHandler = (e) => {
					eventHandler.call(el, e);
				};

				el.addEventListener(eventName, wrappedHandler);

				return wrappedHandler;
			}
		}

		// Build surrogated pairs like 1F3F4-E0067-E0062-E0077-E006C-E0073-E007F
		function toCodePoint(code) {
			let _code = [];

			for (const codePoint of code) {
				_code.push(codePoint.codePointAt(0).toString(16));
			}

			return _code.join('-').toLowerCase();
		}

		function buildImageSrc(opts, code) {
			return opts.base + opts.folder + '/' + toCodePoint(code) + opts.ext;
		}

		function updatePosition(caller, dropdownContainer) {
			const emojiContainerStyles = getComputedStyle(dropdownContainer);

			if (parseInt(emojiContainerStyles.right, 10) < 0) {
				dropdownContainer.style.right = '0px';
				dropdownContainer.style.left = 'auto';
			} else {
				dropdownContainer.style.left = caller.offsetLeft + 'px';
				dropdownContainer.style.right = '';
			}

			dropdownContainer.style.top = caller.offsetTop + caller.offsetHeight + 'px';
		}

		this.init = function () {
			const commands = this.commands,
				opts = this.opts;

			if (opts && opts.emoji.enable) {
				commands.emoji = utils.extend(commands.emoji || {}, {
					_dropDown: function (editor, caller, callback) {
						const excludeGroups = opts.emoji.excludeGroups ? opts.emoji.excludeGroups.split(',') : [],
							excludeSubgroups = opts.emoji.excludeSubgroups ? opts.emoji.excludeSubgroups.split(',') : [],
							excludeEmojis = opts.emoji.excludeEmojis ? opts.emoji.excludeEmojis.split(',') : [];
						// Get an element on each command button click
						let dropdownContainer = document.querySelector('.sceditor-emoji');
						let content = document.querySelector('.emojis-dd-container');

						editor.closeDropDown();

						if (dropdownContainer === null) {
							dropdownContainer = document.createElement('div');
							content = document.createElement('div');
							let html = '';

							dropdownContainer.classList.add('sceditor-dropdown', 'sceditor-emoji');
							content.classList.add('emojis-dd-container');
							html += '<div class="emojis-tabs">' +
								'<ul class="nav nav-pills">';

								// Tabs list
								for (const [key] of Object.entries(list)) {
									if (excludeGroups.indexOf(key) !== -1) {
										continue;
									}

									html += '<li class="nav-item"><a href="#" class="nav-link">' + editor._(key) + '</a></li>';
								}

								html += '</ul>' +
							'<div class="emojis-list">';

							for (const [key, value] of Object.entries(list)) {
								if (excludeGroups.indexOf(key) !== -1) {
									continue;
								}

								html += '<div class="emoji-chars">';

								if (opts.emoji.groupTitle !== false) {
									html += '<span class="group-title" id="' + key.toLowerCase().replace('-', '_') + '">' + editor._(key) + '</span>';
								}

								for (const [index, values] of Object.entries(value)) {
									if (excludeSubgroups.indexOf(index) !== -1) {
										continue;
									}

									html += '<div class="emojis">';

									if (opts.emoji.subgroupTitle !== false) {
										html += '<span class="subgroup-title" id="' + index.toLowerCase().replace('-', '_') + '">' + editor._(index) + '</span>';
									}

									const emojis = values.split(','),
										totalEl = emojis.length;

									// Emojis list
									for (let i = 0; i < totalEl; i++) {
										if (values === '') {
											continue;
										}

										if (excludeEmojis.indexOf(emojis[i]) !== -1) {
											continue;
										}

										// Emoji code can be like this: 1F3F4-E0067-E0062-E0077-E006C-E0073-E007F
										if (emojis[i].indexOf('-')) {
											const _emojis = emojis[i].split('-').map((value) => '0x' + value);

											html += '<i>' + String.fromCodePoint.apply(String, _emojis) + '</i>';
										} else {
											html += '<i>' + String.fromCodePoint('0x' + emojis[i]) + '</i>';
										}
									}

									html += '</div>';
								}

								html += '</div>';
							}

								html += '</div>' +
							'</div>';

							content.innerHTML = html;
							content.replaceWith(dropdownContainer);
							dropdownContainer.appendChild( content);
							editorContainer.appendChild(dropdownContainer);

							if (opts.emoji.twemoji && twemoji) {
								addEventListener(editorContainer, 'click', 'i img', function () {
									callback(this.alt);
								});
								twemoji.parse(dropdownContainer, opts.emoji.twemoji);
							} else {
								addEventListener(editorContainer, 'click', 'i', function () {
									callback(this.innerText);

									if (!!opts.emoji.closeAfterSelect) {
										dropdownContainer.style.display = 'none';
										updatePosition(caller, dropdownContainer);
									}
								});
							}

							tabs(document.querySelector('.emojis-tabs'));
						} else {
							// Check if dropdown is visible
							if (!!(dropdownContainer.offsetWidth || dropdownContainer.offsetHeight || dropdownContainer.getClientRects().length)) {
								dropdownContainer.style.display = 'none';
							} else {
								dropdownContainer.style.display = 'block';
							}
						}

						// Try to adjust emojis dropdown menu height
						const emojiContainerStyles = getComputedStyle(dropdownContainer);

						// Dropdown height = editor height - dwopdown margins - borders - (button height + offsetTop)
						dropdownContainer.style.height = (editor.height() - parseInt(emojiContainerStyles.paddingTop, 10) - parseInt(emojiContainerStyles.paddingBottom, 10) - parseInt(emojiContainerStyles.borderTop, 10) - parseInt(emojiContainerStyles.borderBottom, 10)) - (caller.offsetTop + caller.offsetHeight) + 'px';
						content.style.height = dropdownContainer.style.height;
						// Set initial position. Required.
						dropdownContainer.style.top = caller.offsetTop + caller.offsetHeight + 'px';
						dropdownContainer.style.left = caller.offsetLeft + 'px';

						updatePosition(caller, dropdownContainer);

						window.addEventListener('resize', function() {
							if (!!(dropdownContainer.offsetWidth || dropdownContainer.offsetHeight || dropdownContainer.getClientRects().length)) {
								dropdownContainer.style.display = 'none';
								updatePosition(caller, dropdownContainer);
							}
						});
					},
					exec: function (caller) {
						const editor = this;

						commands.emoji._dropDown(editor, caller, function (code) {
							if (editor.opts.emoji.twemoji && twemoji) {
								//editor.wysiwygEditorInsertHtml('<img class="emoji" src="' + buildImageSrc(editor.opts.emoji.twemoji, code) + '" alt="' + code + '">');
								editor.wysiwygEditorInsertHtml('<span class="emoji-char-bg" style="background-image: url(' + buildImageSrc(editor.opts.emoji.twemoji, code) + ');">&nbsp;</span>');
							} else {
								editor.wysiwygEditorInsertHtml('<span class="emoji-char">' + code + '</span>', null, true);
							}

							/*const rangeHelper = editor.getRangeHelper(),
								range = rangeHelper.cloneSelected();

							if ('selectNodeContents' in range) {
								const bodyChildren = editor.getBody().children;

								range.selectNodeContents(bodyChildren[bodyChildren.length - 1]);
								range.collapse(false);
								rangeHelper.selectRange(range);
							}*/
						});
					},
					txtExec: function (caller) {
						const editor = this;

						commands.emoji._dropDown(editor, caller, function (code) {
							if (editor.opts.format === 'bbcode') {
								editor.insertText('[emoji=' + toCodePoint(code) + ']');
							} else {
								editor.insertText('<span class="emoji-char">' + code + '</span>');
							}
						});
					},
					tooltip: 'Insert emoji'
				});
			}
		};
	};
}(sceditor));

document.addEventListener('DOMContentLoaded', function () {
	sceditor.icons.monocons.icons.emoji = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 36 36"><circle fill="#FFCC4D" cx="18" cy="18" r="18"/><path fill="#664500" d="M18 21c-3.623 0-6.027-.422-9-1-.679-.131-2 0-2 2 0 4 4.595 9 11 9 6.404 0 11-5 11-9 0-2-1.321-2.132-2-2-2.973.578-5.377 1-9 1z"/><path fill="#FFF" d="M9 22s3 1 9 1 9-1 9-1-2 4-9 4-9-4-9-4z"/><ellipse fill="#664500" cx="12" cy="13.5" rx="2.5" ry="3.5"/><ellipse fill="#664500" cx="24" cy="13.5" rx="2.5" ry="3.5"/></svg>';

	if (sceditor.formats.bbcode) {
		sceditor.formats.bbcode.set('emoji', {
			tags: {
				'span': {
					'class': ['emoji-char']
				}
			},
			quoteType: sceditor.BBCodeParser.QuoteType.never,
			format: function (element, content) {
				//let code = element.querySelector('span.emoji-char');

				return '[emoji]';
			},
			html: function (token, attrs, content) {
				return '<span class="emoji-char">' + content + '</span>';
			}
		});
	}
});
