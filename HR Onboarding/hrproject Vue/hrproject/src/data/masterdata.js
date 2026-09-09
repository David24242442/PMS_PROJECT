const mstatus = [{id:1,name:'DIVORCED'},{id:2,name:'MARRIED'},{id:3,name:'NOT DISCLOSED'},{id:4,name:'OTHERS'},{id:5,name:'SINGLE'}]
const bankaccounttype = [{id:1,name:'CURRENT'},{id:2,name:'SAVINGS'}]
const relations = [{id:1,name:'FAMILY'},{id:2,name:'FRIEND'},{id:3,name:'COLLEAGUE'}]

const genders = [{id:'F',name:'FEMALE'},{id:'M',name:'MALE'}]

const conttypes = [{code:'expat',name:'Expat'},{code:'permanent',name:'Permanent'},{code:'contract',name:'Contract'}]

const statuses = [
    {id:1,name:"Active"},
    {id:2,name:"Resigned"},
    {id:3,name:"Absconded"},
    {id:4,name:"Terminated"},
    {id:5,name:"Dismissed"},
    {id:6,name:"Deceased"},
]

const companies = [
    {id:1,name:'CENTURY'},
    {id:2,name:'CROWN STAR'},
    {id:3,name:'MELCOM'}, 
    {id:4,name:'EAD'},
    {id:5,name:'SBP'},
    {id:6,name:'BTC'},
    {id:7,name:'JIREH 7'},
    {id:8,name:'IVA GREEN'},
    {id:9,name:'JIT'},
    {id:10,name:'PHILIPS OUTSOURCING LIMITED'}
]

const depts = [
    { id: 1, name: 'ACCOUNTS' },
    { id: 17, name: 'ADVERT' },
    { id: 2, name: 'ADMINISTRATION' },
    { id: 18, name: 'ARTS KITCHEN' },
    { id: 3, name: 'AUDIT' },
    { id: 4, name: 'TEST DEPT' },
    { id: 5, name: 'ANOTHER TEST DEPT' },
    { id: 19, name: 'BAKERY' },
    { id: 20, name: 'BAR' },
    { id: 21, name: 'BUTCHERY' },
    { id: 16, name: 'CHOP BAR' },
    { id: 22, name: 'CROWN STAR' },
    { id: 23, name: 'ELECTRICAL APPLIANCES' },
    { id: 24, name: 'GIORDANO' },
    { id: 25, name: 'HALLAB' },
    { id: 26, name: 'HOMEDECORE & TEXTILES' },
    { id: 27, name: 'HOUSEWARE & KITCHENWARE' },
    { id: 6, name: 'HR' },
    { id: 8, name: 'IT' },
    { id: 7, name: 'IMPORT' },
    { id: 9, name: 'LAST DEPT' },
    { id: 28, name: 'LOGISTICS' },
    { id: 29, name: 'LUGGAGE' },
    { id: 30, name: 'MANAGEMENT' },
    { id: 10, name: 'MARKETING' },
    { id: 11, name: 'MERCHANDISE' },
    { id: 13, name: 'OPERATIONS' },
    { id: 31, name: 'OTHERS' },
    { id: 14, name: 'PIZZA HUT' },
    { id: 15, name: 'PROJECT' },
    { id: 32, name: 'PRODUCTION' },
    { id: 33, name: 'RESTAURANT' },
    { id: 34, name: 'RETAIL' },
    { id: 35, name: 'SECURITY' },
    { id: 36, name: 'SERVICE CENTER' },
    { id: 37, name: 'SPORTS & FITNESS' },
    { id: 38, name: 'SUPERMARKET' },
    { id: 39, name: 'WARE HOUSE' },
    { id: 40, name: 'MELCOM NOW' },
    { id: 41, name: 'DIGITAL COMMERCE' },
    { id: 42, name: 'CENTURY' },
    { id: 43, name: 'SIP CAFE' },
    { id: 44, name: 'ON THE ROCK, BAR' },
    { id: 45, name: 'WASABI' },
    { id: 46, name: 'MAHARAJA' },
    { id: 47, name: 'YOLE' },
    { id: 48, name: 'SIP GOURMET' }
    
  ]

depts.sort((a, b) => a.name.localeCompare(b.name));

const regions = [
    {id:1,name:'AHAFO REGION', code:'AF'}
    ,{id:2,name:'ASHANTI REGION', code:'AR'}
    ,{id:3,name:'BRONG AHAFO', code:'BA'}
    ,{id:4,name:'BONO EAST', code:'BE'}
    ,{id:5,name:'CENTRAL REGION', code:'CR'}
    ,{id:6,name:'EASTERN REGION', code:'ER'}
    ,{id:7,name:'GREATER ACCRA', code:'GA'}
    ,{id:8,name:'NORTHERN REGION', code:'NR'}
    ,{id:9,name:'NORTH EAST', code:'NE'}
    ,{id:10,name:'OTI REGION', code:'0R'}
    ,{id:11,name:'SAVANNAH REGION', code:'SR'}
    ,{id:12,name:'UPPER EAST', code:'UE'}
    ,{id:13,name:'UPPER WEST', code:'UW'}
    ,{id:14,name:'WESTERN NORTH', code:'WN'}
    ,{id:15,name:'WESTERN REGION', code:'WR'}
    ,{id:16,name:'VOLTA REGION', code:'VR'}
]
regions.sort((a, b) => a.name.localeCompare(b.name));

