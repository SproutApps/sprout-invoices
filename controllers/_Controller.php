<?php

/**
 * A base class from which all other controllers should be derived
 *
 * @package Sprout_Invoices
 * @subpackage Controller
 */
abstract class SI_Controller extends Sprout_Invoices {
	const MESSAGE_STATUS_INFO = 'info';
	const MESSAGE_STATUS_ERROR = 'error';
	const MESSAGE_META_KEY = 'sa_messages';
	const PRIVATE_NOTES_TYPE = 'sa_private_notes';
	const CRON_HOOK = 'si_cron';
	const DAILY_CRON_HOOK = 'si_daily_cron';
	const DEFAULT_TEMPLATE_DIRECTORY = 'sa_templates';
	const SETTINGS_PAGE = 'settings';
	const NONCE = 'sprout_invoices_controller_nonce';

	private static $messages = array();
	private static $query_vars = array();
	private static $template_path = self::DEFAULT_TEMPLATE_DIRECTORY;

	protected static $countries = array(
		'AF' => "Afghanistan",
		'AX' => "Aland Islands",
		'AL' => "Albania",
		'DZ' => "Algeria",
		'AS' => "American Samoa",
		'AD' => "Andorra",
		'AO' => "Angola",
		'AI' => "Anguilla",
		'AQ' => "Antarctica",
		'AG' => "Antigua and Barbuda",
		'AR' => "Argentina",
		'AM' => "Armenia",
		'AW' => "Aruba",
		'AU' => "Australia",
		'AT' => "Austria",
		'AZ' => "Azerbaijan",
		'BS' => "Bahamas",
		'BH' => "Bahrain",
		'BD' => "Bangladesh",
		'BB' => "Barbados",
		'BY' => "Belarus",
		'BE' => "Belgium",
		'BZ' => "Belize",
		'BJ' => "Benin",
		'BM' => "Bermuda",
		'BT' => "Bhutan",
		'BO' => "Bolivia, Plurinational State of",
		'BQ' => "Bonaire, Sint Eustatius and Saba",
		'BA' => "Bosnia and Herzegovina",
		'BW' => "Botswana",
		'BV' => "Bouvet Island",
		'BR' => "Brazil",
		'IO' => "British Indian Ocean Territory",
		'BN' => "Brunei Darussalam",
		'BG' => "Bulgaria",
		'BF' => "Burkina Faso",
		'BI' => "Burundi",
		'KH' => "Cambodia",
		'CM' => "Cameroon",
		'CA' => "Canada",
		'CV' => "Cape Verde",
		'KY' => "Cayman Islands",
		'CF' => "Central African Republic",
		'TD' => "Chad",
		'CL' => "Chile",
		'CN' => "China",
		'CX' => "Christmas Island",
		'CC' => "Cocos (Keeling) Islands",
		'CO' => "Colombia",
		'KM' => "Comoros",
		'CG' => "Congo",
		'CD' => "Congo, The Democratic Republic of the",
		'CK' => "Cook Islands",
		'CR' => "Costa Rica",
		'CI' => "Cote D'ivoire",
		'HR' => "Croatia",
		'CU' => "Cuba",
		'CW' => "Curacao",
		'CY' => "Cyprus",
		'CZ' => "Czech Republic",
		'DK' => "Denmark",
		'DJ' => "Djibouti",
		'DM' => "Dominica",
		'DO' => "Dominican Republic",
		'EC' => "Ecuador",
		'EG' => "Egypt",
		'SV' => "El Salvador",
		'GQ' => "Equatorial Guinea",
		'ER' => "Eritrea",
		'EE' => "Estonia",
		'ET' => "Ethiopia",
		'FK' => "Falkland Islands (Malvinas)",
		'FO' => "Faroe Islands",
		'FJ' => "Fiji",
		'FI' => "Finland",
		'FR' => "France",
		'GF' => "French Guiana",
		'PF' => "French Polynesia",
		'TF' => "French Southern Territories",
		'GA' => "Gabon",
		'GM' => "Gambia",
		'GE' => "Georgia",
		'DE' => "Germany",
		'GH' => "Ghana",
		'GI' => "Gibraltar",
		'GR' => "Greece",
		'GL' => "Greenland",
		'GD' => "Grenada",
		'GP' => "Guadeloupe",
		'GU' => "Guam",
		'GT' => "Guatemala",
		'GG' => "Guernsey",
		'GN' => "Guinea",
		'GW' => "Guinea-Bissau",
		'GY' => "Guyana",
		'HT' => "Haiti",
		'HM' => "Heard Island and McDonald Islands",
		'VA' => "Holy See (Vatican City State)",
		'HN' => "Honduras",
		'HK' => "Hong Kong",
		'HU' => "Hungary",
		'IS' => "Iceland",
		'IN' => "India",
		'ID' => "Indonesia",
		'IR' => "Iran, Islamic Republic of",
		'IQ' => "Iraq",
		'IE' => "Ireland",
		'IM' => "Isle of Man",
		'IL' => "Israel",
		'IT' => "Italy",
		'JM' => "Jamaica",
		'JP' => "Japan",
		'JE' => "Jersey",
		'JO' => "Jordan",
		'KZ' => "Kazakhstan",
		'KE' => "Kenya",
		'KI' => "Kiribati",
		'KP' => "Korea, Democratic People's Republic of",
		'KR' => "Korea, Republic of",
		'KW' => "Kuwait",
		'KG' => "Kyrgyzstan",
		'LA' => "Lao People's Democratic Republic",
		'LV' => "Latvia",
		'LB' => "Lebanon",
		'LS' => "Lesotho",
		'LR' => "Liberia",
		'LY' => "Libyan Arab Jamahiriya",
		'LI' => "Liechtenstein",
		'LT' => "Lithuania",
		'LU' => "Luxembourg",
		'MO' => "Macao",
		'MK' => "Macedonia, The Former Yugoslav Republic of",
		'MG' => "Madagascar",
		'MW' => "Malawi",
		'MY' => "Malaysia",
		'MV' => "Maldives",
		'ML' => "Mali",
		'MT' => "Malta",
		'MH' => "Marshall Islands",
		'MQ' => "Martinique",
		'MR' => "Mauritania",
		'MU' => "Mauritius",
		'YT' => "Mayotte",
		'MX' => "Mexico",
		'FM' => "Micronesia, Federated States of",
		'MD' => "Moldova, Republic of",
		'MC' => "Monaco",
		'MN' => "Mongolia",
		'ME' => "Montenegro",
		'MS' => "Montserrat",
		'MA' => "Morocco",
		'MZ' => "Mozambique",
		'MM' => "Myanmar",
		'NA' => "Namibia",
		'NR' => "Nauru",
		'NP' => "Nepal",
		'NL' => "Netherlands",
		'NC' => "New Caledonia",
		'NZ' => "New Zealand",
		'NI' => "Nicaragua",
		'NE' => "Niger",
		'NG' => "Nigeria",
		'NU' => "Niue",
		'NF' => "Norfolk Island",
		'MP' => "Northern Mariana Islands",
		'NO' => "Norway",
		'OM' => "Oman",
		'PK' => "Pakistan",
		'PW' => "Palau",
		'PS' => "Palestinian Territory, Occupied",
		'PA' => "Panama",
		'PG' => "Papua New Guinea",
		'PY' => "Paraguay",
		'PE' => "Peru",
		'PH' => "Philippines",
		'PN' => "Pitcairn",
		'PL' => "Poland",
		'PT' => "Portugal",
		'PR' => "Puerto Rico",
		'QA' => "Qatar",
		'RE' => "Reunion",
		'RO' => "Romania",
		'RU' => "Russian Federation",
		'RW' => "Rwanda",
		'BL' => "Saint Barthelemy",
		'SH' => "Saint Helena, Ascension and Tristan Da Cunha",
		'KN' => "Saint Kitts and Nevis",
		'LC' => "Saint Lucia",
		'MF' => "Saint Martin (French Part)",
		'PM' => "Saint Pierre and Miquelon",
		'VC' => "Saint Vincent and the Grenadines",
		'WS' => "Samoa",
		'SM' => "San Marino",
		'ST' => "Sao Tome and Principe",
		'SA' => "Saudi Arabia",
		'SN' => "Senegal",
		'RS' => "Serbia",
		'SC' => "Seychelles",
		'SL' => "Sierra Leone",
		'SG' => "Singapore",
		'SX' => "Sint Maarten (Dutch Part)",
		'SK' => "Slovakia",
		'SI' => "Slovenia",
		'SB' => "Solomon Islands",
		'SO' => "Somalia",
		'ZA' => "South Africa",
		'GS' => "South Georgia and the South Sandwich Islands",
		'ES' => "Spain",
		'LK' => "Sri Lanka",
		'SD' => "Sudan",
		'SR' => "Suriname",
		'SJ' => "Svalbard and Jan Mayen",
		'SZ' => "Swaziland",
		'SE' => "Sweden",
		'CH' => "Switzerland",
		'SY' => "Syrian Arab Republic",
		'TW' => "Taiwan, Province of China",
		'TJ' => "Tajikistan",
		'TZ' => "Tanzania, United Republic of",
		'TH' => "Thailand",
		'TL' => "Timor-Leste",
		'TG' => "Togo",
		'TK' => "Tokelau",
		'TO' => "Tonga",
		'TT' => "Trinidad and Tobago",
		'TN' => "Tunisia",
		'TR' => "Turkey",
		'TM' => "Turkmenistan",
		'TC' => "Turks and Caicos Islands",
		'TV' => "Tuvalu",
		'UG' => "Uganda",
		'UA' => "Ukraine",
		'AE' => "United Arab Emirates",
		'GB' => "United Kingdom",
		'US' => "United States",
		'UM' => "United States Minor Outlying Islands",
		'UY' => "Uruguay",
		'UZ' => "Uzbekistan",
		'VU' => "Vanuatu",
		'VE' => "Venezuela,
		Bolivarian Republic of",
		'VN' => "Viet Nam",
		'VG' => "Virgin Islands, British",
		'VI' => "Virgin Islands, U.S.",
		'WF' => "Wallis and Futuna",
		'EH' => "Western Sahara",
		'YE' => "Yemen",
		'ZM' => "Zambia",
		'ZW' => "Zimbabwe"
	);

