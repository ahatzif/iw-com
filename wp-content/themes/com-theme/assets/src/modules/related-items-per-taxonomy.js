import { module } from "modujs";

export default class extends module {
    constructor(m) {
        super(m);
        this.events = { click: { toggle: "handleToggle" } };
    }

    init() { this.setupElements(); }

    destroy() { }

    setupElements() {
        this.activeToggle = null;
        this.activePosts = null;
    }

    handleToggle(e) {
        this.activeToggle = this.el.querySelector(".toggle.active");
        this.activePosts = this.el.querySelector(".posts.active");

        if (this.activeToggle) this.activeToggle.classList.remove("active");
        if (this.activePosts) this.activePosts.classList.remove("active");

        const clickedToggle = e.currentTarget;

        clickedToggle.classList.add("active");
        const targetPosts = this.el.querySelector(`.posts[data-taxonomy="${clickedToggle.dataset.taxonomy}"]`);
        if (targetPosts) targetPosts.classList.add("active");
    }
}
