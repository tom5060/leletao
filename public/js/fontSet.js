(function (doc, win) {
    doc.getElementsByTagName('html')[0].style.display = 'none';
    var docEl = doc.documentElement,
        resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
        recalc = function () {
            var clientWidth = docEl.clientWidth;
            if (clientWidth >= 750) {
                clientWidth = 750
            }
            if (!clientWidth) return;
            docEl.style.fontSize = 20 * (clientWidth / 375) + 'px';
            doc.getElementsByTagName('html')[0].style.display = 'block';
        };
    if (!doc.addEventListener) return;
    win.addEventListener(resizeEvt, recalc, false);
    doc.addEventListener('DOMContentLoaded', recalc, false);
    // setInterval(recalc, 500);
})(document, window);