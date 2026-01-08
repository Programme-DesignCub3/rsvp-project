import Glide from "@glidejs/glide";

const config = {
    type: "slider",
    rewind: false,
    bound: true,
    // autoplay: 4000,
    gap: 24,
    peek: { before: 0, after: 48 },
    perView: 3,
    breakpoints: {
        1024: {
            perView: 2,
        },
        768: {
            perView: 1,
            gap: 6,
            peek: { before: 0, after: 12 },
        },
    },
};

const carouselNow = new Glide("#glide-now", config);
const carouselPast = new Glide("#glide-past", { ...config, autoplay: 4000 });

const showcaseSliderElement = document.getElementById("glide-showcase");

if (showcaseSliderElement) {
    const carouselShowcase = new Glide("#glide-showcase", {
        type: "slider",
        perView: 1,
        // gap: 24,
        autoplay: 4000,
        // perView: 3,
    });
    carouselShowcase.mount();
}

carouselNow.mount();
carouselPast.mount();
