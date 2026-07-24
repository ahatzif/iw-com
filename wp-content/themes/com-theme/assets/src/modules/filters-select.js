import { module } from 'modujs';
import axios from "axios";

export default class extends module {
  constructor(m) {
    super(m);
    this.href = this.el.dataset.href;
    this.select = this.el.querySelector('select');
    this.target = document.querySelector(this.el.dataset.target);
    this.select.addEventListener('change', this.onChange.bind(this));
  }

  reset(m) {
    if (m !== this) {
      this.el.dispatchEvent(new CustomEvent('hard-reset'));
    }
  }
  onChange() {
    let newURL = this.href + this.select.value;
    this.el.classList.add('loading');
    window.history.pushState({ page: 'somePage' }, 'Title', newURL);
    axios.get(newURL).then(response => {

      this.el.classList.remove('loading');
      this.updateDOM(response)
    });

  }

  updateDOM(response, link = false) {
    let doc = document.createElement("div");
    doc.innerHTML = response.data;
    doc.innerHTML = doc.querySelector(this.el.dataset.target).innerHTML;
    this.target.innerHTML = '';
    let newDivs = [];
    [...doc.querySelectorAll('& > div')].forEach(div => {
      div.classList.add('ajax-loaded');
      newDivs.push(div);
      this.target.append(div);
    });
    this.call('update', false, 'Scroll');
    this.call('updateLazy', false, 'Scroll');
    this.call('addParallaxImages', this.target.querySelectorAll(".ajax-loaded [data-parallax]"), 'Scroll');
    this.call('update', this.target, 'app');
    newDivs.forEach(div => div.classList.remove('ajax-loaded'));
  }

}
