const lerp = (a, b, n) => (1 - n) * a + n * b;
const getRandomFloat = (min, max) => (Math.random() * (max - min) + min).toFixed(2);
const camelToSnakeCase = str => str.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
const camelToDash = str => str.replace(/[A-Z]/g, letter => `-${letter.toLowerCase()}`);
const map = (x, a, b, c, d) => (x - a) * (d - c) / (b - a) + c;
const clamp = (num, min, max) => num <= min ? min : num >= max ? max : num;

let VW = window.innerWidth;
let VH = window.innerHeight;
document.addEventListener('resize', (e) => {
    VW = window.innerWidth;
    VH = window.innerHeight;
});

let MOUSE = { x: 0, y: 0 };
let MOUSE_NORMALIZED = { x: 0, y: 0 };
window.addEventListener('mousemove', e => {
    MOUSE = { x : e.clientX, y : e.clientY };
    MOUSE_NORMALIZED = { x: MOUSE.x / VW, y : MOUSE.y / VH }
});

export { lerp, getRandomFloat, camelToSnakeCase, camelToDash, map, clamp, MOUSE, MOUSE_NORMALIZED, VW, VH };
