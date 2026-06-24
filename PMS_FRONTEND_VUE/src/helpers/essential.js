import { toast } from 'vue3-toastify';

const log = (text) => {
    console.log(text)
}

var rH = function (a) {
    if (a < 10) return '0' + a;
    else return a;
}

var afDate = function (a) {

    var monthLetter = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    if (a !== null) {
        var b = new Date(a)

        return rH(b.getDate()) + ' ' + monthLetter[b.getMonth()] + ' ' + b.getFullYear() + ', ' + rH(b.getHours()) + ':' + rH(b.getMinutes());
    } else {
        return '';
    }

}

var aDate = function (a) {
    var monthLetter = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    if (a !== null) {
        var b = new Date(a)
        return rH(b.getDate()) + ' ' + monthLetter[b.getMonth()] + ' ' + b.getFullYear();
    } else {
        return '';
    }
}
var aToday = function (a) {
    if (a !== null) {
        var b = new Date(a)
        return b.getFullYear() + '-' + rH(b.getMonth() + 1) + '-' + rH(b.getDate());
    } else {
        return '';
    }

}
var aTime = function (a) {
    if (a !== null) {
        var b = new Date(a)
        return rH(b.getHours()) + ':' + rH(b.getMinutes());
    } else {
        return '';
    }

}

function numberToWords(num) {
    if (num === 0) return 'zero';

    const belowTwenty = [
        'zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
        'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'
    ];
    const tens = [
        '', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'
    ];
    const thousands = [
        '', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion'
    ];

    function helper(n) {
        if (n < 20) return belowTwenty[n];
        else if (n < 100) return tens[Math.floor(n / 10)] + (n % 10 !== 0 ? ' ' + belowTwenty[n % 10] : '');
        else return belowTwenty[Math.floor(n / 100)] + ' hundred' + (n % 100 !== 0 ? ' ' + helper(n % 100) : '');
    }

    function integerToWords(integerPart) {
        let result = '';
        let thousandCounter = 0;

        while (integerPart > 0) {
            const part = integerPart % 1000;
            if (part !== 0) {
                const partInWords = helper(part);
                result = partInWords + (thousands[thousandCounter] ? ' ' + thousands[thousandCounter] : '') + (result ? ' ' + result : '');
            }
            integerPart = Math.floor(integerPart / 1000);
            thousandCounter++;
        }

        return result.trim();
    }

    function decimalToWords(decimalPart) {
        return decimalPart.split('').map(digit => belowTwenty[parseInt(digit)]).join(' ');
    }

    let [integerPart, decimalPart] = num.toString().split('.');

    let words = integerToWords(parseInt(integerPart));
    if (decimalPart) {
        words += ' and ' + numberToWords(decimalPart) + ' peswas';
    }

    return words;
}
function formatMoney(value) {
    return parseFloat(value).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

function convertHTMLtoPDF(target) {
    const { jsPDF } = window.jspdf;

    let doc = new jsPDF('p', 'pt', 'a4');


    let pdfjs = document.querySelector('#' + target);

    let pWidth = 595.28
    let srcWidth = document.getElementById(target).scrollWidth;
    let scale = (pWidth - 12 * 2) / srcWidth;

    doc.html(pdfjs, {

        html2canvas: {
            scale: scale,
        },
        callback: function (doc) {
            window.open(doc.output('bloburl'));
        },
        x: 12,
        y: 12,
    });
}

const gmonths = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

const getMonths = (a) => {
    return gmonths[a - 1];
}

const toastt = (text, type = 'success') => {
    toast(text, {
        "type": type,
        "dangerouslyHTMLString": true,
        "autoClose": 2000,
    })
}

const calculateAge = (birthDateString) => {
    // Parse the input string into a Date object
    const birthDate = new Date(birthDateString);
    const today = new Date();

    // Calculate the difference in years
    let age = today.getFullYear() - birthDate.getFullYear();

    // Adjust if the birth date hasn't occurred yet this year
    const monthDiff = today.getMonth() - birthDate.getMonth();
    const dayDiff = today.getDate() - birthDate.getDate();

    if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) {
        age--;
    }

    return age;
}

import Swal from 'sweetalert2';

const imgburl = '/storage/';

const showAlert = (title, text, icon = 'success') => {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon,
        confirmButtonColor: '#7c3aed', // Primary purple
        confirmButtonText: 'OK'
    });
}

const showConfirm = (title, text, icon = 'warning', confirmButtonText = 'Yes, proceed') => {
    return Swal.fire({
        title: title,
        text: text,
        icon: icon,
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#d33',
        confirmButtonText: confirmButtonText
    });
}

export { log, afDate, aDate, aTime, aToday, numberToWords, convertHTMLtoPDF, getMonths, formatMoney, toastt, calculateAge, imgburl, showAlert, showConfirm }