	protected static $states = array(
		'AL' => 'Alabama',
		'AK' => 'Alaska',
		'AS' => 'American Samoa',
		'AZ' => 'Arizona',
		'AR' => 'Arkansas',
		'AE' => 'Armed Forces - Europe',
		'AP' => 'Armed Forces - Pacific',
		'AA' => 'Armed Forces - USA/Canada',
		'CA' => 'California',
		'CO' => 'Colorado',
		'CT' => 'Connecticut',
		'DE' => 'Delaware',
		'DC' => 'District of Columbia',
		'FM' => 'Federated States of Micronesia',
		'FL' => 'Florida',
		'GA' => 'Georgia',
		'GU' => 'Guam',
		'HI' => 'Hawaii',
		'ID' => 'Idaho',
		'IL' => 'Illinois',
		'IN' => 'Indiana',
		'IA' => 'Iowa',
		'KS' => 'Kansas',
		'KY' => 'Kentucky',
		'LA' => 'Louisiana',
		'ME' => 'Maine',
		'MH' => 'Marshall Islands',
		'MD' => 'Maryland',
		'MA' => 'Massachusetts',
		'MI' => 'Michigan',
		'MN' => 'Minnesota',
		'MS' => 'Mississippi',
		'MO' => 'Missouri',
		'MT' => 'Montana',
		'NE' => 'Nebraska',
		'NV' => 'Nevada',
		'NH' => 'New Hampshire',
		'NJ' => 'New Jersey',
		'NM' => 'New Mexico',
		'NY' => 'New York',
		'NC' => 'North Carolina',
		'ND' => 'North Dakota',
		'OH' => 'Ohio',
		'OK' => 'Oklahoma',
		'OR' => 'Oregon',
		'PA' => 'Pennsylvania',
		'PR' => 'Puerto Rico',
		'RI' => 'Rhode Island',
		'SC' => 'South Carolina',
		'SD' => 'South Dakota',
		'TN' => 'Tennessee',
		'TX' => 'Texas',
		'UT' => 'Utah',
		'VT' => 'Vermont',
		'VI' => 'Virgin Islands',
		'VA' => 'Virginia',
		'WA' => 'Washington',
		'WV' => 'West Virginia',
		'WI' => 'Wisconsin',
		'WY' => 'Wyoming',
		'canada' => '== Canadian Provinces ==',
		'AB' => 'Alberta',
		'BC' => 'British Columbia',
		'MB' => 'Manitoba',
		'NB' => 'New Brunswick',
		'NF' => 'Newfoundland',
		'NT' => 'Northwest Territories',
		'NS' => 'Nova Scotia',
		'NU' => 'Nunavut',
		'ON' => 'Ontario',
		'PE' => 'Prince Edward Island',
		'QC' => 'Quebec',
		'SK' => 'Saskatchewan',
		'YT' => 'Yukon Territory',
		'uk' => '== UK ==',
		'Avon' => 'Avon',
		'Bedfordshire' => 'Bedfordshire',
		'Berkshire' => 'Berkshire',
		'Borders' => 'Borders',
		'Buckinghamshire' => 'Buckinghamshire',
		'Cambridgeshire' => 'Cambridgeshire',
		'Central' => 'Central',
		'Cheshire' => 'Cheshire',
		'Cleveland' => 'Cleveland',
		'Clwyd' => 'Clwyd',
		'Cornwall' => 'Cornwall',
		'County Antrim' => 'County Antrim',
		'County Armagh' => 'County Armagh',
		'County Down' => 'County Down',
		'County Fermanagh' => 'County Fermanagh',
		'County Londonderry' => 'County Londonderry',
		'County Tyrone' => 'County Tyrone',
		'Cumbria' => 'Cumbria',
		'Derbyshire' => 'Derbyshire',
		'Devon' => 'Devon',
		'Dorset' => 'Dorset',
		'Dumfries and Galloway' => 'Dumfries and Galloway',
		'Durham' => 'Durham',
		'Dyfed' => 'Dyfed',
		'East Sussex' => 'East Sussex',
		'Essex' => 'Essex',
		'Fife' => 'Fife',
		'Gloucestershire' => 'Gloucestershire',
		'Grampian' => 'Grampian',
		'Greater Manchester' => 'Greater Manchester',
		'Gwent' => 'Gwent',
		'Gwynedd County' => 'Gwynedd County',
		'Hampshire' => 'Hampshire',
		'Herefordshire' => 'Herefordshire',
		'Hertfordshire' => 'Hertfordshire',
		'Highlands and Islands' => 'Highlands and Islands',
		'Humberside' => 'Humberside',
		'Isle of Wight' => 'Isle of Wight',
		'Kent' => 'Kent',
		'Lancashire' => 'Lancashire',
		'Leicestershire' => 'Leicestershire',
		'Lincolnshire' => 'Lincolnshire',
		'London' => 'London',
		'Lothian' => 'Lothian',
		'Merseyside' => 'Merseyside',
		'Mid Glamorgan' => 'Mid Glamorgan',
		'Norfolk' => 'Norfolk',
		'North Yorkshire' => 'North Yorkshire',
		'Northamptonshire' => 'Northamptonshire',
		'Northumberland' => 'Northumberland',
		'Nottinghamshire' => 'Nottinghamshire',
		'Oxfordshire' => 'Oxfordshire',
		'Powys' => 'Powys',
		'Rutland' => 'Rutland',
		'Shropshire' => 'Shropshire',
		'Somerset' => 'Somerset',
		'South Glamorgan' => 'South Glamorgan',
		'South Yorkshire' => 'South Yorkshire',
		'Staffordshire' => 'Staffordshire',
		'Strathclyde' => 'Strathclyde',
		'Suffolk' => 'Suffolk',
		'Surrey' => 'Surrey',
		'Tayside' => 'Tayside',
		'Tyne and Wear' => 'Tyne and Wear',
		'Warwickshire' => 'Warwickshire',
		'West Glamorgan' => 'West Glamorgan',
		'West Midlands' => 'West Midlands',
		'West Sussex' => 'West Sussex',
		'West Yorkshire' => 'West Yorkshire',
		'Wiltshire' => 'Wiltshire',
		'Worcestershire' => 'Worcestershire',
	);

