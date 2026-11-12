/* Turns the addresses back. Server side: KreativSoehne\SuluEmailObfuscatorBundle. */
(function () {
    var ATTRIBUTE = 'data-obfuscated-email';
    var ADDRESS = /^[\w.+-]+@[\w-]+(?:\.[\w-]+)+$/;

    function rot13(value) {
        return value.replace(/[a-z]/gi, function (character) {
            var code = character.charCodeAt(0);
            var base = code < 97 ? 65 : 97;

            return String.fromCharCode(((code - base + 13) % 26) + base);
        });
    }

    function turnBackText(node) {
        for (var child = node.firstChild; child; child = child.nextSibling) {
            if (child.nodeType === 3) {
                child.nodeValue = child.nodeValue.replace(/[\w.+-]+@[\w-]+(?:\.[\w-]+)+/g, rot13);
            } else if (child.nodeType === 1) {
                turnBackText(child);
            }
        }
    }

    function decode() {
        var links = document.querySelectorAll('a[' + ATTRIBUTE + ']');

        for (var i = 0; i < links.length; i++) {
            var link = links[i];
            var href = link.getAttribute('href') || '';

            if (href.indexOf('mailto:') !== 0) {
                continue;
            }

            // A subject behind the question mark is readable and stays as it is.
            var target = href.slice(7);
            var separator = target.indexOf('?');
            var address = separator === -1 ? target : target.slice(0, separator);
            var query = separator === -1 ? '' : target.slice(separator);

            link.setAttribute('href', 'mailto:' + rot13(address) + query);

            // Same rule as on the server: only a label whose visible text is an
            // address gets turned back, a word stays readable. Markup inside the
            // label (an icon, a span) stays, only its text is turned back.
            var label = link.textContent.trim();

            if (ADDRESS.test(label)) {
                turnBackText(link);
            }

            link.removeAttribute(ATTRIBUTE);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', decode);
    } else {
        decode();
    }
})();
