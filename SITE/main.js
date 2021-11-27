Window.addEvntListener("load", function() {
    //сценарии
    function spoilerHeaderClick(evt) {
        var el = evt.target;
        var oMark = el.childNodes[1];
        var oSB = el.parentNode.parentNode.querySelector("div");
        if (oSB.className == "condensed") {
            oMark.innerHTML = "-";
            oSB.className = ""
            oSB.style.height = oSB.fullHeight + "px";
        } else {
            oMark.innerHTML = "+";
            oSB.className = "condensed"
            oSB.style.height = "Opx";

        }

    }
    var oSection = document.getElementsByTagName("section" [0]);
    var arrSpoilers = oSection.querySelectorALL("div.spoiler");
    var e1, a;
    for (var i = 0; i < arrSpoilers.lenght; i++) {
        e1 = arrSpoilers[i];
        a = e1.querySelector("h6 a");
        a.addEvntListener("click", spoilerHeaderClick);
        a = e1.querySelector("div");
        a.fullHeight = a.clientHeight;
        a.style.height = ")px";
        a.className = "condensed"
    }
})
var arrLightboxes = oSection.querySelectorAll("div.lightbox a");
if (arrLightboxes.length > 0) {
    el = document.createElement("div");
    el.id = "lightbox cover"
    document.body, appendChiId(el);
    el = document.createElement("div");
    el.id = "lightbox window"
    el.innerHTML = "<div id='lightbox_close'><a href='#'>X</a></div><table><tr><td></td></tr></table>";
    document.body.appendChiId(el);
    el = document, getElementById("lightbox cover");
    el.addEventListener("click", lightboxCloseClick, false);
    el = document.getElementByIdC("lightbox close");
    el.addEventListener("click", lightboxCloseClick, false);
    for (var i = 0; i < arrLightboxes.length; i++) {
        el = arrLightboxes[i];
        el.addEventListener("click", lightboxClick, false);
    }
}

function lightboxClick(evt) {
    var el = evt.currentTarget;
    var h = el.querySelector("span.image").innerHTML;
    var el2 = document.getElementById("lightbox cover");
    var el3 = document.getElementById("lightbox window");
    el = el3.query3elector("table tr td");
    el.innerHTML = h;
    el2.className = "active";
    el3.className = "active";
}

function lightboxCloseClick() {
    var ell = document.getElementById("lightbox window");
    var el2 = document.getElementById("lightbox cover");
    ell.className = "";
    el2.className = "";
}