const branchs = [
    {"id": 1, "name": "ABLEKUMA"},
    {"id": 2, "name": "ACCRA"},
    {"id": 3, "name": "ACCRA MALL"},
    {"id": 4, "name": "ACCRA WHOLESALES"},
    {"id": 5, "name": "ACCRAWHOLESALE"},
    {"id": 6, "name": "ACHIMOTA"},
    {"id": 7, "name": "ACHIMOTA MALL"},
    {"id": 8, "name": "ACTIVE MSS"},
    {"id": 9, "name": "ADABRAKA"},
    {"id": 10, "name": "ADENTA"},
    {"id": 11, "name": "ADJIRINGNOR-MINI"},
    {"id": 12, "name": "AFEINYA"},
    {"id": 13, "name": "AFLAO"},
    {"id": 14, "name": "AMASAMAN"},
    {"id": 15, "name": "ANLOGA WAREHOUSE"},
    {"id": 16, "name": "ARCADIA SPINTEX NEW"},
    {"id": 17, "name": "ARCADIA SHOP (WEST HILLS)"},
    {"id": 18, "name": "ART KITCHEN ACCRA MALL"},
    {"id": 19, "name": "ART KITCHEN ACHIMOTA"},
    {"id": 20, "name": "ART KITCHEN COMMUNITY 25"},
    {"id": 21, "name": "ART KITCHEN KASDA"},
    {"id": 22, "name": "ART KITCHEN KUMASI MALL"},
    {"id": 23, "name": "ART KITCHEN WESTHILL MALL"},
    {"id": 24, "name": "ASAMANKESE"},
    {"id": 25, "name": "ASAMANKESE CASH & CARRY"},
    {"id": 26, "name": "ASHAIMAN"},
    {"id": 27, "name": "ASHALEY BOTWE-MINI"},
    {"id": 28, "name": "ASHIAMAN CASH AND CARRY"},
    {"id": 29, "name": "ASHONGMAN"},
    {"id": 30, "name": "ASSIN FOSU"},
    {"id": 31, "name": "BAKERY"},
    {"id": 32, "name": "BAWKU"},
    {"id": 33, "name": "BEREKUM"},
    {"id": 34, "name": "BIBIANI"},
    {"id": 35, "name": "BOLGATANGA"},
    {"id": 36, "name": "CAPECOAST"},
    {"id": 37, "name": "COMMUNITY 25 ELECTRIC"},
    {"id": 38, "name": "COMMUNITY 25 MINI"},
    {"id": 39, "name": "DANSOMAN"},
    {"id": 40, "name": "DANSOMAN MINI"},
    {"id": 41, "name": "EAST LEGON"},
    {"id": 42, "name": "EAST LEGON MINI"},
    {"id": 43, "name": "EAST LEGON -2"},
    {"id": 44, "name": "EAST LEGON BOUNDARY ROAD BAKERY"},
    {"id": 45, "name": "EAST LEGON BOUNDARY ROAD BAR"},
    {"id": 46, "name": "EAST LEGON BOUNDARY ROAD LIV"},
    {"id": 47, "name": "EASTLEGON BOUNDARY ROAD"},
    {"id": 48, "name": "EXPATS HO"},
    {"id": 49, "name": "FRAFRAHA"},
    {"id": 50, "name": "GBAWE"},
    {"id": 51, "name": "GIORDANO ACHIMOTA"},
    {"id": 52, "name": "GIORDANO-EAST LEGON"},
    {"id": 53, "name": "GIORDANO-FRAFRAHA"},
    {"id": 54, "name": "GIORDANO HEAD OFFICE"},
    {"id": 55, "name": "GIORDANO-KANESHIE"},
    {"id": 56, "name": "GIORDANO KUMASI"},
    {"id": 57, "name": "GIORDANO KUMASI SANTASI"},
    {"id": 58, "name": "GIORDANO SPINTEX NEW"},
    {"id": 59, "name": "GIORDANO TAKORADI"},
    {"id": 60, "name": "GIORDANO WEST HILL"},
    {"id": 61, "name": "GIORDANO WH-TEMA FZ"},
    {"id": 62, "name": "HAATSO"},
    {"id": 63, "name": "HALLAB-EAST LEGON"},
    {"id": 64, "name": "HALLAB EAST LEGON BOUNDARY ROAD"},
    {"id": 65, "name": "HALLAB REST. SPINTEX MALL"},
    {"id": 66, "name": "HAMPTON SQUARE"},
    {"id": 67, "name": "HEAD OFFICE"},
    {"id": 68, "name": "HO"},
    {"id": 69, "name": "HOHOE"},
    {"id": 70, "name": "HOME-TEMA"},
    {"id": 71, "name": "KANESHIE"},
    {"id": 72, "name": "KANESHIE BAR"},
    {"id": 73, "name": "KANESHIE CHOP BAR"},
    {"id": 74, "name": "KANESHIE LIVE KITCHEN"},
    {"id": 75, "name": "KASOA"},
    {"id": 76, "name": "KASOA CASH & CARRY"},
    {"id": 77, "name": "KASOA MINI"},
    {"id": 78, "name": "KASS"},
    {"id": 79, "name": "KISSEMAN"},
    {"id": 80, "name": "KOFORIDIA"},
    {"id": 81, "name": "KOFORIDUA HOME"},
    {"id": 82, "name": "KOFORIDIA-2"},
    {"id": 83, "name": "KUMASI"},
    {"id": 84, "name": "KUMASI SUAME"},
    {"id": 85, "name": "KUMASI 2"},
    {"id": 86, "name": "KUMASI 3"},
    {"id": 87, "name": "KUMASI 4 HENE"},
    {"id": 88, "name": "KUMASI 5"},
    {"id": 89, "name": "KUMASI 6 TAFO"},
    {"id": 90, "name": "KUMASI CASH AND CARRY"},
    {"id": 91, "name": "KUMASI MALL"},
    {"id": 92, "name": "KUMASI SANTASI"},
    {"id": 93, "name": "KUMASI SUAME CASH & CARRY"},
    {"id": 94, "name": "LABADI"},
    {"id": 95, "name": "LABONE"},
    {"id": 96, "name": "LAPAZ SHOP"},
    {"id": 97, "name": "LOGISTICS"},
    {"id": 98, "name": "MADINA"},
    {"id": 99, "name": "MAIN WAREHOUSE"},
    {"id": 100, "name": "MAINTANANCES HEAD OFFICE"},
    {"id": 101, "name": "MAINTANANCES TEMA"},
    {"id": 102, "name": "MAKOLA"},
    {"id": 103, "name": "MANKESSIM"},
    {"id": 104, "name": "MANKESSIM CASH AND CARRY"},
    {"id": 105, "name": "MARKETING"},
    {"id": 106, "name": "MELCOM HOSPITALITY"},
    {"id": 107, "name": "MELCOM NOW"},
    {"id": 108, "name": "MESS EXPENSES SPINTEX NEW"},
    {"id": 109, "name": "MESS EXPENSES WH FREEZONE"},
    {"id": 110, "name": "MINI-DOME"},
    {"id": 111, "name": "MODERN TRADE"},
    {"id": 112, "name": "NANAKROM"},
    {"id": 113, "name": "NKAWKAW"},
    {"id": 114, "name": "OBUASI"},
    {"id": 115, "name": "ON THE ROCK ASHIAMAN"},
    {"id": 116, "name": "ONLINE ACCRA"},
    {"id": 117, "name": "PROJECT KUMASI"},
    {"id": 118, "name": "PROJECTS"},
    {"id": 119, "name": "REGIONAL MANAGERS"},
    {"id": 120, "name": "SAKUMONO"},
    {"id": 121, "name": "SEFWI WIAWSO"},
    {"id": 122, "name": "SERVICE CENTER"},
    {"id": 123, "name": "SIP CAFE ACHIMOTA MALL"},
    {"id": 124, "name": "SIP CAFE-KANESHIE"},
    {"id": 125, "name": "SIP CAFE SPINTEX MALL"},
    {"id": 126, "name": "SIP CAFE EXPRESS KASS TOWER"},
    {"id": 127, "name": "SPINTEX BAR"},
    {"id": 128, "name": "SPINTEX NEW MALL"},
    {"id": 129, "name": "SPINTEX NEW ACTIVE & GYM"},
    {"id": 130, "name": "SPINTEX NEW BAKERY"},
    {"id": 131, "name": "SPINTEX NEW LIVE KITCHEN"},
    {"id": 132, "name": "SPINTEX NEW MAHARAJA"},
    {"id": 133, "name": "SPINTEX RD."},
    {"id": 134, "name": "SUNIYANI"},
    {"id": 135, "name": "SUNYANI CASH AND CARRY"},
    {"id": 136, "name": "SUNYANI-2"},
    {"id": 137, "name": "SWEDRU"},
    {"id": 138, "name": "SWEDRU 2"},
    {"id": 139, "name": "SWEDRU CASH AND CARRY"},
    {"id": 140, "name": "TAKORADI"},
    {"id": 141, "name": "TAMALE"},
    {"id": 142, "name": "TAMALE CASH AND CARRY"},
    {"id": 143, "name": "TARKWA"},
    {"id": 144, "name": "TECHIMAN"},
    {"id": 145, "name": "TECHIMAN 2"},
    {"id": 146, "name": "TECHIMAN CASH AND CARRY"},
    {"id": 147, "name": "TECHIMAN HOME"},
    {"id": 148, "name": "TEMA"},
    {"id": 149, "name": "TEMA (IMPORT DIVISION)"},
    {"id": 150, "name": "TEMA ONLINE"},
    {"id": 151, "name": "TEMA PLUS"},
    {"id": 152, "name": "TEMA-2"},
    {"id": 153, "name": "TEPA"},
    {"id": 154, "name": "TESHIE NUNGUA"},
    {"id": 155, "name": "TRANSPORT DIVISION"},
    {"id": 156, "name": "UPSA"},
    {"id": 157, "name": "WA SHOP"},
    {"id": 158, "name": "WAREHOUSE-15"},
    {"id": 159, "name": "WAREHOUSE-16"},
    {"id": 160, "name": "WAREHOUSE-17"},
    {"id": 161, "name": "WAREHOUSE"},
    {"id": 162, "name": "WEIJA BAKERY"},
    {"id": 163, "name": "WEIJA SHOP"},
    {"id": 164, "name": "WENCHI"},
    {"id": 165, "name": "WH-TEMA-FZ"},
    {"id": 166, "name": "YOLE-SPINTEX NEW"},
    {"id": 167, "name": "PIZZA HUT - DZORWULU"},
    {"id": 168, "name": "PIZZA HUT - MARINA MALL"},
    {"id": 169, "name": "PIZZA HUT - UPSA"},
    {"id": 170, "name": "PIZZA HUT - ASHALEY BOTWE"},
    {"id": 171, "name": "PIZZA HUT - HOSPITAL RD (COM II)"},
    {"id": 172, "name": "PIZZA HUT - COM.25"},
    {"id": 173, "name": "PIZZA HUT - ASYLUM DOWN"},
    {"id": 174, "name": "WASABI SPINTEX MALL"},
    {"id": 175, "name": "ON THE ROCK KANESHIE"},
    {"id": 176, "name": "ON THE ROCK EAST LEGON"},
    {"id": 177, "name": "SIP CAFE GOURMET LABONE"},
    {"id": 178, "name": "DOME"},
    {"id": 179, "name": "DOME MINI"},
    {"id": 180, "name": "OYARIFA"},
    {"id": 181, "name": "LASHIBI"}
]

