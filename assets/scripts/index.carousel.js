window.addEventListener("load", function() {
    const carousel = document.getElementById("carousel");
    const slidesCount = carousel.querySelectorAll(".index-carousel-slide").length;
    let timesScrolled = 0
    setInterval(() => {
        carousel.scrollLeft += carousel.clientWidth;
        timesScrolled++;
        if (timesScrolled >= slidesCount) {
            carousel.scrollLeft -= carousel.clientWidth * slidesCount;
            timesScrolled = 0;
        }
    }, 5000)

    window.addEventListener("resize", function() {
        carousel.scrollLeft = 0;
        timesScrolled = 0;
    });
});