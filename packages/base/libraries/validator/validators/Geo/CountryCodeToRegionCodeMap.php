<?php
namespace packages\base\Validator\Geo;

/**
 * The country codes is based on:
 * 		https://github.com/giggsey/libphonenumber-for-php
 * that actually based on:
 * 		https://github.com/google/libphonenumber
 *
 * @see https://github.com/giggsey/libphonenumber-for-php/raw/master/src/CountryCodeToRegionCodeMap.php
 *
 */
class CountryCodeToRegionCodeMap {

	public static $CC2RMap = array(
		1 => array(
			'US', 'AG', 'AI', 'AS','BB',
			'BM', 'BS', 'CA', 'DM', 'DO',
			'GD', 'GU', 'JM', 'KN', 'KY',
			'LC', 'MP', 'MS', 'PR', 'SX',
			'TC', 'TT', 'VC', 'VG', 'VI'
		),
		7 => array(
			'RU', 'KZ'
		),
		20 => array('EG'),
		27 => array('ZA'),
		30 => array('GR'),
		31 => array('NL'),
		32 => array('BE'),
		33 => array('FR'),
		34 => array('ES'),
		36 => array('HU'),
		39 => array(
			'IT', 'VA'
		),
		40 => array('RO'),
		41 => array('CH'),
		43 => array('AT'),
		44 => array(
			'GB', 'GG', 'IM', 'JE'
		),
		45 => array('DK'),
		46 => array('SE'),
		47 => array(
			'NO', 'SJ'
		),
		48 => array('PL'),
		49 => array('DE'),
		51 => array('PE'),
		52 => array('MX'),
		53 => array('CU'),
		54 => array('AR'),
		55 => array('BR'),
		56 => array('CL'),
		57 => array('CO'),
		58 => array('VE'),
		60 => array('MY'),
		61 => array(
			'AU', 'CC', 'CX'
		),
		62 => array('ID'),
		63 => array('PH'),
		64 => array('NZ'),
		65 => array('SG'),
		66 => array('TH'),
		81 => array('JP'),
		82 => array('KR'),
		84 => array('VN'),
		86 => array('CN'),
		90 => array('TR'),
		91 => array('IN'),
		92 => array('PK'),
		93 => array('AF'),
		94 => array('LK'),
		95 => array('MM'),
		98 => array('IR'),
		211 => array('SS'),
		212 => array(
			'MA', 'EH'
		),
		213 => array('DZ'),
		216 => array('TN'),
		218 => array('LY'),
		220 => array('GM'),
		221 => array('SN'),
		222 => array('MR'),
		223 => array('ML'),
		224 => array('GN'),
		225 => array('CI'),
		226 => array('BF'),
		227 => array('NE'),
		228 => array('TG'),
		229 => array('BJ'),
		230 => array('MU'),
		231 => array('LR'),
		232 => array('SL'),
		233 => array('GH'),
		234 => array('NG'),
		235 => array('TD'),
		236 => array('CF'),
		237 => array('CM'),
		238 => array('CV'),
		239 => array('ST'),
		240 => array('GQ'),
		241 => array('GA'),
		242 => array('CG'),
		243 => array('CD'),
		244 => array('AO'),
		245 => array('GW'),
		246 => array('IO'),
		247 => array('AC'),
		248 => array('SC'),
		249 => array('SD'),
		250 => array('RW'),
		251 => array('ET'),
		252 => array('SO'),
		253 => array('DJ'),
		254 => array('KE'),
		255 => array('TZ'),
		256 => array('UG'),
		257 => array('BI'),
		258 => array('MZ'),
		260 => array('ZM'),
		261 => array('MG'),
		262 => array(
			'RE', 'YT'
		),
		263 => array('ZW'),
		264 => array('NA'),
		265 => array('MW'),
		266 => array('LS'),
		267 => array('BW'),
		268 => array('SZ'),
		269 => array('KM'),
		290 => array(
			'SH', 'TA'
		),
		291 => array('ER'),
		297 => array('AW'),
		298 => array('FO'),
		299 => array('GL'),
		350 => array('GI'),
		351 => array('PT'),
		352 => array('LU'),
		353 => array('IE'),
		354 => array('IS'),
		355 => array('AL'),
		356 => array('MT'),
		357 => array('CY'),
		358 => array(
			'FI', 'AX'
		),
		359 => array('BG'),
		370 => array('LT'),
		371 => array('LV'),
		372 => array('EE'),
		373 => array('MD'),
		374 => array('AM'),
		375 => array('BY'),
		376 => array('AD'),
		377 => array('MC'),
		378 => array('SM'),
		380 => array('UA'),
		381 => array('RS'),
		382 => array('ME'),
		383 => array('XK'),
		385 => array('HR'),
		386 => array('SI'),
		387 => array('BA'),
		389 => array('MK'),
		420 => array('CZ'),
		421 => array('SK'),
		423 => array('LI'),
		500 => array('FK'),
		501 => array('BZ'),
		502 => array('GT'),
		503 => array('SV'),
		504 => array('HN'),
		505 => array('NI'),
		506 => array('CR'),
		507 => array('PA'),
		508 => array('PM'),
		509 => array('HT'),
		590 => array(
			'GP', 'BL', 'MF'
		),
		591 => array('BO'),
		592 => array('GY'),
		593 => array('EC'),
		594 => array('GF'),
		595 => array('PY'),
		596 => array('MQ'),
		597 => array('SR'),
		598 => array('UY'),
		599 => array(
			'CW', 'BQ'
		),
		670 => array('TL'),
		672 => array('NF'),
		673 => array('BN'),
		674 => array('NR'),
		675 => array('PG'),
		676 => array('TO'),
		677 => array('SB'),
		678 => array('VU'),
		679 => array('FJ'),
		680 => array('PW'),
		681 => array('WF'),
		682 => array('CK'),
		683 => array('NU'),
		685 => array('WS'),
		686 => array('KI'),
		687 => array('NC'),
		688 => array('TV'),
		689 => array('PF'),
		690 => array('TK'),
		691 => array('FM'),
		692 => array('MH'),
		850 => array('KP'),
		852 => array('HK'),
		853 => array('MO'),
		855 => array('KH'),
		856 => array('LA'),
		880 => array('BD'),
		886 => array('TW'),
		960 => array('MV'),
		961 => array('LB'),
		962 => array('JO'),
		963 => array('SY'),
		964 => array('IQ'),
		965 => array('KW'),
		966 => array('SA'),
		967 => array('YE'),
		968 => array('OM'),
		970 => array('PS'),
		971 => array('AE'),
		972 => array('IL'),
		973 => array('BH'),
		974 => array('QA'),
		975 => array('BT'),
		976 => array('MN'),
		977 => array('NP'),
		992 => array('TJ'),
		993 => array('TM'),
		994 => array('AZ'),
		995 => array('GE'),
		996 => array('KG'),
		998 => array('UZ'),
	);

	private static $regionCodeToCountryCodeMap = array();

	public static function regionCodeToCountryCode(): array {
		if (empty(self::$regionCodeToCountryCodeMap)) {
			foreach (self::$CC2RMap as $countryCode => $regions) {
				foreach ($regions as $region) {
					self::$regionCodeToCountryCodeMap[$region] = $countryCode;
				}
			}
		}
		return self::$regionCodeToCountryCodeMap;
	}

}