const positions = [
    {id:1,name:'Employee'},
    {id:2,name:'Entry'},
    {id:3,name:'Manager'},
    {id:4,name:'Hr Head'},
]
branchs.sort((a, b) => a.name.localeCompare(b.name));

const qualtypes = [{id:1,name:'DIPLOMA/HND/UNDERGRADUATE'},{id:2,name:'GRADUATE'},{id:3,name:'OTHER'},{id:4,name:'POST GRADUATE'}, {id:5,name:'PHD'},{id:6,name:'SCHOOLING'},]

const banks = [
    { id: 3, name: 'GCB' },
    { id: 2, name: 'PRUDENTIAL BANK' },
    { id: 4, name: 'FIDELITY' },
    { id: 1, name: 'ACCESS BANK' },
    { id: 5, name: 'FIRST NATIONAL' },
    { id: 6, name: 'FIRST ATLANTIC' },
    { id: 7, name: 'STANBIC' },
    { id: 8, name: 'ZENITH' },
    { id: 9, "name": "ABSA" },
    { id: 10, "name": "ADB" },
    { id: 11, "name": "BANK OF AFRICA" },
    { id: 12, "name": "CALBANK" },
    { id: 13, "name": "CONSOLIDATED BANK GHANA" },
    { id: 14, "name": "ECOBANK" },
    { id: 15, "name": "FBN" },
    { id: 16, "name": "GT" },
    { id: 17, "name": "NIB" },
    { id: 18, "name": "OMNI BSIC BANK" },
    { id: 19, "name": "REPUBLIC BANK" },
    { id: 20, "name": "SOCIETE GENERAL" },
    { id: 21, "name": "STANDARD CHARTERED" },
    { id: 22, "name": "UNITED BANK OF AFRICA" },
    { id: 23, "name": "UMB" }
]
banks.sort((a, b) => a.name.localeCompare(b.name));