	protected static $grouped_states = array(
		'United States' => array(
			'AL' => 'Alabama',
			'AK' => 'Alaska',
			'AS' => 'American Samoa',
			'AZ' => 'Arizona',
			'AR' => 'Arkansas',
			'AE' => 'Armed Forces - Europe',
			'AP' => 'Armed Forces - Pacific',
			'AA' => 'Armed Forces - USA/Canada',
			'CA' => 'California',
			'CO' => 'Colorado',
			'CT' => 'Connecticut',
			'DE' => 'Delaware',
			'DC' => 'District of Columbia',
			'FM' => 'Federated States of Micronesia',
			'FL' => 'Florida',
			'GA' => 'Georgia',
			'GU' => 'Guam',
			'HI' => 'Hawaii',
			'ID' => 'Idaho',
			'IL' => 'Illinois',
			'IN' => 'Indiana',
			'IA' => 'Iowa',
			'KS' => 'Kansas',
			'KY' => 'Kentucky',
			'LA' => 'Louisiana',
			'ME' => 'Maine',
			'MH' => 'Marshall Islands',
			'MD' => 'Maryland',
			'MA' => 'Massachusetts',
			'MI' => 'Michigan',
			'MN' => 'Minnesota',
			'MS' => 'Mississippi',
			'MO' => 'Missouri',
			'MT' => 'Montana',
			'NE' => 'Nebraska',
			'NV' => 'Nevada',
			'NH' => 'New Hampshire',
			'NJ' => 'New Jersey',
			'NM' => 'New Mexico',
			'NY' => 'New York',
			'NC' => 'North Carolina',
			'ND' => 'North Dakota',
			'OH' => 'Ohio',
			'OK' => 'Oklahoma',
			'OR' => 'Oregon',
			'PA' => 'Pennsylvania',
			'PR' => 'Puerto Rico',
			'RI' => 'Rhode Island',
			'SC' => 'South Carolina',
			'SD' => 'South Dakota',
			'TN' => 'Tennessee',
			'TX' => 'Texas',
			'UT' => 'Utah',
			'VT' => 'Vermont',
			'VI' => 'Virgin Islands',
			'VA' => 'Virginia',
			'WA' => 'Washington',
			'WV' => 'West Virginia',
			'WI' => 'Wisconsin',
			'WY' => 'Wyoming',
		),
		'Canadian Provinces' => array(
			'AB' => 'Alberta',
			'BC' => 'British Columbia',
			'MB' => 'Manitoba',
			'NB' => 'New Brunswick',
			'NF' => 'Newfoundland',
			'NT' => 'Northwest Territories',
			'NS' => 'Nova Scotia',
			'NU' => 'Nunavut',
			'ON' => 'Ontario',
			'PE' => 'Prince Edward Island',
			'QC' => 'Quebec',
			'SK' => 'Saskatchewan',
			'YT' => 'Yukon Territory',
		),
		'UK' => array(
			'Avon' => 'Avon',
			'Bedfordshire' => 'Bedfordshire',
			'Berkshire' => 'Berkshire',
			'Borders' => 'Borders',
			'Buckinghamshire' => 'Buckinghamshire',
			'Cambridgeshire' => 'Cambridgeshire',
			'Central' => 'Central',
			'Cheshire' => 'Cheshire',
			'Cleveland' => 'Cleveland',
			'Clwyd' => 'Clwyd',
			'Cornwall' => 'Cornwall',
			'County Antrim' => 'County Antrim',
			'County Armagh' => 'County Armagh',
			'County Down' => 'County Down',
			'County Fermanagh' => 'County Fermanagh',
			'County Londonderry' => 'County Londonderry',
			'County Tyrone' => 'County Tyrone',
			'Cumbria' => 'Cumbria',
			'Derbyshire' => 'Derbyshire',
			'Devon' => 'Devon',
			'Dorset' => 'Dorset',
			'Dumfries and Galloway' => 'Dumfries and Galloway',
			'Durham' => 'Durham',
			'Dyfed' => 'Dyfed',
			'East Sussex' => 'East Sussex',
			'Essex' => 'Essex',
			'Fife' => 'Fife',
			'Gloucestershire' => 'Gloucestershire',
			'Grampian' => 'Grampian',
			'Greater Manchester' => 'Greater Manchester',
			'Gwent' => 'Gwent',
			'Gwynedd County' => 'Gwynedd County',
			'Hampshire' => 'Hampshire',
			'Herefordshire' => 'Herefordshire',
			'Hertfordshire' => 'Hertfordshire',
			'Highlands and Islands' => 'Highlands and Islands',
			'Humberside' => 'Humberside',
			'Isle of Wight' => 'Isle of Wight',
			'Kent' => 'Kent',
			'Lancashire' => 'Lancashire',
			'Leicestershire' => 'Leicestershire',
			'Lincolnshire' => 'Lincolnshire',
			'Lothian' => 'Lothian',
			'Merseyside' => 'Merseyside',
			'Mid Glamorgan' => 'Mid Glamorgan',
			'Norfolk' => 'Norfolk',
			'North Yorkshire' => 'North Yorkshire',
			'Northamptonshire' => 'Northamptonshire',
			'Northumberland' => 'Northumberland',
			'Nottinghamshire' => 'Nottinghamshire',
			'Oxfordshire' => 'Oxfordshire',
			'Powys' => 'Powys',
			'Rutland' => 'Rutland',
			'Shropshire' => 'Shropshire',
			'Somerset' => 'Somerset',
			'South Glamorgan' => 'South Glamorgan',
			'South Yorkshire' => 'South Yorkshire',
			'Staffordshire' => 'Staffordshire',
			'Strathclyde' => 'Strathclyde',
			'Suffolk' => 'Suffolk',
			'Surrey' => 'Surrey',
			'Tayside' => 'Tayside',
			'Tyne and Wear' => 'Tyne and Wear',
			'Warwickshire' => 'Warwickshire',
			'West Glamorgan' => 'West Glamorgan',
			'West Midlands' => 'West Midlands',
			'West Sussex' => 'West Sussex',
			'West Yorkshire' => 'West Yorkshire',
			'Wiltshire' => 'Wiltshire',
			'Worcestershire' => 'Worcestershire',
		)

	);

