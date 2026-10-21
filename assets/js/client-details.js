document.addEventListener('DOMContentLoaded', function () {
    'use strict';
    function revealSection() {
        var section = document.getElementById(window.location.hash.slice(1));
        if (section && section.matches('.ui-page details')) {
            section.open = true;
            section.scrollIntoView({block: 'start'});
        }
    }
    window.addEventListener('hashchange', revealSection);
    document.querySelectorAll('.ui-section-nav a').forEach(function (link) {
        link.addEventListener('click', function () {
            var section = document.getElementById(link.hash.slice(1));
            if (section && section.tagName === 'DETAILS') section.open = true;
        });
    });
    revealSection();
});