const familyrelation = [
    {id:1,name:'AUNTY'},{id:2,name:'BROTHER-IN-LAW'},{id:3,name:'COUSIN'},{id:4,name:'DAUGHTER-IN-LAW'},{id:5,name:'FATHER'},{id:6,name:'FATHER-IN-LAW'},{id:7,name:'GRANDFATHER'},{id:8,name:'GRANDMOTHER'},{id:9,name:'MOTHER'},{id:10,name:'MOTHER-IN-LAW'},{id:11,name:'NEPHEW'},{id:12,name:'NIECE'},{id:13,name:'SIBLING'},{id:14,name:'SISTER-IN-LAW'},{id:15,name:'SON-IN-LAW'},{id:16,name:'SPOUSE'},{id:17,name:'STEPFATHER'},{id:18,name:'STEPMOTHER'},
    {id:19,name:'UNCLE'},
    {id:20,name:'SON'},
    {id:21,name:'DAUGHTER'}
]
familyrelation.sort((a, b) => a.name.localeCompare(b.name));


const countries = [ 
    {name: 'Afghanistan', code: 'AF'}, 
    {name: 'Åland Islands', code: 'AX'}, 
    {name: 'Albania', code: 'AL'}, 
    {name: 'Algeria', code: 'DZ'}, 
    {name: 'American Samoa', code: 'AS'}, 
    {name: 'AndorrA', code: 'AD'}, 
    {name: 'Angola', code: 'AO'}, 
    {name: 'Anguilla', code: 'AI'}, 
    {name: 'Antarctica', code: 'AQ'}, 
    {name: 'Antigua and Barbuda', code: 'AG'}, 
    {name: 'Argentina', code: 'AR'}, 
    {name: 'Armenia', code: 'AM'}, 
    {name: 'Aruba', code: 'AW'}, 
    {name: 'Australia', code: 'AU'}, 
    {name: 'Austria', code: 'AT'}, 
    {name: 'Azerbaijan', code: 'AZ'}, 
    {name: 'Bahamas', code: 'BS'}, 
    {name: 'Bahrain', code: 'BH'}, 
    {name: 'Bangladesh', code: 'BD'}, 
    {name: 'Barbados', code: 'BB'}, 
    {name: 'Belarus', code: 'BY'}, 
    {name: 'Belgium', code: 'BE'}, 
    {name: 'Belize', code: 'BZ'}, 
    {name: 'Benin', code: 'BJ'}, 
    {name: 'Bermuda', code: 'BM'}, 
    {name: 'Bhutan', code: 'BT'}, 
    {name: 'Bolivia', code: 'BO'}, 
    {name: 'Bosnia and Herzegovina', code: 'BA'}, 
    {name: 'Botswana', code: 'BW'}, 
    {name: 'Bouvet Island', code: 'BV'}, 
    {name: 'Brazil', code: 'BR'}, 
    {name: 'British Indian Ocean Territory', code: 'IO'}, 
    {name: 'Brunei Darussalam', code: 'BN'}, 
    {name: 'Bulgaria', code: 'BG'}, 
    {name: 'Burkina Faso', code: 'BF'}, 
    {name: 'Burundi', code: 'BI'}, 
    {name: 'Cambodia', code: 'KH'}, 
    {name: 'Cameroon', code: 'CM'}, 
    {name: 'Canada', code: 'CA'}, 
    {name: 'Cape Verde', code: 'CV'}, 
    {name: 'Cayman Islands', code: 'KY'}, 
    {name: 'Central African Republic', code: 'CF'}, 
    {name: 'Chad', code: 'TD'}, 
    {name: 'Chile', code: 'CL'}, 
    {name: 'China', code: 'CN'}, 
    {name: 'Christmas Island', code: 'CX'}, 
    {name: 'Cocos (Keeling) Islands', code: 'CC'}, 
    {name: 'Colombia', code: 'CO'}, 
    {name: 'Comoros', code: 'KM'}, 
    {name: 'Congo', code: 'CG'}, 
    {name: 'Congo, The Democratic Republic of the', code: 'CD'}, 
    {name: 'Cook Islands', code: 'CK'}, 
    {name: 'Costa Rica', code: 'CR'}, 
    {name: 'Cote D\'Ivoire', code: 'CI'}, 
    {name: 'Croatia', code: 'HR'}, 
    {name: 'Cuba', code: 'CU'}, 
    {name: 'Cyprus', code: 'CY'}, 
    {name: 'Czech Republic', code: 'CZ'}, 
    {name: 'Denmark', code: 'DK'}, 
    {name: 'Djibouti', code: 'DJ'}, 
    {name: 'Dominica', code: 'DM'}, 
    {name: 'Dominican Republic', code: 'DO'}, 
    {name: 'Ecuador', code: 'EC'}, 
    {name: 'Egypt', code: 'EG'}, 
    {name: 'El Salvador', code: 'SV'}, 
    {name: 'Equatorial Guinea', code: 'GQ'}, 
    {name: 'Eritrea', code: 'ER'}, 
    {name: 'Estonia', code: 'EE'}, 
    {name: 'Ethiopia', code: 'ET'}, 
    {name: 'Falkland Islands (Malvinas)', code: 'FK'}, 
    {name: 'Faroe Islands', code: 'FO'}, 
    {name: 'Fiji', code: 'FJ'}, 
    {name: 'Finland', code: 'FI'}, 
    {name: 'France', code: 'FR'}, 
    {name: 'French Guiana', code: 'GF'}, 
    {name: 'French Polynesia', code: 'PF'}, 
    {name: 'French Southern Territories', code: 'TF'}, 
    {name: 'Gabon', code: 'GA'}, 
    {name: 'Gambia', code: 'GM'}, 
    {name: 'Georgia', code: 'GE'}, 
    {name: 'Germany', code: 'DE'}, 
    {name: 'Ghana', code: 'GH'}, 
    {name: 'Gibraltar', code: 'GI'}, 
    {name: 'Greece', code: 'GR'}, 
    {name: 'Greenland', code: 'GL'}, 
    {name: 'Grenada', code: 'GD'}, 
    {name: 'Guadeloupe', code: 'GP'}, 
    {name: 'Guam', code: 'GU'}, 
    {name: 'Guatemala', code: 'GT'}, 
    {name: 'Guernsey', code: 'GG'}, 
    {name: 'Guinea', code: 'GN'}, 
    {name: 'Guinea-Bissau', code: 'GW'}, 
    {name: 'Guyana', code: 'GY'}, 
    {name: 'Haiti', code: 'HT'}, 
    {name: 'Heard Island and Mcdonald Islands', code: 'HM'}, 
    {name: 'Holy See (Vatican City State)', code: 'VA'}, 
    {name: 'Honduras', code: 'HN'}, 
    {name: 'Hong Kong', code: 'HK'}, 
    {name: 'Hungary', code: 'HU'}, 
    {name: 'Iceland', code: 'IS'}, 
    {name: 'India', code: 'IN'}, 
    {name: 'Indonesia', code: 'ID'}, 
    {name: 'Iran, Islamic Republic Of', code: 'IR'}, 
    {name: 'Iraq', code: 'IQ'}, 
    {name: 'Ireland', code: 'IE'}, 
    {name: 'Isle of Man', code: 'IM'}, 
    {name: 'Israel', code: 'IL'}, 
    {name: 'Italy', code: 'IT'}, 
    {name: 'Jamaica', code: 'JM'}, 
    {name: 'Japan', code: 'JP'}, 
    {name: 'Jersey', code: 'JE'}, 
    {name: 'Jordan', code: 'JO'}, 
    {name: 'Kazakhstan', code: 'KZ'}, 
    {name: 'Kenya', code: 'KE'}, 
    {name: 'Kiribati', code: 'KI'}, 
    {name: 'Korea, Democratic People\'S Republic of', code: 'KP'}, 
    {name: 'Korea, Republic of', code: 'KR'}, 
    {name: 'Kuwait', code: 'KW'}, 
    {name: 'Kyrgyzstan', code: 'KG'}, 
    {name: 'Lao People\'S Democratic Republic', code: 'LA'}, 
    {name: 'Latvia', code: 'LV'}, 
    {name: 'Lebanon', code: 'LB'}, 
    {name: 'Lesotho', code: 'LS'}, 
    {name: 'Liberia', code: 'LR'}, 
    {name: 'Libyan Arab Jamahiriya', code: 'LY'}, 
    {name: 'Liechtenstein', code: 'LI'}, 
    {name: 'Lithuania', code: 'LT'}, 
    {name: 'Luxembourg', code: 'LU'}, 
    {name: 'Macao', code: 'MO'}, 
    {name: 'Macedonia, The Former Yugoslav Republic of', code: 'MK'}, 
    {name: 'Madagascar', code: 'MG'}, 
    {name: 'Malawi', code: 'MW'}, 
    {name: 'Malaysia', code: 'MY'}, 
    {name: 'Maldives', code: 'MV'}, 
    {name: 'Mali', code: 'ML'}, 
    {name: 'Malta', code: 'MT'}, 
    {name: 'Marshall Islands', code: 'MH'}, 
    {name: 'Martinique', code: 'MQ'}, 
    {name: 'Mauritania', code: 'MR'}, 
    {name: 'Mauritius', code: 'MU'}, 
    {name: 'Mayotte', code: 'YT'}, 
    {name: 'Mexico', code: 'MX'}, 
    {name: 'Micronesia, Federated States of', code: 'FM'}, 
    {name: 'Moldova, Republic of', code: 'MD'}, 
    {name: 'Monaco', code: 'MC'}, 
    {name: 'Mongolia', code: 'MN'}, 
    {name: 'Montserrat', code: 'MS'}, 
    {name: 'Morocco', code: 'MA'}, 
    {name: 'Mozambique', code: 'MZ'}, 
    {name: 'Myanmar', code: 'MM'}, 
    {name: 'Namibia', code: 'NA'}, 
    {name: 'Nauru', code: 'NR'}, 
    {name: 'Nepal', code: 'NP'}, 
    {name: 'Netherlands', code: 'NL'}, 
    {name: 'Netherlands Antilles', code: 'AN'}, 
    {name: 'New Caledonia', code: 'NC'}, 
    {name: 'New Zealand', code: 'NZ'}, 
    {name: 'Nicaragua', code: 'NI'}, 
    {name: 'Niger', code: 'NE'}, 
    {name: 'Nigeria', code: 'NG'}, 
    {name: 'Niue', code: 'NU'}, 
    {name: 'Norfolk Island', code: 'NF'}, 
    {name: 'Northern Mariana Islands', code: 'MP'}, 
    {name: 'Norway', code: 'NO'}, 
    {name: 'Oman', code: 'OM'}, 
    {name: 'Pakistan', code: 'PK'}, 
    {name: 'Palau', code: 'PW'}, 
    {name: 'Palestinian Territory, Occupied', code: 'PS'}, 
    {name: 'Panama', code: 'PA'}, 
    {name: 'Papua New Guinea', code: 'PG'}, 
    {name: 'Paraguay', code: 'PY'}, 
    {name: 'Peru', code: 'PE'}, 
    {name: 'Philippines', code: 'PH'}, 
    {name: 'Pitcairn', code: 'PN'}, 
    {name: 'Poland', code: 'PL'}, 
    {name: 'Portugal', code: 'PT'}, 
    {name: 'Puerto Rico', code: 'PR'}, 
    {name: 'Qatar', code: 'QA'}, 
    {name: 'Reunion', code: 'RE'}, 
    {name: 'Romania', code: 'RO'}, 
    {name: 'Russian Federation', code: 'RU'}, 
    {name: 'RWANDA', code: 'RW'}, 
    {name: 'Saint Helena', code: 'SH'}, 
    {name: 'Saint Kitts and Nevis', code: 'KN'}, 
    {name: 'Saint Lucia', code: 'LC'}, 
    {name: 'Saint Pierre and Miquelon', code: 'PM'}, 
    {name: 'Saint Vincent and the Grenadines', code: 'VC'}, 
    {name: 'Samoa', code: 'WS'}, 
    {name: 'San Marino', code: 'SM'}, 
    {name: 'Sao Tome and Principe', code: 'ST'}, 
    {name: 'Saudi Arabia', code: 'SA'}, 
    {name: 'Senegal', code: 'SN'}, 
    {name: 'Serbia and Montenegro', code: 'CS'}, 
    {name: 'Seychelles', code: 'SC'}, 
    {name: 'Sierra Leone', code: 'SL'}, 
    {name: 'Singapore', code: 'SG'}, 
    {name: 'Slovakia', code: 'SK'}, 
    {name: 'Slovenia', code: 'SI'}, 
    {name: 'Solomon Islands', code: 'SB'}, 
    {name: 'Somalia', code: 'SO'}, 
    {name: 'South Africa', code: 'ZA'}, 
    {name: 'South Georgia and the South Sandwich Islands', code: 'GS'}, 
    {name: 'Spain', code: 'ES'}, 
    {name: 'Sri Lanka', code: 'LK'}, 
    {name: 'Sudan', code: 'SD'}, 
    {name: 'Suriname', code: 'SR'}, 
    {name: 'Svalbard and Jan Mayen', code: 'SJ'}, 
    {name: 'Swaziland', code: 'SZ'}, 
    {name: 'Sweden', code: 'SE'}, 
    {name: 'Switzerland', code: 'CH'}, 
    {name: 'Syrian Arab Republic', code: 'SY'}, 
    {name: 'Taiwan, Province of China', code: 'TW'}, 
    {name: 'Tajikistan', code: 'TJ'}, 
    {name: 'Tanzania, United Republic of', code: 'TZ'}, 
    {name: 'Thailand', code: 'TH'}, 
    {name: 'Timor-Leste', code: 'TL'}, 
    {name: 'Togo', code: 'TG'}, 
    {name: 'Tokelau', code: 'TK'}, 
    {name: 'Tonga', code: 'TO'}, 
    {name: 'Trinidad and Tobago', code: 'TT'}, 
    {name: 'Tunisia', code: 'TN'}, 
    {name: 'Turkey', code: 'TR'}, 
    {name: 'Turkmenistan', code: 'TM'}, 
    {name: 'Turks and Caicos Islands', code: 'TC'}, 
    {name: 'Tuvalu', code: 'TV'}, 
    {name: 'Uganda', code: 'UG'}, 
    {name: 'Ukraine', code: 'UA'}, 
    {name: 'United Arab Emirates', code: 'AE'}, 
    {name: 'United Kingdom', code: 'GB'}, 
    {name: 'United States', code: 'US'}, 
    {name: 'United States Minor Outlying Islands', code: 'UM'}, 
    {name: 'Uruguay', code: 'UY'}, 
    {name: 'Uzbekistan', code: 'UZ'}, 
    {name: 'Vanuatu', code: 'VU'}, 
    {name: 'Venezuela', code: 'VE'}, 
    {name: 'Viet Nam', code: 'VN'}, 
    {name: 'Virgin Islands, British', code: 'VG'}, 
    {name: 'Virgin Islands, U.S.', code: 'VI'}, 
    {name: 'Wallis and Futuna', code: 'WF'}, 
    {name: 'Western Sahara', code: 'EH'}, 
    {name: 'Yemen', code: 'YE'}, 
    {name: 'Zambia', code: 'ZM'}, 
    {name: 'Zimbabwe', code: 'ZW'} 
]

