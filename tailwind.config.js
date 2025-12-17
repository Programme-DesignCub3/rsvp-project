/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./resources/**/*.blade.php",
        "./resources/**/*.js",
        "./resources/**/*.vue",
    ],
    theme: {
        extend: {
            colors: {
                navy: "#182e55",
                "bni-gold": "#EFC8A8",
                "bni-gold-dark": "#c3996b",
                "red-bni": "#bd2232",
            },
            backgroundImage: {
                landing: "url('../img/landing-bg-pattern.svg')",
                stats: "url('../img/stats-bg.svg')",
                "why-bni": "url('../img/why-bni-bg.png')",
                activities: "url('../img/showcase-bg.png')",
            },
        },
    },
    plugins: [require("@tailwindcss/forms")],
};
