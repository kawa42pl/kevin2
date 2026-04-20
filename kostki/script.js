const $ = (e) => document.querySelector(e);
const $$ = (e) => document.querySelectorAll(e);
let oczka = []
let ruch = 0;

function blokujOdblokuj(kostka) {
    console.log(kostka);
    if (kostka.hasAttribute("disabled")) {
        kostka.removeAttribute("disabled");
    } else {
        kostka.setAttribute("disabled", "");
    }
}

function reset() {
    let kostki = $$(".kostka");
    kostki.forEach(kostka => {
        kostka.className = "kostka n0";
        kostka.removeAttribute("disabled");
    });

    oczka = [];
    ruch = 0;
    $(".wynik span").innerHTML = "0";
}

function losuj() {
    let kostki = $$(".kostka");
    let oczka = [];
    kostki.forEach(kostka => {
        if (!kostka.hasAttribute("disabled")) {
            let los = Math.floor(Math.random() * 6) + 1;
            oczka.push(los);
            kostka.className = "kostka n" + los;
        }
    });
    if (oczka.length > 0) {
        $(".wynik span").innerHTML = oczka.reduce((a, b) => a + b);
    }
}