const idtypes = [{id:1, name:'Passport'}, {id:2, name:'Country ID Card'}]

const recordstatus = [{id:0, 'name':'Draft'},{id:1, 'name':'Submitted'},{id:2, 'name':'Verified'},{id:3, 'name':'Approved'}]

const hospitalityDeptsIDs = [25, 20, 16, 18, 14, 33, 43, 44, 45, 46, 47, 48]

const regionminis = [11,27, 40, 77, 95, 110, 78, 38]

const regioncentral = [30, 36, 75, 76, 77, 103, 104, 137, 138, 139]
const regionsgra = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12,  14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 26, 27, 28, 29, 31, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47,  49, 50, 51, 52, 53, 54, 55, 58, 60, 61, 62, 63, 64, 65, 66, 67, 70, 71, 72, 73, 74, 78, 79, 94, 95, 96, 97, 98, 99, 100, 101, 102, 105, 106, 107, 108, 109, 110, 111, 112, 115, 116, 119, 120, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 148, 149, 150, 151, 152, 154, 155, 156, 158, 159, 160, 161, 162, 163, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 177, 178,179]
const regionswestern = [34, 59, 121, 140, 143, 157]
const regionashanti = [56, 57, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 114, 117, 153]
const regionvolta = [13, 68, 69, 48]
const regionnorth = [32, 35, 141, 142]
const regioneastern = [25, 80, 81, 82, 113]
const regionbrong = [33, 134, 135, 136, 144, 145, 146, 147, 164]

