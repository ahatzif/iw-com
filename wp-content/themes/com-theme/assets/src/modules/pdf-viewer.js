import { module } from 'modujs';
pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.worker.min.js';
export default class extends module {
    constructor(m) {
        super(m);
        this.pdfSrc = this.el.dataset.pdfSrc;
        this.imagesContainer = this.$( 'image-container' )[0];
        this.manuscriptsModule = this.el.querySelector( '[data-module-manuscript-pages]' );
    }
    init() {
        if( this.manuscriptsModule ){
            this.manuscriptsModuleName = this.manuscriptsModule.dataset.moduleManuscriptPages;
            this.renderPDF();
        }
    }

    async renderPDF() {
      try {
        const loadingTask = pdfjsLib.getDocument(this.pdfSrc);
        const pdf = await loadingTask.promise;
        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
          const page = await pdf.getPage(pageNum);
          const viewport = page.getViewport({ scale: 1.5 });
          const canvas = document.createElement('canvas');
          const ctx = canvas.getContext('2d');
          canvas.width = viewport.width;
          canvas.height = viewport.height;
          await page.render({ canvasContext: ctx, viewport }).promise;
          const img = document.createElement('img');
          img.src = canvas.toDataURL('image/png');
          img.alt = `Page ${pageNum}`;
          img.dataset.page = pageNum;
          img.classList.add( 'w-full' );
          this.imagesContainer.appendChild(img);
        }
          this.call( 'initPages', pdf.numPages, 'ManuscriptPages', this.manuscriptsModuleName );
      } catch (err) {
        console.error('Error loading PDF:', err);
      }
    }
}
