const blinkTexts = document.querySelectorAll('.blink');

blinkTexts.forEach((element) => {
    setInterval(() => {
        element.style.visibility = (element.style.visibility === 'hidden') ? 'visible' : 'hidden';
    }, 700);
});

for (let i = 1; i <= 8; i++) {
    const cardImage = document.querySelector(`.card-image-${i}`);
    const startBody = document.getElementById(`cardStartBody${i}`);
    const endBody = document.getElementById(`cardEndBody${i}`);

    if (cardImage && startBody && endBody) {
        cardImage.addEventListener('click', function() {
            // Toggle visibility classes
            startBody.classList.toggle('hidden');
            startBody.classList.toggle('visible');
            
            endBody.classList.toggle('hidden');
            endBody.classList.toggle('visible');
        });
    }
}

const fullscreenBtn = document.getElementById('fullscreenBtn');

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.error(`Failed to enter fullscreen mode: ${err.message}`);
        });
        fullscreenBtn.classList.add('fullscreen');
    } else {
        document.exitFullscreen().catch(err => {
            console.error(`Failed to exit fullscreen mode: ${err.message}`);
        });
        fullscreenBtn.classList.remove('fullscreen');
    }
}

fullscreenBtn.addEventListener('click', toggleFullscreen);

function checkAndToggleClass() {
    var saleSpans = document.querySelectorAll('.position-absolute.sale');

    saleSpans.forEach(function(saleSpan) {
    var blinkSpan = saleSpan.querySelector('.blink');

    if (blinkSpan.textContent.trim() === '') {
            saleSpan.classList.add('hidden');
        } else {
            // Если не пусто, убираем класс 'hidden'
            saleSpan.classList.remove('hidden');
        }
    });
}

document.addEventListener('DOMContentLoaded', checkAndToggleClass);

document.addEventListener('DOMContentLoaded', function () {
    // Собираем имена файлов из комментариев <!-- INPUT: CardN_Image -->file<!-- END INPUT -->
    const html = document.documentElement.innerHTML;
    const regex = /<!-- INPUT: Card(\d+)_Image -->(.*?)<!-- END INPUT -->/g;
    const files = {};
    let m;

    while ((m = regex.exec(html)) !== null) {
        const num = m[1];
        const fileName = m[2].trim();
        if (fileName) {
            files[num] = fileName;
        }
    }

    // Проставляем src всем img.typeicon с классом card-image-N
    document.querySelectorAll('img.typeicon').forEach(img => {
        const cls = Array.from(img.classList).find(c => c.startsWith('card-image-'));
        if (!cls) return;

        const num = cls.split('-')[2]; // card-image-1 -> "1"
        const fileName = files[num];
        if (!fileName) return;

        img.src = 'images/beerIcons/' + fileName;
    });
});