const findrecordstatus = (status) => {
    return  recordstatus.find((rs) => rs.id == status ) ? recordstatus.find((rs) => rs.id == status ).name.toUpperCase() : ''
}

const findstatus = (status) => {
    return  statuses.find((rs) => rs.id == status ) ? statuses.find((rs) => rs.id == status ).name.toUpperCase() : ''
}

const findposition = (position) => {
    const found = positions.find((p) => p.id == position)
    if (found) return found.name.toUpperCase()
    if (position == 1 || !position) return 'EMPLOYEE'
    return ''
}

const findrelation = (rel) => {
    return  relations.find((r) => r.id == rel ) ? relations.find((r) => r.id == rel ).name.toUpperCase() : ''
}

const findgender = (gen) => {
    return  genders.find((g) => g.id == gen ) ? genders.find((g) => g.id == gen ).name.toUpperCase() : ''
}

const findcompany = (c) => {
    const found = companies.find((comp) => comp.id == c )
    if(found) return found.name.toUpperCase()
    else return 'No Company'
}

const findcountry = (country) => {
    return  countries.find((c) => c.code == country ) ? countries.find((c) => c.code == country ).name.toUpperCase() : ''
}

const findconttypes = (conttype) => {
    return  conttypes.find((c) => c.code == conttype ) ? conttypes.find((c) => c.code == conttype ).name.toUpperCase() : ''
}