	protected static $locales = array( 
		'Albanian (Albania)' => 'sq_AL',
		'Albanian' => 'sq',
		'Arabic (Algeria)' => 'ar_DZ',
		'Arabic (Bahrain)' => 'ar_BH',
		'Arabic (Egypt)' => 'ar_EG',
		'Arabic (Iraq)' => 'ar_IQ',
		'Arabic (Jordan)' => 'ar_JO',
		'Arabic (Kuwait)' => 'ar_KW',
		'Arabic (Lebanon)' => 'ar_LB',
		'Arabic (Libya)' => 'ar_LY',
		'Arabic (Morocco)' => 'ar_MA',
		'Arabic (Oman)' => 'ar_OM',
		'Arabic (Qatar)' => 'ar_QA',
		'Arabic (Saudi Arabia)' => 'ar_SA',
		'Arabic (Sudan)' => 'ar_SD',
		'Arabic (Syria)' => 'ar_SY',
		'Arabic (Tunisia)' => 'ar_TN',
		'Arabic (United Arab Emirates)' => 'ar_AE',
		'Arabic (Yemen)' => 'ar_YE',
		'Arabic' => 'ar',
		'Belarusian (Belarus)' => 'be_BY',
		'Belarusian' => 'be',
		'Bulgarian (Bulgaria)' => 'bg_BG',
		'Bulgarian' => 'bg',
		'Catalan (Spain)' => 'ca_ES',
		'Catalan' => 'ca',
		'Chinese (China)' => 'zh_CN',
		'Chinese (Hong Kong)' => 'zh_HK',
		'Chinese (Singapore)' => 'zh_SG',
		'Chinese (Taiwan)' => 'zh_TW',
		'Chinese' => 'zh',
		'Croatian (Croatia)' => 'hr_HR',
		'Croatian' => 'hr',
		'Czech (Czech Republic)' => 'cs_CZ',
		'Czech' => 'cs',
		'Danish (Denmark)' => 'da_DK',
		'Danish' => 'da',
		'Dutch (Belgium)' => 'nl_BE',
		'Dutch (Netherlands)' => 'nl_NL',
		'Dutch' => 'nl',
		'English (Australia)' => 'en_AU',
		'English (Canada)' => 'en_CA',
		'English (India)' => 'en_IN',
		'English (Ireland)' => 'en_IE',
		'English (Malta)' => 'en_MT',
		'English (New Zealand)' => 'en_NZ',
		'English (Philippines)' => 'en_PH',
		'English (Singapore)' => 'en_SG',
		'English (South Africa)' => 'en_ZA',
		'English (United Kingdom)' => 'en_GB',
		'English (United States)' => 'en_US',
		'English' => 'en',
		'Estonian (Estonia)' => 'et_EE',
		'Estonian' => 'et',
		'Finnish (Finland)' => 'fi_FI',
		'Finnish' => 'fi',
		'French (Belgium)' => 'fr_BE',
		'French (Canada)' => 'fr_CA',
		'French (France)' => 'fr_FR',
		'French (Luxembourg)' => 'fr_LU',
		'French (Switzerland)' => 'fr_CH',
		'French' => 'fr',
		'German (Austria)' => 'de_AT',
		'German (Germany)' => 'de_DE',
		'German (Luxembourg)' => 'de_LU',
		'German (Switzerland)' => 'de_CH',
		'German' => 'de',
		'Greek (Cyprus)' => 'el_CY',
		'Greek (Greece)' => 'el_GR',
		'Greek' => 'el',
		'Hebrew (Israel)' => 'iw_IL',
		'Hebrew' => 'iw',
		'Hindi (India)' => 'hi_IN',
		'Hungarian (Hungary)' => 'hu_HU',
		'Hungarian' => 'hu',
		'Icelandic (Iceland)' => 'is_IS',
		'Icelandic' => 'is',
		'Indonesian (Indonesia)' => 'in_ID',
		'Indonesian' => 'in',
		'Irish (Ireland)' => 'ga_IE',
		'Irish' => 'ga',
		'Italian (Italy)' => 'it_IT',
		'Italian (Switzerland)' => 'it_CH',
		'Italian' => 'it',
		'Japanese (Japan)' => 'ja_JP',
		'Japanese (Japan,JP)' => 'ja_JP_JP',
		'Japanese' => 'ja',
		'Korean (South Korea)' => 'ko_KR',
		'Korean' => 'ko',
		'Latvian (Latvia)' => 'lv_LV',
		'Latvian' => 'lv',
		'Lithuanian (Lithuania)' => 'lt_LT',
		'Lithuanian' => 'lt',
		'Macedonian (Macedonia)' => 'mk_MK',
		'Macedonian' => 'mk',
		'Malay (Malaysia)' => 'ms_MY',
		'Malay' => 'ms',
		'Maltese (Malta)' => 'mt_MT',
		'Maltese' => 'mt',
		'Norwegian (Norway)' => 'no_NO',
		'Norwegian (Norway,Nynorsk)' => 'no_NO_NY',
		'Norwegian' => 'no',
		'Polish (Poland)' => 'pl_PL',
		'Polish' => 'pl',
		'Portuguese (Brazil)' => 'pt_BR',
		'Portuguese (Portugal)' => 'pt_PT',
		'Portuguese' => 'pt',
		'Romanian (Romania)' => 'ro_RO',
		'Romanian' => 'ro',
		'Russian (Russia)' => 'ru_RU',
		'Russian' => 'ru',
		'Serbian (Bosnia and Herzegovina)' => 'sr_BA',
		'Serbian (Montenegro)' => 'sr_ME',
		'Serbian (Serbia and Montenegro)' => 'sr_CS',
		'Serbian (Serbia)' => 'sr_RS',
		'Serbian' => 'sr',
		'Slovak (Slovakia)' => 'sk_SK',
		'Slovak' => 'sk',
		'Slovenian (Slovenia)' => 'sl_SI',
		'Slovenian' => 'sl',
		'Spanish (Argentina)' => 'es_AR',
		'Spanish (Bolivia)' => 'es_BO',
		'Spanish (Chile)' => 'es_CL',
		'Spanish (Colombia)' => 'es_CO',
		'Spanish (Costa Rica)' => 'es_CR',
		'Spanish (Dominican Republic)' => 'es_DO',
		'Spanish (Ecuador)' => 'es_EC',
		'Spanish (El Salvador)' => 'es_SV',
		'Spanish (Guatemala)' => 'es_GT',
		'Spanish (Honduras)' => 'es_HN',
		'Spanish (Mexico)' => 'es_MX',
		'Spanish (Nicaragua)' => 'es_NI',
		'Spanish (Panama)' => 'es_PA',
		'Spanish (Paraguay)' => 'es_PY',
		'Spanish (Peru)' => 'es_PE',
		'Spanish (Puerto Rico)' => 'es_PR',
		'Spanish (Spain)' => 'es_ES',
		'Spanish (United States)' => 'es_US',
		'Spanish (Uruguay)' => 'es_UY',
		'Spanish (Venezuela)' => 'es_VE',
		'Spanish' => 'es',
		'Swedish (Sweden)' => 'sv_SE',
		'Swedish' => 'sv',
		'Thai (Thailand)' => 'th_TH',
		'Thai (Thailand,TH)' => 'th_TH_TH',
		'Thai' => 'th',
		'Turkish (Turkey)' => 'tr_TR',
		'Turkish' => 'tr',
		'Ukrainian (Ukraine)' => 'uk_UA',
		'Ukrainian' => 'uk',
		'Vietnamese (Vietnam)' => 'vi_VN',
		'Vietnamese' => 'vi' );


	public static function init() {
		if ( is_admin() ) {
		
			// On Activation
			add_action( 'si_plugin_activation_hook', array( __CLASS__, 'sprout_invoices_activated' ) );

			// clone notification
			add_action( 'admin_init', array( get_class(), 'maybe_clone_and_redirect' ) );
		}

		// Enqueue
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_resources' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'frontend_enqueue' ), 20 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue' ), 20 );
		
		// Cron
		add_filter( 'cron_schedules', array( __CLASS__, 'si_cron_schedule' ) );
		add_action( 'init', array( __CLASS__, 'set_schedule' ), 10, 0 );

		// Messages
		add_action( 'init', array( __CLASS__, 'load_messages' ), 0, 0 );
		add_action( 'loop_start', array( __CLASS__, 'do_loop_start' ), 10, 1 );

		// AJAX
		add_action( 'wp_ajax_si_display_messages', array( __CLASS__, 'display_messages' ) );
		add_action( 'wp_ajax_nopriv_si_display_messages', array( __CLASS__, 'display_messages' ) );

		add_action( 'wp_ajax_sa_create_private_note',  array( get_class(), 'maybe_create_private_note' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_sa_create_private_note',  array( get_class(), 'maybe_create_private_note' ), 10, 0 );
		add_action( 'wp_ajax_si_change_doc_status',  array( get_class(), 'maybe_change_status' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_si_change_doc_status',  array( get_class(), 'maybe_change_status' ), 10, 0 );

	}

	/**
	 * Template path for templates/views, default to 'invoices'.
	 * 
	 * @return string self::$template_path the folder
	 */
	public static function get_template_path() {
		return apply_filters( 'si_template_path', self::$template_path );
	}

	/**
	 * Fire actions based on plugin being updated.
	 * @return 
	 */
	public static function sprout_invoices_activated() {
		add_option( 'si_do_activation_redirect', TRUE );
		// Get the previous version number
		$si_version = get_option( 'si_current_version', self::SI_VERSION );
		if ( version_compare( $si_version, self::SI_VERSION, '<' ) ) { // If an upgrade create some hooks
			do_action( 'si_version_upgrade', $si_version );
			do_action( 'si_version_upgrade_'.$si_version );
		}
		// Set the new version number
		update_option( 'si_current_version', self::SI_VERSION );
	}



	public static function register_resources() {
		// admin js
		wp_register_script( 'si_admin', SI_URL . '/resources/admin/js/sprout_invoice.js', array( 'jquery', 'qtip', 'select2' ), self::SI_VERSION );

		// Item management
		wp_register_script( 'nestable', SI_URL . '/resources/admin/js/nestable.js', array( 'jquery' ), self::SI_VERSION );
		wp_register_script( 'sticky', SI_URL . '/resources/admin/js/sticky.js', array( 'jquery' ), self::SI_VERSION );
		wp_register_script( 'si_admin_est_and_invoices', SI_URL . '/resources/admin/js/est_and_invoices.js', array( 'jquery', 'nestable', 'sticky' ), self::SI_VERSION );
		wp_register_style( 'sprout_invoice_admin_css', SI_URL . '/resources/admin/css/sprout-invoice.css', array(), self::SI_VERSION  );

		// Redactor
		wp_register_script( 'redactor', SI_URL . '/resources/admin/plugins/redactor/redactor.min.js', array( 'jquery' ), self::SI_VERSION );
		wp_register_style( 'redactor', SI_URL . '/resources/admin/plugins/redactor/redactor.css', array(), self::SI_VERSION  );

		// Select2
		wp_register_style( 'select2_css', SI_URL . '/resources/admin/plugins/select2/select2.css', null, false, false );
		wp_register_script( 'select2', SI_URL . '/resources/admin/plugins/select2/select2.min.js', array( 'jquery' ), false, false );

		// qtip plugin
		wp_enqueue_style( 'qtip', SI_URL . '/resources/admin/plugins/qtip/jquery.qtip.min.css', null, false, false );
		wp_enqueue_script( 'qtip', SI_URL . '/resources/admin/plugins/qtip/jquery.qtip.min.js', array('jquery'), false, true );

		// dropdown plugin
		wp_enqueue_style( 'dropdown', SI_URL . '/resources/admin/plugins/dropdown/jquery.dropdown.css', null, false, false );
		wp_enqueue_script( 'dropdown', SI_URL . '/resources/admin/plugins/dropdown/jquery.dropdown.min.js', array('jquery'), false, true );

		// Templates
		wp_register_script( 'sprout_doc_scripts', SI_URL . '/resources/front-end/js/sprout-invoices.js', array( 'jquery', 'qtip' ), self::SI_VERSION );
		wp_register_style( 'sprout_doc_style', SI_URL . '/resources/front-end/css/sprout-invoices.style.css', array( 'open-sans', 'dashicons', 'qtip' ), self::SI_VERSION  );

	}

	public static function frontend_enqueue() {
		// Localization
		$si_js_object = array(
			'ajax_url' => get_admin_url().'/admin-ajax.php',
			'plugin_url' => SI_URL,
			'thank_you_string' => self::__( 'Thank you' ),
			'updating_string' => self::__( 'Updating...' ),
			'sorry_string' => self::__( 'Bummer. Maybe next time?' ),
			'security' => wp_create_nonce( self::NONCE ),
			'locale' => get_locale()
		);
		if ( is_single() && ( get_post_type( get_the_ID() ) === SI_Invoice::POST_TYPE ) ) {
			$si_js_object += array(
				'invoice_id' => get_the_ID(),
				'invoice_amount' => si_get_invoice_calculated_total(),
				'invoice_balance' => si_get_invoice_balance()
			);
		}
		if ( is_single() && ( get_post_type( get_the_ID() ) === SI_Estimate::POST_TYPE ) ) {
			$si_js_object += array(
				'estimate_id' => get_the_ID(),
				'estimate_total' => si_get_estimate_total()
			);
		}
		wp_localize_script( 'sprout_doc_scripts', 'si_js_object', self::get_localized_js() );
	}

	public static function get_localized_js() {
		// Localization
		$si_js_object = array(
			'ajax_url' => get_admin_url().'/admin-ajax.php',
			'plugin_url' => SI_URL,
			'thank_you_string' => self::__( 'Thank you' ),
			'updating_string' => self::__( 'Updating...' ),
			'sorry_string' => self::__( 'Bummer. Maybe next time?' ),
			'security' => wp_create_nonce( self::NONCE ),
			'locale' => get_locale()
		);
		if ( is_single() && ( get_post_type( get_the_ID() ) === SI_Invoice::POST_TYPE ) ) {
			$si_js_object += array(
				'invoice_id' => get_the_ID(),
				'invoice_amount' => si_get_invoice_calculated_total(),
				'invoice_balance' => si_get_invoice_balance()
			);
		}
		if ( is_single() && ( get_post_type( get_the_ID() ) === SI_Estimate::POST_TYPE ) ) {
			$si_js_object += array(
				'estimate_id' => get_the_ID(),
				'estimate_total' => si_get_estimate_total()
			);
		}
		return apply_filters( 'si_sprout_doc_scripts_localization', $si_js_object );
	}

	public static function admin_enqueue() {
		// Localization
		$si_js_object = array(
			'plugin_url' => SI_URL,
			'thank_you_string' => self::__( 'Thank you' ),
			'updating_string' => self::__( 'Updating...' ),
			'sorry_string' => self::__( 'Bummer. Maybe next time?' ),
			'done_string' => self::__( 'Finished!' ),
			'security' => wp_create_nonce( self::NONCE ),
			'premium' => ( !SI_FREE_TEST && file_exists( SI_PATH.'/controllers/updates/Updates.php' ) ) ? true : false,
			'redactor' => false
		);

		$post_id = isset( $_GET['post'] ) ? (int)$_GET['post'] : -1;
		if ( ( isset( $_GET['post_type'] ) && ( SI_Estimate::POST_TYPE || SI_Invoice::POST_TYPE ) == $_GET['post_type'] ) || ( SI_Estimate::POST_TYPE || SI_Invoice::POST_TYPE ) == get_post_type( $post_id ) ) {
			
			if ( !SI_FREE_TEST && file_exists( SI_PATH.'/resources/admin/plugins/redactor/redactor.min.js' ) ) {
				$si_js_object['redactor'] = true;
				wp_enqueue_script( 'redactor' );
				wp_enqueue_style( 'redactor' );
			}

			wp_enqueue_script( 'nestable' );
			wp_enqueue_script( 'sticky' );
			wp_enqueue_script( 'si_admin_est_and_invoices' );

			// add doc info
			$si_js_object += array(
				'doc_status' => get_post_status( get_the_id() )
			);
		}

		if ( ( isset( $_GET['post_type'] ) && SI_Client::POST_TYPE == $_GET['post_type'] ) || SI_Client::POST_TYPE == get_post_type( $post_id ) ) {
			// only clients admin
		}

		wp_enqueue_script( 'qtip' );
		wp_enqueue_script( 'select2' );
		wp_enqueue_style( 'select2_css' );
		wp_enqueue_script( 'si_admin' );
		
		wp_enqueue_style( 'qtip' );
		wp_enqueue_style( 'sprout_invoice_admin_css' );

		wp_localize_script( 'si_admin', 'si_js_object', apply_filters( 'si_admin_scripts_localization', $si_js_object ) );
	}

	/**
	 * Filter WP Cron schedules
	 * @param  array $schedules 
	 * @return array            
	 */
	public static function si_cron_schedule( $schedules ) {
		$schedules['minute'] = array(
			'interval' => 60,
			'display' => __( 'Once a Minute' )
		);
		$schedules['quarterhour'] = array(
			'interval' => 900,
			'display' => __( '15 Minutes' )
		);
		$schedules['halfhour'] = array(
			'interval' => 1800,
			'display' => __( 'Twice Hourly' )
		);
		return $schedules;
	}

	/**
	 * schedule wp events for wpcron.
	 */
	public static function set_schedule() {
		if ( self::DEBUG ) {
			wp_clear_scheduled_hook( self::CRON_HOOK );
		}
		if ( !wp_next_scheduled( self::CRON_HOOK ) ) {
			$interval = apply_filters( 'si_set_schedule', 'quarterhour' );
			wp_schedule_event( time(), $interval, self::CRON_HOOK );
		}
		if ( !wp_next_scheduled( self::DAILY_CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::DAILY_CRON_HOOK );
		}
	}

	/**
	 * Display the template for the given view
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return void
	 */
	public static function load_view( $view, $args, $allow_theme_override = TRUE ) {
		// whether or not .php was added
		if ( substr( $view, -4 ) != '.php' ) {
			$view .= '.php';
		}
		$path = apply_filters( 'si_views_path', SI_PATH.'/views/' );
		$file = $path.$view;
		if ( $allow_theme_override && defined( 'TEMPLATEPATH' ) ) {
			$file = self::locate_template( array( $view ), $file );
		}
		$file = apply_filters( 'sprout_invoice_template_'.$view, $file );
		$args = apply_filters( 'load_view_args_'.$view, $args, $allow_theme_override );
		if ( !empty( $args ) ) extract( $args );
		if ( self::DEBUG ) {
			include $file;
		}
		else {
			include $file;	
		}
	}

	/**
	 * Return a template as a string
	 *
	 * @static
	 * @param string  $view
	 * @param array   $args
	 * @param bool    $allow_theme_override
	 * @return string
	 */
	protected static function load_view_to_string( $view, $args, $allow_theme_override = TRUE ) {
		ob_start();
		self::load_view( $view, $args, $allow_theme_override );
		return ob_get_clean();
	}

	/**
	 * Locate the template file, either in the current theme or the public views directory
	 *
	 * @static
	 * @param array   $possibilities
	 * @param string  $default
	 * @return string
	 */
	protected static function locate_template( $possibilities, $default = '' ) {
		$possibilities = apply_filters( 'sprout_invoice_template_possibilities', $possibilities );
		$possibilities = array_filter( $possibilities );
		// check if the theme has an override for the template
		$theme_overrides = array();
		foreach ( $possibilities as $p ) {
			$theme_overrides[] = self::get_template_path().'/'.$p;
		}
		if ( $found = locate_template( $theme_overrides, FALSE ) ) {
			return $found;
		}

		// check for it in the templates directory
		foreach ( $possibilities as $p ) {
			if ( file_exists( SI_PATH.'/views/templates/'.$p ) ) {
				return SI_PATH.'/views/templates/'.$p;
			}
		}

		// we don't have it
		return $default;
	}

	///////////////////////////////
	// Query vars and callbacks //
	///////////////////////////////

	/**
	 * Register a query var and a callback method
	 * @param  string $var      query variable
	 * @param  string $callback callback for query variable
	 * @return null           
	 */
	protected static function register_query_var( $var, $callback = '' ) {
		self::add_register_query_var_hooks();
		self::$query_vars[$var] = $callback;
	}

	/**
	 * Register query var hooks with WordPress.
	 */
	private static function add_register_query_var_hooks() {
		static $registered = FALSE; // only do this once
		if ( !$registered ) {
			add_filter( 'query_vars', array( __CLASS__, 'filter_query_vars' ) );
			add_action( 'parse_request', array( __CLASS__, 'handle_callbacks' ), 10, 1 );
			$registered = TRUE;
		}
	}

	/**
	 * Add query vars into the filtered query_vars filter
	 * @param  array  $vars 
	 * @return array  $vars
	 */
	public static function filter_query_vars( array $vars ) {
		$vars = array_merge( $vars, array_keys( self::$query_vars ) );
		return $vars;
	}

	/**
	 * Handle callbacks for registered query vars
	 * @param  WP     $wp 
	 * @return null     
	 */
	public static function handle_callbacks( WP $wp ) {
		foreach ( self::$query_vars as $var => $callback ) {
			if ( isset( $wp->query_vars[$var] ) && $wp->query_vars[$var] && $callback && is_callable( $callback ) ) {
				call_user_func( $callback, $wp );
			}
		}
	}

	///////////////
	// Messages //
	///////////////

	public static function has_messages() {
		$msgs = self::get_messages();
		return !empty( $msgs );
	}

	public static function set_message( $message, $status = self::MESSAGE_STATUS_INFO, $save = TRUE ) {
		if ( !isset( self::$messages ) ) {
			self::load_messages();
		}
		$message = self::__( $message );
		if ( !isset( self::$messages[$status] ) ) {
			self::$messages[$status] = array();
		}
		self::$messages[$status][] = $message;
		if ( $save ) {
			self::save_messages();
		}
	}

	public static function clear_messages() {
		self::$messages = array();
		self::save_messages();
	}

	private static function save_messages() {
		global $blog_id;
		$user_id = get_current_user_id();
		if ( !$user_id ) {
			set_transient( 'si_messaging_for_'.$_SERVER['REMOTE_ADDR'], self::$messages, 300 );
		}
		update_user_meta( $user_id, $blog_id.'_'.self::MESSAGE_META_KEY, self::$messages );
	}

	public static function get_messages( $type = NULL ) {
		if ( !isset( self::$messages ) ) {
			self::load_messages();
		}
		return self::$messages;
	}

	public static function load_messages() {
		$user_id = get_current_user_id();
		$messages = FALSE;
		if ( !$user_id ) {
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$messages = get_transient( 'si_messaging_for_'.$_SERVER['REMOTE_ADDR'] );
			}
		} else {
			global $blog_id;
			$messages = get_user_meta( $user_id, $blog_id.'_'.self::MESSAGE_META_KEY, TRUE );
		}
		if ( $messages ) {
			self::$messages = $messages;
		} else {
			self::$messages = array();
		}
	}

	public static function display_messages( $type = NULL ) {
		$type = ( isset( $_REQUEST['si_message_type'] ) ) ? $_REQUEST['si_message_type'] : $type ;
		$statuses = array();
		if ( $type == NULL ) {
			if ( isset( self::$messages[self::MESSAGE_STATUS_INFO] ) ) {
				$statuses[] = self::MESSAGE_STATUS_INFO;
			}
			if ( isset( self::$messages[self::MESSAGE_STATUS_ERROR] ) ) {
				$statuses[] = self::MESSAGE_STATUS_ERROR;
			}
		} elseif ( isset( self::$messages[$type] ) ) {
			$statuses = array( $type );
		}

		if ( !isset( self::$messages ) ) {
			self::load_messages();
		}
		$messages = array();
		foreach ( $statuses as $status ) {
			foreach ( self::$messages[$status] as $message ) {
				self::load_view( 'templates/messages', array(
						'status' => $status,
						'message' => $message,
					), TRUE );
			}
			self::$messages[$status] = array();
		}
		self::save_messages();
		if ( defined( 'DOING_AJAX' ) ) {
			exit();
		}
	}

	public static function do_loop_start( $query ) {
		global $wp_query;
		if ( $query == $wp_query ) {
			self::display_messages();
		}
	}

	public static function login_required( $redirect = '' ) {
		if ( !get_current_user_id() && apply_filters( 'si_login_required', TRUE ) ) {
			if ( !$redirect && self::using_permalinks() ) {
				$schema = is_ssl() ? 'https://' : 'http://';
				$redirect = $schema.$_SERVER['SERVER_NAME'].htmlspecialchars( $_SERVER['REQUEST_URI'] );
				if ( isset( $_REQUEST ) ) {
					$redirect = urlencode( add_query_arg( $_REQUEST, $redirect ) );
				}
			}
			wp_redirect( wp_login_url( $redirect ) );
			exit();
		}
		return TRUE; // explicit return value, for the benefit of the router plugin
	}

	/**
	 * Get the home_url option directly since home_url injects a scheme based on current page.
	 */
	public static function si_get_home_url_option() {
		global $blog_id;

		if ( empty( $blog_id ) || !is_multisite() )
			$url = get_option( 'home' );
		else
			$url = get_blog_option( $blog_id, 'home' );

		return apply_filters( 'si_get_home_url_option', $url );
	}

	/**
	 * Comparison function
	 */
	public static function sort_by_weight( $a, $b ) {
		if ( !isset( $a['weight'] ) || !isset( $b['weight'] ) )
			return 0;	
		
		if ( $a['weight'] == $b['weight'] ) {
			return 0;
		}
		return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
	}


	/**
	 * Get default state options
	 * @param  array  $args 
	 * @return array       
	 */
	public static function get_state_options( $args = array() ) {
		$states = self::$grouped_states;
		if ( isset( $args['include_option_none'] ) && $args['include_option_none'] ) {
			$states = array( self::__('Select') => array( $args['include_option_none'] ) ) + $states;
		}
		$states = apply_filters( 'sprout_state_options', $states, $args );
		return $states;
	}

	/**
	 * Get default countries options
	 * @param  array  $args 
	 * @return array       
	 */
	public static function get_country_options( $args = array() ) {
		$countries = self::$countries;
		if ( isset( $args['include_option_none'] ) && $args['include_option_none'] ) {
			$countries = array( '' => $args['include_option_none'] ) + $countries;
		}
		$countries = apply_filters( 'sprout_country_options', $countries, $args );
		return $countries;
	}

	/**
	 * Is current site using permalinks
	 * @return bool
	 */
	public static function using_permalinks() {
		return get_option( 'permalink_structure' ) != '';
	}

	/**
	 * Tell caching plugins not to cache the current page load
	 */
	public static function do_not_cache() {
		if ( !defined('DONOTCACHEPAGE') ) {
			define('DONOTCACHEPAGE', TRUE);
		}
	}

	/**
	 * Tell caching plugins to clear their caches related to a post
	 *
	 * @static
	 * @param int $post_id
	 */
	public static function clear_post_cache( $post_id ) {
		if ( function_exists( 'wp_cache_post_change' ) ) {
			// WP Super Cache

			$GLOBALS["super_cache_enabled"] = 1;
			wp_cache_post_change( $post_id );

		} elseif ( function_exists( 'w3tc_pgcache_flush_post' ) ) {
			// W3 Total Cache

			w3tc_pgcache_flush_post( $post_id );

		}
	}

	/**
	 * Function to duplicate the post
	 * @param  int $post_id         
	 * @param  string $new_post_status 
	 * @return                   
	 */
	protected static function clone_post( $post_id, $new_post_status = 'draft', $new_post_type = '' ){
		$post = get_post( $post_id );
		$new_post_id = 0;
		if ( isset( $post ) && $post != null ) {

			if ( $new_post_type == '' ) 
				$new_post_type = $post->post_type;

			/*
			 * new post data array
			 */
			$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $post->post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => $new_post_status,
				'post_title'     => $post->post_title,
				'post_type'      => $new_post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order
			);
	 
			// clone the post
			$new_post_id = wp_insert_post( $args );

			// get current terms and add them to the new post
			$taxonomies = get_object_taxonomies($post->post_type);
			if ( is_array( $taxonomies ) ) {
				foreach ($taxonomies as $taxonomy) {
					$post_terms = wp_get_object_terms($post_id, $taxonomy, array( 'orderby' => 'term_order' ));
					$terms = array();
					for ($i=0; $i<count($post_terms); $i++) {
						$terms[] = $post_terms[$i]->slug;
					}
					wp_set_object_terms($new_post_id, $terms, $taxonomy);
				}
			}
	 
			// Duplicate all post_meta
			$meta_keys = get_post_custom_keys($post_id);
			if ( $meta_keys ) {
				foreach ($meta_keys as $meta_key) {
					$meta_values = get_post_custom_values($meta_key, $post_id);
					foreach ($meta_values as $meta_value) {
						$meta_value = maybe_unserialize($meta_value);
						add_post_meta($new_post_id, $meta_key, $meta_value);
					}
				}
			}
		}
		// end
		do_action( 'si_cloned_post', $new_post_id, $post_id, $new_post_type, $new_post_status );
		return $new_post_id;
	}

	///////////////////////////////
	// Notification duplication //
	///////////////////////////////

	public static function maybe_clone_and_redirect() {
		if ( isset( $_GET['clone_si_post'] ) && isset( $_GET['post'] ) && $_GET['clone_si_post'] ) {
			$post_id = $_GET['post'];
			if ( check_admin_referer( 'clone-si_post_'.$post_id, 'clone_si_post' ) ) {
				$new_post_type = ( isset( $_GET['post_type'] ) ) ? $_GET['post_type'] : '' ;
				$cloned_post_id = self::clone_post( $post_id, 'publish', $new_post_type );

				do_action( 'si_cloned_post_before_redirect', $cloned_post_id );

				wp_redirect( add_query_arg( array( 'post' => $cloned_post_id, 'action' => 'edit' ), admin_url( 'post.php') ) );
				exit();
			}
		}
		
	}

	public static function get_clone_post_url( $post_id = 0, $new_post_type = '' ) {
		$url = wp_nonce_url( get_edit_post_link( $post_id ), 'clone-si_post_'.$post_id, 'clone_si_post' );
		if ( $new_post_type != '' ) {
			$url = add_query_arg( array( 'post_type' => $new_post_type ), $url );
		}
		return apply_filters( 'si_get_clone_post_url', $url, $post_id, $new_post_type );
	}


	/**
	 * Standard Address Fields.
	 * Params are used for filter only.
	 * @param  integer $user_id  
	 * @param  boolean $shipping 
	 * @return array            
	 */
	public static function get_standard_address_fields( $required = TRUE, $user_id = 0 ) {
		$fields = array();
		$fields['first_name'] = array(
			'weight' => 50,
			'label' => self::__( 'First Name' ),
			'placeholder' => self::__( 'First Name' ),
			'type' => 'text',
			'required' => $required
		);
		$fields['last_name'] = array(
			'weight' => 51,
			'label' => self::__( 'Last Name' ),
			'placeholder' => self::__( 'Last Name' ),
			'type' => 'text',
			'required' => $required
		);
		$fields['street'] = array(
			'weight' => 60,
			'label' => self::__( 'Street Address' ),
			'placeholder' => self::__( 'Street Address' ),
			'type' => 'textarea',
			'rows' => 2,
			'required' => $required
		);
		$fields['city'] = array(
			'weight' => 65,
			'label' => self::__( 'City' ),
			'placeholder' => self::__( 'City' ),
			'type' => 'text',
			'required' => $required
		);

		$fields['postal_code'] = array(
			'weight' => 70,
			'label' => self::__( 'ZIP Code' ),
			'placeholder' => self::__( 'ZIP Code' ),
			'type' => 'text',
			'required' => $required
		);

		$fields['zone'] = array(
			'weight' => 75,
			'label' => self::__( 'State' ),
			'type' => 'select-state',
			'options' => self::get_state_options( array( 'include_option_none' => ' -- '.self::__( 'State' ).' -- ' ) ),
			'attributes' => array( 'class' => 'select2' ),
			'required' => $required
		); // FUTURE: Add some JavaScript to switch between select box/text-field depending on country

		$fields['country'] = array(
			'weight' => 80,
			'label' => self::__( 'Country' ),
			'type' => 'select',
			'required' => $required,
			'options' => self::get_country_options( array( 'include_option_none' => ' -- '.self::__( 'Country' ).' -- ' ) ),
			'attributes' => array( 'class' => 'select2' ),
		);
		return apply_filters( 'si_get_standard_address_fields', $fields, $required, $user_id );
	}

	////////////////////
	// AJAX Callback //
	////////////////////

	public static function maybe_create_private_note() {

		if ( !isset( $_REQUEST['private_note_nonce'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['private_note_nonce'];
		if ( !wp_verify_nonce( $nonce, SI_Internal_Records::NONCE ) )
			self::ajax_fail( 'Not going to fall for it!' );

		if ( !current_user_can( 'edit_posts' ) )
			return;

		$record_id = SI_Internal_Records::new_record( $_REQUEST['notes'], SI_Controller::PRIVATE_NOTES_TYPE, $_REQUEST['associated_id'], '', 0, FALSE );
		$error = ( $record_id ) ? '' : si__( 'Private note failed to save, try again.' ) ;
		$data = array(
			'id' => $record_id,
			'content' => $_REQUEST['notes'],
			'type' => si__( 'Private Note' ),
			'post_date' => si__( 'Just now' ),
			'error' => $error
		);

		header( 'Content-type: application/json' );
		if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( $data );
		exit();

	}

	public static function maybe_change_status() {
		if ( !isset( $_REQUEST['change_status_nonce'] ) )
			self::ajax_fail( 'Forget something?' );

		$nonce = $_REQUEST['change_status_nonce'];
		if ( !wp_verify_nonce( $nonce, self::NONCE ) )
			self::ajax_fail( 'Not going to fall for it!' );

		if ( !isset( $_REQUEST['id'] ) )
			self::ajax_fail( 'Forget something?' );

		switch ( get_post_type( $_REQUEST['id'] ) ) {
			case SI_Invoice::POST_TYPE:
				$doc = SI_Invoice::get_instance( $_REQUEST['id'] );
				$doc->set_status( $_REQUEST['status'] );
				$view = self::load_view_to_string( 'admin/sections/invoice-status-change-drop', array(
						'id' => $_REQUEST['id'],
						'status' => $doc->get_status()
					), FALSE );
				break;
			case SI_Estimate::POST_TYPE:
				$doc = SI_Estimate::get_instance( $_REQUEST['id'] );
				$doc->set_status( $_REQUEST['status'] );
				$view = self::load_view_to_string( 'admin/sections/estimate-status-change-drop', array(
						'id' => $_REQUEST['id'],
						'status' => $doc->get_status()
					), FALSE );
				break;
			
			default:
				self::ajax_fail( 'Not an estimate or invoice.' );
				break;
		}
		
		// action
		do_action( 'doc_status_changed', $doc, $_REQUEST );
		
		header( 'Content-type: application/json' );
		if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
		echo json_encode( array( 'new_button' => $view ) );
		exit();

	}


	//////////////
	// Utility //
	//////////////

	public static function ajax_fail( $message = '', $json = TRUE ) {
		if ( $message == '' ) {
			$message = self::__('Something failed.');
		}
		if ( $json ) header( 'Content-type: application/json' );
		if ( self::DEBUG ) header( 'Access-Control-Allow-Origin: *' );
		if ( $json ) {
			echo json_encode( array( 'error' => 1, 'response' => $message ) );
		}
		else {
			echo $message;
		}
		exit();
	}

	public static function get_user_ip() {
	    $client  = @$_SERVER['HTTP_CLIENT_IP'];
	    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
	    $remote  = $_SERVER['REMOTE_ADDR'];

	    if(filter_var($client, FILTER_VALIDATE_IP)) {
	        $ip = $client;
	    }
	    elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
	        $ip = $forward;
	    }
	    else {
	        $ip = $remote;
	    }
	    return $ip;
	}

	/**
	 * Number with ordinal suffix
	 */
	public static function number_ordinal_suffix( $number = 0 ) {
		if ( !is_numeric( $number ) ) {
			return $number;
		}
		if ( !$number ) {
			return 'zero';
		}
		$ends = array('th','st','nd','rd','th','th','th','th','th','th');
		if ( ($number %100) >= 11 && ($number%100) <= 13 ) {
			$abbreviation = $number. 'th';
		}
		else {
			$abbreviation = $number. $ends[$number % 10];
		}
		return $abbreviation;
		
	}

	public static function _save_null() {
		__return_null();
	}

}