const findidtypes = (idtype) => {
    return  idtypes.find((i) => i.id == idtype ) ? idtypes.find((i) => i.id == idtype ).name.toUpperCase() : ''
}

const findfamilyrelation = (rel) => {
    return  familyrelation.find((r) => r.id == rel ) ? familyrelation.find((r) => r.id == rel ).name.toUpperCase() : ''
}
const findmarital = (mar) => {
    return  mstatus.find((m) => m.id == mar ) ? mstatus.find((m) => m.id == mar ).name.toUpperCase() : ''
}
const findqual = (e) => {
    return  qualtypes.find((item) => item.id == e ) ? qualtypes.find((item) => item.id == e ).name.toUpperCase() : ''
}
const finddept = (e) => {
    return  depts.find((item) => item.id == e ) ? depts.find((item) => item.id == e ).name.toUpperCase() : ''
}
const findregion = (e) => {
    return  regions.find((item) => item.id == e ) ? regions.find((item) => item.id == e ).name.toUpperCase() : ''
}
const findbranch = (e) => {
    return  branchs.find((item) => item.id == e ) ? branchs.find((item) => item.id == e ).name.toUpperCase() : ''
}
const findbankaccounttype = (e) => {
    return  bankaccounttype.find((item) => item.id == e ) ? bankaccounttype.find((item) => item.id == e ).name.toUpperCase() : ''
}
const findbank = (e) => {
    return  banks.find((item) => item.id == e ) ? banks.find((item) => item.id == e ).name.toUpperCase() : ''
}

export { mstatus,  bankaccounttype, relations, depts, regions, branchs,qualtypes, banks,familyrelation, countries, idtypes, genders, companies,conttypes, positions, hospitalityDeptsIDs, regionminis, regioncentral, regionsgra, regionswestern, regionashanti, regionvolta, regionnorth, regioneastern, regionbrong, statuses, findrelation, findmarital, findqual, finddept, findregion, findbranch, findbankaccounttype, findbank, findfamilyrelation, findcountry, findidtypes, findgender, findcompany, findconttypes, findposition, findrecordstatus, findstatus}